<?php

namespace App\Services;

use App\Models\Question;
use App\Models\ResearchFormVersion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class ResearchFormAuditService
{
    public const STATUS_DEVELOPMENT = 'development';

    public const STATUS_NEEDS_REVIEW = 'needs_review';

    public const STATUS_STRUCTURALLY_VALID = 'structurally_valid';

    public const STATUS_VALIDATED = 'validated';

    public const STATUS_LOCKED = 'locked';

    public const STATUS_READY = 'ready_for_pilot';

    private const FORM_DEFINITIONS = [
        Question::FORM_PRE_TEST_A => [
            'label' => 'Form A — Diagnostic',
            'version_config' => 'citirx.research.pre_test_form_version',
        ],
        Question::FORM_POST_TEST_B => [
            'label' => 'Form B — Mock Board',
            'version_config' => 'citirx.research.post_test_form_version',
        ],
    ];

    /**
     * This is the authoritative runtime blueprint.
     *
     * @var array<string, array<int, array<string, int>>>
     */
    private const BLUEPRINTS = [
        Question::FORM_PRE_TEST_A => [
            1 => ['PC-01' => 4, 'PC-02' => 5, 'PC-03' => 1],
            2 => ['PB-01' => 5, 'PB-02' => 5],
            3 => ['PP-01' => 2, 'PP-02' => 4, 'PP-03' => 2, 'PP-04' => 2],
            4 => ['PK-01' => 6, 'PK-02' => 2, 'PK-03' => 2],
            5 => ['PH-01' => 3, 'PH-02' => 3, 'PH-03' => 2, 'PH-04' => 2],
            6 => ['QC-01' => 3, 'QC-02' => 2, 'QC-03' => 3, 'QC-04' => 2],
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

    /**
     * @return array<string, array<string, string>>
     */
    public function formDefinitions(): array
    {
        return self::FORM_DEFINITIONS;
    }

    public function hasForm(string $form): bool
    {
        return array_key_exists(
            $form,
            self::FORM_DEFINITIONS,
        );
    }

    /**
     * Load both active versions together and audit only the
     * questions belonging to those versions.
     *
     * @return array<string, array<string, mixed>>
     */
    public function auditAll(
        bool $requireValidation = true,
    ): array {
        $versions = ResearchFormVersion::query()
            ->whereIn(
                'research_form',
                array_keys(
                    self::FORM_DEFINITIONS,
                ),
            )
            ->where('is_active', true)
            ->whereNotNull('active_slot')
            ->get()
            ->keyBy('research_form');

        $versionIds = $versions
            ->pluck('form_version_id')
            ->values();

        /*
     * Load the questions for both versions together. This preserves
     * the existing efficient eager-loading behavior and avoids one
     * complete query set per form.
     */
        $questionsByVersion = $versionIds->isEmpty()
            ? collect()
            : $this->baseQuery()
                ->whereIn(
                    'form_version_id',
                    $versionIds->all(),
                )
                ->orderBy('research_form')
                ->orderBy('form_position')
                ->get()
                ->groupBy('form_version_id');

        $reports = [];

        foreach (
            self::FORM_DEFINITIONS as $form => $definition
        ) {
            $version = $versions->get($form);

            $questions = $version
                ? $questionsByVersion->get(
                    $version->form_version_id,
                    collect(),
                )
                : collect();

            $reports[$form] = $this->buildReport(
                form: $form,
                questions: $questions,
                requireValidation: $requireValidation,
                version: $version,
            );
        }

        return $reports;
    }

    /**
     * Audit the currently active version of one form.
     *
     * @return array<string, mixed>
     */
    public function audit(
        string $form,
        bool $requireValidation = true,
    ): array {
        $this->assertKnownForm($form);

        $version = ResearchFormVersion::query()
            ->where('research_form', $form)
            ->where('is_active', true)
            ->where('active_slot', $form)
            ->first();

        if (! $version) {
            return $this->buildReport(
                form: $form,
                questions: collect(),
                requireValidation: $requireValidation,
                version: null,
            );
        }

        return $this->auditVersion(
            $version,
            $requireValidation,
        );
    }

    /**
     * Audit a specific active or draft version.
     *
     * This method will also be used by the 4B workflow before a version
     * can move into review, validation, or publication.
     *
     * @return array<string, mixed>
     */
    public function auditVersion(
        ResearchFormVersion $version,
        bool $requireValidation = true,
    ): array {
        $this->assertKnownForm(
            $version->research_form,
        );

        $questions = $this->baseQuery()
            ->where(
                'form_version_id',
                $version->form_version_id,
            )
            ->where(
                'research_form',
                $version->research_form,
            )
            ->orderBy('form_position')
            ->get();

        return $this->buildReport(
            form: $version->research_form,
            questions: $questions,
            requireValidation: $requireValidation,
            version: $version,
        );
    }

    private function baseQuery(): Builder
    {
        return Question::query()
            ->select([
                'question_id',
                'competency_id',
                'question_text',
                'hypercorrection_rationale',
                'research_form',
                'form_version_id',
                'form_position',
                'cognitive_level',
                'research_validated_at',
                'research_validated_by',
                'first_served_at',
                'is_active',
            ])
            ->where('is_active', true)
            ->with([
                'choices:choice_id,question_id,choice_letter,choice_text,is_correct',
                'competency:competency_id,domain_id,competency_code,title',
                'competency.domain:domain_id,domain_number,domain_name',
            ]);
    }

    /**
     * @param  Collection<int, Question>  $questions
     * @return array<string, mixed>
     */
    private function buildReport(
        string $form,
        Collection $questions,
        bool $requireValidation,
        ?ResearchFormVersion $version = null,
    ): array {
        $definition = self::FORM_DEFINITIONS[$form];
        $blueprint = self::BLUEPRINTS[$form];

        $structuralErrors = [];
        $validationErrors = [];

        $activeCount = $questions->count();

        /*
        * An empty collection must not pass Collection::every()
        * accidentally, so isNotEmpty() is checked explicitly.
        */
        $versionIsBound = $version !== null
            && $questions->isNotEmpty()
            && $questions->every(
                fn (Question $question): bool => $question->form_version_id
                    === $version->form_version_id
                    && $question->research_form
                    === $version->research_form,
            );

        if (! $versionIsBound) {
            $structuralErrors[] =
                'Questions are not bound to one '
                .'registered form version.';
        }

        if ($activeCount !== ResearchFormService::TOTAL_ITEMS) {
            $structuralErrors[] =
                "Expected 60 active questions; found {$activeCount}.";
        }

        $positions = $questions
            ->pluck('form_position')
            ->map(
                fn (mixed $position): int => (int) $position,
            )
            ->sort()
            ->values()
            ->all();

        $positionsComplete = $positions === range(
            1,
            ResearchFormService::TOTAL_ITEMS,
        );

        if (! $positionsComplete) {
            $structuralErrors[] =
                'Active positions must uniquely cover 1 through 60.';
        }

        $missingRelationships = $questions->filter(
            fn (Question $question): bool => $question->competency === null
                || $question->competency->domain === null,
        );

        if ($missingRelationships->isNotEmpty()) {
            $structuralErrors[] =
                'Questions with missing competency/domain relationships: '
                .$this->positions($missingRelationships).'.';
        }

        $domainCounts = $questions
            ->filter(
                fn (Question $question): bool => $question->competency?->domain !== null,
            )
            ->countBy(
                fn (Question $question): int => (int) $question
                    ->competency
                    ->domain
                    ->domain_number,
            );

        $domainRows = [];

        foreach (range(1, ResearchFormService::SUBJECT_COUNT) as $domain) {
            $actual = (int) $domainCounts->get(
                $domain,
                0,
            );

            $name = $questions
                ->first(
                    fn (Question $question): bool => (int) $question
                        ->competency
                        ?->domain
                        ?->domain_number === $domain,
                )
                ?->competency
                ?->domain
                ?->domain_name ?? "Domain {$domain}";

            $matches =
                $actual === ResearchFormService::ITEMS_PER_SUBJECT;

            $domainRows[] = [
                'domain_number' => $domain,
                'domain_name' => $name,
                'expected' => ResearchFormService::ITEMS_PER_SUBJECT,
                'actual' => $actual,
                'matches' => $matches,
            ];

            if (! $matches) {
                $structuralErrors[] =
                    "Domain {$domain} requires 10 items; found {$actual}.";
            }
        }

        $competencyCounts = $questions
            ->filter(
                fn (Question $question): bool => $question->competency !== null,
            )
            ->countBy(
                fn (Question $question): string => (string) $question
                    ->competency
                    ->competency_code,
            );

        $expectedCodes = [];
        $competencyRows = [];

        foreach ($blueprint as $domainNumber => $quotas) {
            foreach ($quotas as $code => $expected) {
                $expectedCodes[] = $code;

                $matchingQuestions = $questions->filter(
                    fn (Question $question): bool => $question
                        ->competency
                        ?->competency_code === $code,
                );

                $actual = $matchingQuestions->count();

                $wrongDomain = $matchingQuestions->filter(
                    fn (Question $question): bool => (int) $question
                        ->competency
                        ?->domain
                        ?->domain_number !== $domainNumber,
                );

                $matches =
                    $actual === $expected
                    && $wrongDomain->isEmpty();

                $competencyRows[] = [
                    'domain_number' => $domainNumber,
                    'competency_code' => $code,
                    'expected' => $expected,
                    'actual' => $actual,
                    'matches' => $matches,
                    'wrong_domain_positions' => $this->positionArray($wrongDomain),
                ];

                if ($actual !== $expected) {
                    $structuralErrors[] =
                        "{$code} requires {$expected} item(s); "
                        ."found {$actual}.";
                }

                if ($wrongDomain->isNotEmpty()) {
                    $structuralErrors[] =
                        "Domain mismatch for {$code} at positions "
                        .$this->positions($wrongDomain).'.';
                }
            }
        }

        $unexpectedCodes = $competencyCounts
            ->keys()
            ->diff($expectedCodes)
            ->values();

        if ($unexpectedCodes->isNotEmpty()) {
            $structuralErrors[] =
                'Unexpected competency codes: '
                .$unexpectedCodes->implode(', ').'.';
        }

        $invalidChoiceStructures = $questions->filter(
            function (Question $question): bool {
                $letters = $question
                    ->choices
                    ->pluck('choice_letter')
                    ->map(
                        fn (mixed $letter): string => mb_strtoupper(
                            trim((string) $letter),
                        ),
                    )
                    ->sort()
                    ->values()
                    ->all();

                return $question->choices->count() !== 4
                    || $letters !== ['A', 'B', 'C', 'D']
                    || $question
                        ->choices
                        ->where('is_correct', true)
                        ->count() !== 1;
            },
        );

        if ($invalidChoiceStructures->isNotEmpty()) {
            $structuralErrors[] =
                'Questions without choices A-D and exactly one '
                .'correct answer: '
                .$this->positions($invalidChoiceStructures).'.';
        }

        $missingStemsOrChoices = $questions->filter(
            fn (Question $question): bool => trim((string) $question->question_text) === ''
                || $question->choices->count() < 4
                || $question->choices->contains(
                    fn ($choice): bool => trim((string) $choice->choice_text) === '',
                ),
        );

        if ($missingStemsOrChoices->isNotEmpty()) {
            $structuralErrors[] =
                'Questions with a missing or empty stem/choice: '
                .$this->positions($missingStemsOrChoices).'.';
        }

        $placeholders = $questions->filter(
            fn (Question $question): bool => str_contains(
                mb_strtoupper(
                    (string) $question->question_text,
                ),
                'DEVELOPMENT MOCK',
            ),
        );

        if ($placeholders->isNotEmpty()) {
            $structuralErrors[] =
                'Development placeholders remain at positions '
                .$this->positions($placeholders).'.';
        }

        $duplicateStemGroups = $questions
            ->groupBy(
                fn (Question $question): string => $this->normalizeStem(
                    (string) $question->question_text,
                ),
            )
            ->filter(
                fn (Collection $group, string $stem): bool => $stem !== ''
                    && $group->count() > 1,
            )
            ->map(
                fn (Collection $group): array => $this->positionArray($group),
            )
            ->values();

        if ($duplicateStemGroups->isNotEmpty()) {
            $structuralErrors[] =
                'Exact normalized duplicate stems occur at positions '
                .$duplicateStemGroups
                    ->map(
                        fn (array $group): string => implode(', ', $group),
                    )
                    ->implode('; ').'.';
        }

        $missingRationales = $questions->filter(
            fn (Question $question): bool => trim(
                (string) $question
                    ->hypercorrection_rationale,
            ) === '',
        );

        if ($missingRationales->isNotEmpty()) {
            $structuralErrors[] =
                'Missing rationales at positions '
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
            $structuralErrors[] =
                'Missing or invalid cognitive levels at positions '
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
            $validationErrors[] =
                'Partially validated questions occur at positions '
                .$this->positions($partialValidation).'.';
        }

        if (
            $requireValidation
            && $fullyValidated !== ResearchFormService::TOTAL_ITEMS
        ) {
            $validationErrors[] =
                'Expected 60 fully validated questions; '
                ."found {$fullyValidated}.";
        }

        $servedQuestions = $questions->filter(
            fn (Question $question): bool => $question->first_served_at !== null,
        );

        $firstServedQuestion = $servedQuestions
            ->sortBy('first_served_at')
            ->first();

        $firstServedAt = $firstServedQuestion
            ?->first_served_at
            ?->toIso8601String();

        $lockedQuestions = $questions->filter(
            fn (Question $question): bool => $question->contentIsFrozen(),
        )->count();

        $domainsMatching = collect($domainRows)
            ->where('matches', true)
            ->count();

        $competenciesMatching = collect($competencyRows)
            ->where('matches', true)
            ->count();

        $structurallyValid = $structuralErrors === [];

        $validationComplete =
            $activeCount === ResearchFormService::TOTAL_ITEMS
            && $fullyValidated === ResearchFormService::TOTAL_ITEMS
            && $partialValidation->isEmpty();

        $overrideConfigured = (bool) config(
            'citirx.research.allow_unvalidated_forms',
            false,
        );

        $status = $this->status(
            placeholders: $placeholders->count(),
            structurallyValid: $structurallyValid,
            hasPartialValidation: $partialValidation->isNotEmpty(),
            validationComplete: $validationComplete,
            servedCount: $servedQuestions->count(),
            overrideConfigured: $overrideConfigured,
        );

        $errors = array_values(
            array_unique([
                ...$structuralErrors,
                ...$validationErrors,
            ]),
        );

        $expectedVersion =
            $version?->version_code
            ?? 'Missing active version';

        $versionStatusReady = $version !== null
            && in_array(
                $version->status,
                [
                    ResearchFormVersion::STATUS_VALIDATED,
                    ResearchFormVersion::STATUS_PUBLISHED,
                ],
                true,
            );

        $metrics = [
            'Form version' => $expectedVersion,
            'Version binding' => $versionIsBound
                ? 'Pass'
                : 'Fail',
            'Active questions' => "{$activeCount}/60",
            'Fully validated' => "{$fullyValidated}/60",
            'Placeholders' => $placeholders->count(),
            'Positions 1-60' => $positionsComplete
                ? 'Pass'
                : 'Fail',
            'Domains with 10 items' => "{$domainsMatching}/6",
            'Competency blueprint' => "{$competenciesMatching}/"
                .count($competencyRows),
            'Invalid choice structures' => $invalidChoiceStructures->count(),
            'Missing stems/choices' => $missingStemsOrChoices->count(),
            'Duplicate stems' => $duplicateStemGroups->count(),
            'Missing rationales' => $missingRationales->count(),
            'Missing/invalid cognitive levels' => $invalidCognitiveLevels->count(),
            'Partial validation metadata' => $partialValidation->count(),
            'Served questions' => $servedQuestions->count(),
            'Overall status' => $status['label'],
        ];

        return [
            'form' => $form,
            'label' => $definition['label'],
            'expected_version' => $expectedVersion,

            'form_version_id' => $version?->form_version_id,

            'version_number' => $version?->version_number,

            'version_status' => $version?->status ?? 'missing',

            'version_binding' => $versionIsBound
                ? $expectedVersion
                : 'Missing or inconsistent version binding',

            'version_is_bound' => $versionIsBound,

            'active_questions' => $activeCount,
            'required_questions' => ResearchFormService::TOTAL_ITEMS,
            'positions_complete' => $positionsComplete,
            'domains_matching' => $domainsMatching,
            'domain_rows' => $domainRows,
            'competencies_matching' => $competenciesMatching,
            'competency_rows' => $competencyRows,

            'invalid_choice_structures' => $invalidChoiceStructures->count(),
            'missing_stems_or_choices' => $missingStemsOrChoices->count(),
            'duplicate_stem_groups' => $duplicateStemGroups->count(),
            'duplicate_positions' => $duplicateStemGroups->all(),
            'missing_rationales' => $missingRationales->count(),
            'invalid_cognitive_levels' => $invalidCognitiveLevels->count(),
            'placeholders' => $placeholders->count(),

            'fully_validated' => $fullyValidated,
            'partial_validation' => $partialValidation->count(),
            'served_questions' => $servedQuestions->count(),
            'first_served_at' => $firstServedAt,
            'locked_questions' => $lockedQuestions,

            'structurally_valid' => $structurallyValid,
            'validation_complete' => $validationComplete,

            /*
            * This confirms the content and version gate.
            * Official pilot opening still requires the later
            * pilot-control workflow.
            */
            'content_gate_ready' => $versionIsBound
                && $versionStatusReady
                && $version?->is_active === true
                && $structurallyValid
                && $validationComplete
                && ! $overrideConfigured,

            'status' => $status,
            'errors' => $errors,
            'structural_errors' => $structuralErrors,
            'validation_errors' => $validationErrors,
            'console_metrics' => $metrics,
        ];
    }

    /**
     * @return array{key: string, label: string}
     */
    private function status(
        int $placeholders,
        bool $structurallyValid,
        bool $hasPartialValidation,
        bool $validationComplete,
        int $servedCount,
        bool $overrideConfigured,
    ): array {
        if ($placeholders > 0) {
            return [
                'key' => self::STATUS_DEVELOPMENT,
                'label' => 'Development',
            ];
        }

        if (! $structurallyValid || $hasPartialValidation) {
            return [
                'key' => self::STATUS_NEEDS_REVIEW,
                'label' => 'Needs review',
            ];
        }

        if (! $validationComplete) {
            return [
                'key' => self::STATUS_STRUCTURALLY_VALID,
                'label' => 'Structurally valid',
            ];
        }

        if ($servedCount > 0) {
            return [
                'key' => self::STATUS_LOCKED,
                'label' => 'Locked',
            ];
        }

        if ($overrideConfigured) {
            return [
                'key' => self::STATUS_VALIDATED,
                'label' => 'Validated',
            ];
        }

        return [
            'key' => self::STATUS_READY,
            'label' => 'Ready for pilot',
        ];
    }

    private function assertKnownForm(string $form): void
    {
        if (! $this->hasForm($form)) {
            throw new InvalidArgumentException(
                "Unknown research form: {$form}",
            );
        }
    }

    private function normalizeStem(string $stem): string
    {
        $normalized = mb_strtolower(
            trim($stem),
        );

        $normalized = preg_replace(
            '/[^\pL\pN]+/u',
            ' ',
            $normalized,
        ) ?? $normalized;

        return trim(
            preg_replace(
                '/\s+/u',
                ' ',
                $normalized,
            ) ?? $normalized,
        );
    }

    /**
     * @param  Collection<int, Question>  $questions
     */
    private function positions(Collection $questions): string
    {
        return implode(
            ', ',
            $this->positionArray($questions),
        );
    }

    /**
     * @param  Collection<int, Question>  $questions
     * @return array<int, int>
     */
    private function positionArray(
        Collection $questions,
    ): array {
        return $questions
            ->pluck('form_position')
            ->map(
                fn (mixed $position): int => (int) $position,
            )
            ->sort()
            ->values()
            ->all();
    }
}
