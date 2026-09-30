<?php

namespace App\Console\Commands;

use App\Models\Question;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class AuditResearchForms extends Command
{
    private const TOTAL_ITEMS = 60;

    private const ITEMS_PER_DOMAIN = 10;

    private const BLUEPRINTS = [
        Question::FORM_PRE_TEST_A => [
            1 => ['PC-01' => 5, 'PC-02' => 4, 'PC-03' => 1],
            2 => ['PB-01' => 5, 'PB-02' => 5],
            3 => ['PP-01' => 2, 'PP-02' => 4, 'PP-03' => 2, 'PP-04' => 2],
            4 => ['PK-01' => 6, 'PK-02' => 2, 'PK-03' => 2],
            5 => ['PH-01' => 3, 'PH-02' => 3, 'PH-03' => 2, 'PH-04' => 2],
            6 => ['QC-01' => 3, 'QC-02' => 3, 'QC-03' => 2, 'QC-04' => 2],
        ],
        Question::FORM_POST_TEST_B => [
            1 => ['PC-01' => 4, 'PC-02' => 5, 'PC-03' => 1],
            2 => ['PB-01' => 5, 'PB-02' => 5],
            3 => ['PP-01' => 2, 'PP-02' => 4, 'PP-03' => 2, 'PP-04' => 2],
            4 => ['PK-01' => 6, 'PK-02' => 2, 'PK-03' => 2],
            5 => ['PH-01' => 3, 'PH-02' => 3, 'PH-03' => 2, 'PH-04' => 2],
            6 => ['QC-01' => 2, 'QC-02' => 2, 'QC-03' => 3, 'QC-04' => 3],
        ],
    ];

    protected $signature = 'research:audit-forms
        {--form= : Audit one form only}
        {--require-validation : Require both validation fields}';

    protected $description = 'Audit research forms for blueprint, content, answer-key, and validation integrity';

    public function handle(): int
    {
        $selectedForm = trim((string) ($this->option('form') ?? ''));
        $forms = $selectedForm === ''
            ? array_keys(self::BLUEPRINTS)
            : [$selectedForm];

        foreach ($forms as $form) {
            if (! array_key_exists($form, self::BLUEPRINTS)) {
                $this->error("Unknown research form: {$form}");

                return self::FAILURE;
            }
        }

        $requireValidation = (bool) $this->option('require-validation');
        $failed = false;

        foreach ($forms as $form) {
            [$metrics, $errors] = $this->auditForm(
                $form,
                $requireValidation,
            );

            $this->newLine();
            $this->info("Research-form audit: {$form}");
            $this->table(
                ['Check', 'Result'],
                collect($metrics)
                    ->map(fn (mixed $value, string $label): array => [
                        $label,
                        $value,
                    ])
                    ->values()
                    ->all(),
            );

            if ($errors === []) {
                $this->info("{$form} passed the requested audit.");

                continue;
            }

            $failed = true;
            $this->error("{$form} failed with ".count($errors).' issue(s):');

            foreach ($errors as $error) {
                $this->line("  - {$error}");
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array{0: array<string, int|string>, 1: array<int, string>}
     */
    private function auditForm(
        string $form,
        bool $requireValidation,
    ): array {
        $questions = Question::query()
            ->where('research_form', $form)
            ->where('is_active', true)
            ->with([
                'choices',
                'competency.domain',
            ])
            ->orderBy('form_position')
            ->get();

        $errors = [];
        $activeCount = $questions->count();

        if ($activeCount !== self::TOTAL_ITEMS) {
            $errors[] = "Expected 60 active questions; found {$activeCount}.";
        }

        $positions = $questions
            ->pluck('form_position')
            ->map(fn (mixed $position): int => (int) $position)
            ->sort()
            ->values()
            ->all();

        if ($positions !== range(1, self::TOTAL_ITEMS)) {
            $errors[] = 'Active positions must uniquely cover 1 through 60.';
        }

        $missingRelationships = $questions->filter(
            fn (Question $question): bool => $question->competency === null
                || $question->competency->domain === null,
        );

        if ($missingRelationships->isNotEmpty()) {
            $errors[] = 'Questions with missing competency/domain relationships: '
                .$this->positions($missingRelationships).'.';
        }

        $domainCounts = $questions
            ->filter(fn (Question $question): bool => $question->competency?->domain !== null)
            ->countBy(fn (Question $question): int => (int) $question->competency->domain->domain_number);

        foreach (range(1, 6) as $domainNumber) {
            $actual = (int) $domainCounts->get($domainNumber, 0);

            if ($actual !== self::ITEMS_PER_DOMAIN) {
                $errors[] = "Domain {$domainNumber} requires 10 items; found {$actual}.";
            }
        }

        $competencyCounts = $questions
            ->filter(fn (Question $question): bool => $question->competency !== null)
            ->countBy(fn (Question $question): string => $question->competency->competency_code);

        $expectedCodes = [];

        foreach (self::BLUEPRINTS[$form] as $domainNumber => $quotas) {
            foreach ($quotas as $code => $expected) {
                $expectedCodes[] = $code;
                $actual = (int) $competencyCounts->get($code, 0);

                if ($actual !== $expected) {
                    $errors[] = "{$code} requires {$expected} item(s); found {$actual}.";
                }
            }

            $wrongDomain = $questions->filter(
                fn (Question $question): bool => in_array(
                    $question->competency?->competency_code,
                    array_keys($quotas),
                    true,
                )
                    && (int) $question->competency?->domain?->domain_number
                        !== $domainNumber,
            );

            if ($wrongDomain->isNotEmpty()) {
                $errors[] = "Domain mismatch for {$domainNumber} at positions "
                    .$this->positions($wrongDomain).'.';
            }
        }

        $unexpectedCodes = $competencyCounts
            ->keys()
            ->diff($expectedCodes)
            ->values();

        if ($unexpectedCodes->isNotEmpty()) {
            $errors[] = 'Unexpected competency codes: '
                .$unexpectedCodes->implode(', ').'.';
        }

        $invalidChoices = $questions->filter(
            fn (Question $question): bool => $question->choices->count() !== 4
                || $question->choices
                    ->where('is_correct', true)
                    ->count() !== 1,
        );

        if ($invalidChoices->isNotEmpty()) {
            $errors[] = 'Questions without four choices and exactly one correct choice: '
                .$this->positions($invalidChoices).'.';
        }

        $emptyContent = $questions->filter(
            fn (Question $question): bool => trim($question->question_text) === ''
                || $question->choices->contains(
                    fn ($choice): bool => trim($choice->choice_text) === '',
                ),
        );

        if ($emptyContent->isNotEmpty()) {
            $errors[] = 'Questions with an empty stem or choice: '
                .$this->positions($emptyContent).'.';
        }

        $placeholders = $questions->filter(
            fn (Question $question): bool => str_contains(
                mb_strtoupper($question->question_text),
                'DEVELOPMENT MOCK',
            ),
        );

        if ($placeholders->isNotEmpty()) {
            $errors[] = 'Development placeholders remain at positions '
                .$this->positions($placeholders).'.';
        }

        $duplicatePositions = $questions
            ->groupBy(fn (Question $question): string => $this->normalizeStem($question->question_text))
            ->filter(fn (Collection $group, string $stem): bool => $stem !== '' && $group->count() > 1)
            ->map(fn (Collection $group): string => $this->positions($group))
            ->values();

        if ($duplicatePositions->isNotEmpty()) {
            $errors[] = 'Exact normalized duplicate stems occur at positions '
                .$duplicatePositions->implode('; ').'.';
        }

        $missingRationales = $questions->filter(
            fn (Question $question): bool => trim((string) $question->hypercorrection_rationale) === '',
        );

        if ($missingRationales->isNotEmpty()) {
            $errors[] = 'Missing rationales at positions '
                .$this->positions($missingRationales).'.';
        }

        $invalidCognitiveLevels = $questions->filter(
            fn (Question $question): bool => ! in_array(
                $question->cognitive_level,
                Question::COGNITIVE_LEVELS,
                true,
            ),
        );

        if ($invalidCognitiveLevels->isNotEmpty()) {
            $errors[] = 'Missing or invalid cognitive levels at positions '
                .$this->positions($invalidCognitiveLevels).'.';
        }

        $fullyValidated = $questions->filter(
            fn (Question $question): bool => $question->research_validated_at !== null
                && $question->research_validated_by !== null,
        )->count();

        $partialValidation = $questions->filter(
            fn (Question $question): bool => ($question->research_validated_at === null)
                !== ($question->research_validated_by === null),
        );

        if ($partialValidation->isNotEmpty()) {
            $errors[] = 'Partially validated questions occur at positions '
                .$this->positions($partialValidation).'.';
        }

        if ($requireValidation && $fullyValidated !== self::TOTAL_ITEMS) {
            $errors[] = "Expected 60 fully validated questions; found {$fullyValidated}.";
        }

        $metrics = [
            'Active questions' => "{$activeCount}/60",
            'Fully validated' => "{$fullyValidated}/60",
            'Placeholders' => $placeholders->count(),
            'Positions 1-60' => $positions === range(1, self::TOTAL_ITEMS)
                ? 'Pass'
                : 'Fail',
            'Domains with 10 items' => collect(range(1, 6))
                ->filter(fn (int $domain): bool => (int) $domainCounts->get($domain, 0)
                    === self::ITEMS_PER_DOMAIN)
                ->count().'/6',
            'Invalid choice structures' => $invalidChoices->count(),
            'Empty stems/choices' => $emptyContent->count(),
            'Duplicate stems' => $duplicatePositions->count(),
            'Missing rationales' => $missingRationales->count(),
            'Missing/invalid cognitive levels' => $invalidCognitiveLevels->count(),
        ];

        return [$metrics, $errors];
    }

    private function normalizeStem(string $stem): string
    {
        $normalized = mb_strtolower(trim($stem));
        $normalized = preg_replace('/[^\pL\pN]+/u', ' ', $normalized)
            ?? $normalized;

        return trim(preg_replace('/\s+/u', ' ', $normalized) ?? $normalized);
    }

    /** @param Collection<int, Question> $questions */
    private function positions(Collection $questions): string
    {
        return $questions
            ->pluck('form_position')
            ->map(fn (mixed $position): int => (int) $position)
            ->sort()
            ->implode(', ');
    }
}
