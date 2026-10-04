<?php

namespace App\Console\Commands;

use App\Models\Question;
use App\Models\QuestionChoice;
use App\Models\TosCompetency;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use SplFileObject;
use Throwable;

class ImportResearchFormContent extends Command
{
    private const REQUIRED_HEADERS = [
        'research_form',
        'form_position',
        'domain_number',
        'competency_code',
        'question_text',
        'choice_a',
        'choice_b',
        'choice_c',
        'choice_d',
        'correct_choice',
        'rationale',
        'cognitive_level',
    ];

    protected $signature = 'research:import-form-content
        {file : Absolute path to the reviewed CSV file}
        {--form= : pre_test_a or post_test_b}
        {--dry-run : Validate and roll back all database changes}';

    protected $description = 'Import reviewed CSV content for research pre/post test forms';

    public function handle(): int
    {
        $transactionStarted = false;

        try {
            $form = trim((string) ($this->option('form') ?? ''));
            $this->assertKnownForm($form);

            $requestedPath = trim((string) $this->argument('file'));
            $path = realpath($requestedPath);

            if ($path === false || ! is_file($path) || ! is_readable($path)) {
                throw new RuntimeException(
                    "CSV file is missing or unreadable: {$requestedPath}",
                );
            }

            $rows = $this->readCsv($path);
            $rows = $this->validateRows($rows, $form);

            DB::beginTransaction();
            $transactionStarted = true;

            $this->persistRows($rows, $form);

            $auditExitCode = $this->call('research:audit-forms', [
                '--form' => $form,
            ]);

            if ($auditExitCode !== self::SUCCESS) {
                throw new RuntimeException(
                    'The imported form failed the structural audit.',
                );
            }

            if ((bool) $this->option('dry-run')) {
                DB::rollBack();
                $transactionStarted = false;

                $this->info(
                    "Dry run passed for {$form}; all database changes were rolled back.",
                );

                return self::SUCCESS;
            }

            DB::commit();
            $transactionStarted = false;

            $this->info(
                "Imported 60 unvalidated questions into {$form}.",
            );
            $this->warn(
                'The form remains unavailable for formal research until it is approved and fully validated.',
            );

            return self::SUCCESS;
        } catch (Throwable $exception) {
            if ($transactionStarted && DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * @return array<int, array<string, string|int>>
     */
    private function readCsv(string $path): array
    {
        $csv = new SplFileObject($path, 'r');
        $csv->setCsvControl(',', '"', '');
        $csv->setFlags(
            SplFileObject::READ_CSV
                | SplFileObject::DROP_NEW_LINE,
        );

        $headers = null;
        $lineNumber = 0;

        while (! $csv->eof()) {
            $candidate = $csv->fgetcsv();
            $lineNumber++;

            if (! is_array($candidate) || $candidate === [null]) {
                continue;
            }

            $candidateHeaders = array_map(
                fn (mixed $value): string => mb_strtolower(
                    trim((string) $value),
                ),
                $candidate,
            );

            $candidateHeaders[0] = preg_replace(
                '/^\xEF\xBB\xBF/',
                '',
                $candidateHeaders[0],
            ) ?? $candidateHeaders[0];

            if (array_diff(self::REQUIRED_HEADERS, $candidateHeaders) === []) {
                $headers = $candidateHeaders;
                break;
            }
        }

        if ($headers === null) {
            throw new RuntimeException(
                'Could not find the CSV header row containing the required research fields.',
            );
        }

        if (count($headers) !== count(array_unique($headers))) {
            throw new RuntimeException('The CSV contains duplicate header names.');
        }

        $missingHeaders = array_values(array_diff(
            self::REQUIRED_HEADERS,
            $headers,
        ));

        if ($missingHeaders !== []) {
            throw new RuntimeException(
                'Missing required CSV headers: '.implode(', ', $missingHeaders),
            );
        }

        $rows = [];

        while (! $csv->eof()) {
            $values = $csv->fgetcsv();
            $lineNumber++;

            if (
                $values === false
                || $values === [null]
                || collect($values)->every(
                    fn (mixed $value): bool => trim((string) $value) === '',
                )
            ) {
                continue;
            }

            if (count($values) !== count($headers)) {
                throw new RuntimeException(
                    "CSV line {$lineNumber} has ".count($values)
                        .' columns; expected '.count($headers).'.',
                );
            }

            $row = array_combine($headers, $values);

            if ($row === false) {
                throw new RuntimeException(
                    "Could not parse CSV line {$lineNumber}.",
                );
            }

            $rows[] = [
                ...array_map(
                    fn (mixed $value): string => trim((string) $value),
                    $row,
                ),
                '_line' => $lineNumber,
            ];
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, string|int>>  $rows
     * @return array<int, array<string, string|int>>
     */
    private function validateRows(array $rows, string $form): array
    {
        if (count($rows) !== 60) {
            throw new RuntimeException(
                'The CSV must contain exactly 60 data rows; found '
                    .count($rows).'.',
            );
        }

        $codes = array_values(array_unique(array_map(
            fn (array $row): string => mb_strtoupper(
                (string) $row['competency_code'],
            ),
            $rows,
        )));

        $competencies = TosCompetency::query()
            ->with('domain')
            ->whereIn('competency_code', $codes)
            ->get()
            ->keyBy('competency_code');

        $questionIds = array_values(array_unique(array_filter(array_map(
            fn (array $row): string => trim(
                (string) ($row['question_id'] ?? ''),
            ),
            $rows,
        ))));

        $knownQuestions = Question::query()
            ->whereIn('question_id', $questionIds)
            ->get()
            ->keyBy('question_id');

        $positions = [];
        $normalizedStems = [];
        $validated = [];

        foreach ($rows as $row) {
            $line = (int) $row['_line'];
            $rowForm = (string) $row['research_form'];

            if ($rowForm !== $form) {
                throw new RuntimeException(
                    "CSV line {$line} uses {$rowForm}; expected {$form}.",
                );
            }

            $positionValue = (string) $row['form_position'];

            if (! preg_match('/^\d+$/', $positionValue)) {
                throw new RuntimeException(
                    "CSV line {$line} has a non-integer form_position.",
                );
            }

            $position = (int) $positionValue;

            if ($position < 1 || $position > 60) {
                throw new RuntimeException(
                    "CSV line {$line} has form_position {$position}; expected 1-60.",
                );
            }

            if (isset($positions[$position])) {
                throw new RuntimeException(
                    "Duplicate form_position {$position} appears on CSV lines "
                        ."{$positions[$position]} and {$line}.",
                );
            }

            $positions[$position] = $line;

            $domainValue = (string) $row['domain_number'];

            if (! preg_match('/^[1-6]$/', $domainValue)) {
                throw new RuntimeException(
                    "CSV line {$line} has an invalid domain_number.",
                );
            }

            $domainNumber = (int) $domainValue;
            $competencyCode = mb_strtoupper(
                (string) $row['competency_code'],
            );
            $competency = $competencies->get($competencyCode);

            if ($competency === null) {
                throw new RuntimeException(
                    "CSV line {$line} uses unknown competency {$competencyCode}.",
                );
            }

            if (
                $competency->domain === null
                || (int) $competency->domain->domain_number !== $domainNumber
            ) {
                throw new RuntimeException(
                    "CSV line {$line}: {$competencyCode} does not belong to Domain {$domainNumber}.",
                );
            }

            $questionText = (string) $row['question_text'];
            $rationale = (string) $row['rationale'];
            $choices = [
                'A' => (string) $row['choice_a'],
                'B' => (string) $row['choice_b'],
                'C' => (string) $row['choice_c'],
                'D' => (string) $row['choice_d'],
            ];

            if ($questionText === '') {
                throw new RuntimeException(
                    "CSV line {$line} has an empty question_text.",
                );
            }

            foreach ($choices as $letter => $choiceText) {
                if ($choiceText === '') {
                    throw new RuntimeException(
                        "CSV line {$line} has an empty choice_"
                            .mb_strtolower($letter).'.',
                    );
                }
            }

            if ($rationale === '') {
                throw new RuntimeException(
                    "CSV line {$line} has an empty rationale.",
                );
            }

            if (str_contains(
                mb_strtoupper($questionText),
                'DEVELOPMENT MOCK',
            )) {
                throw new RuntimeException(
                    "CSV line {$line} still contains placeholder text.",
                );
            }

            $correctChoice = mb_strtoupper(
                (string) $row['correct_choice'],
            );

            if (! in_array($correctChoice, ['A', 'B', 'C', 'D'], true)) {
                throw new RuntimeException(
                    "CSV line {$line} has invalid correct_choice {$correctChoice}.",
                );
            }

            $cognitiveLevel = mb_strtolower(
                (string) $row['cognitive_level'],
            );

            if (! in_array(
                $cognitiveLevel,
                Question::COGNITIVE_LEVELS,
                true,
            )) {
                throw new RuntimeException(
                    "CSV line {$line} has invalid cognitive_level {$cognitiveLevel}.",
                );
            }

            $normalizedStem = $this->normalizeStem($questionText);

            if (isset($normalizedStems[$normalizedStem])) {
                throw new RuntimeException(
                    "CSV lines {$normalizedStems[$normalizedStem]} and {$line} contain duplicate question stems.",
                );
            }

            $normalizedStems[$normalizedStem] = $line;
            $questionId = trim((string) ($row['question_id'] ?? ''));

            if ($questionId !== '') {
                $knownQuestion = $knownQuestions->get($questionId);

                if ($knownQuestion === null) {
                    throw new RuntimeException(
                        "CSV line {$line} uses unknown question_id {$questionId}.",
                    );
                }

                if (
                    $knownQuestion->research_form !== $form
                    || (int) $knownQuestion->form_position !== $position
                ) {
                    throw new RuntimeException(
                        "CSV line {$line}: question_id {$questionId} does not match {$form} position {$position}.",
                    );
                }
            }

            $validated[] = [
                'question_id' => $questionId,
                'form_position' => $position,
                'competency_id' => $competency->competency_id,
                'question_text' => $questionText,
                'rationale' => $rationale,
                'cognitive_level' => $cognitiveLevel,
                'correct_choice' => $correctChoice,
                'choices' => $choices,
            ];
        }

        ksort($positions);

        if (array_keys($positions) !== range(1, 60)) {
            throw new RuntimeException(
                'CSV positions must uniquely cover 1 through 60.',
            );
        }

        usort(
            $validated,
            fn (array $left, array $right): int => $left['form_position'] <=> $right['form_position'],
        );

        return $validated;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function persistRows(
        array $rows,
        string $form,
    ): void {
        $this->assertRowsHaveNoResearchSessionHistory(
            $rows,
            $form,
        );

        foreach ($rows as $row) {
            $question = $row['question_id'] !== ''
                ? Question::query()->findOrFail(
                    $row['question_id'],
                )
                : Question::query()->firstOrNew([
                    'research_form' => $form,
                    'form_position' => $row['form_position'],
                ]);

            $question->fill([
                'competency_id' => $row['competency_id'],

                'question_text' => $row['question_text'],

                'hypercorrection_rationale' => $row['rationale'],

                'is_diagnostic_pool' => $form
                    === Question::FORM_PRE_TEST_A,

                'difficulty_index_p' => null,
                'speed_flag_count' => 0,
                'is_active' => true,
                'research_form' => $form,

                'form_position' => $row['form_position'],

                'cognitive_level' => $row['cognitive_level'],

                'research_validated_at' => null,
                'research_validated_by' => null,
            ]);

            $question->save();

            foreach (
                $row['choices'] as $letter => $choiceText
            ) {
                QuestionChoice::query()
                    ->updateOrCreate(
                        [
                            'question_id' => $question->question_id,

                            'choice_letter' => $letter,
                        ],
                        [
                            'choice_text' => $choiceText,

                            'is_correct' => $letter
                                === $row['correct_choice'],

                            'distractor_fallacy_note' => null,
                        ],
                    );
            }
        }
    }

    private function assertKnownForm(string $form): void
    {
        if (! in_array($form, [
            Question::FORM_PRE_TEST_A,
            Question::FORM_POST_TEST_B,
        ], true)) {
            throw new RuntimeException(
                'The --form option must be pre_test_a or post_test_b.',
            );
        }
    }

    private function normalizeStem(string $stem): string
    {
        $normalized = mb_strtolower(trim($stem));
        $normalized = preg_replace('/[^\pL\pN]+/u', ' ', $normalized)
            ?? $normalized;

        return trim(preg_replace('/\s+/u', ' ', $normalized) ?? $normalized);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function assertRowsHaveNoResearchSessionHistory(
        array $rows,
        string $form,
    ): void {
        $rowsCollection = collect($rows);

        $positions = $rowsCollection
            ->pluck('form_position')
            ->map(
                fn (mixed $position): int => (int) $position,
            )
            ->unique()
            ->values();

        /*
     * Include explicit IDs from the CSV and existing questions
     * found by form position. This also protects imports whose CSV
     * leaves question_id blank.
     */
        $questionIds = $rowsCollection
            ->pluck('question_id')
            ->map(
                fn (mixed $id): string => trim((string) $id),
            )
            ->filter()
            ->merge(
                Question::query()
                    ->where('research_form', $form)
                    ->whereIn(
                        'form_position',
                        $positions->all(),
                    )
                    ->pluck('question_id'),
            )
            ->unique()
            ->values();

        if ($questionIds->isEmpty()) {
            return;
        }

        /*
     * One assessment-session history query for the entire form,
     * instead of one query for each question.
     */
        $hasBeenServed = DB::table(
            'assessment_sessions',
        )
            ->whereIn('research_phase', [
                'pre_test',
                'post_test',
            ])
            ->where(function ($query) use (
                $questionIds,
            ): void {
                foreach (
                    $questionIds as $index => $questionId
                ) {
                    if ($index === 0) {
                        $query->whereJsonContains(
                            'served_question_ids',
                            $questionId,
                        );

                        continue;
                    }

                    $query->orWhereJsonContains(
                        'served_question_ids',
                        $questionId,
                    );
                }
            })
            ->exists();

        if ($hasBeenServed) {
            throw new RuntimeException(
                'One or more questions have already been served '
                    .'in a research assessment. Create a new '
                    .'question/form version instead of changing '
                    .'historical research content.',
            );
        }
    }
}
