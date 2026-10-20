<?php

namespace App\Console\Commands;

use App\Models\Question;
use App\Models\ResearchFormVersion;
use App\Models\User;
use App\Services\ResearchFormVersionTargetService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use SplFileObject;
use Throwable;

class ImportResearchValidation extends Command
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
        'copyright_permission',
        'review_verdict',
    ];

    private const APPROVED_PERMISSION_STATUSES = [
        'permission confirmed',
        'public domain',
        'licensed',
    ];

    protected $signature = 'research:import-validation
        {file : Absolute path to the approved CSV file}
        {--form= : pre_test_a or post_test_b}
        {--form-version= : Exact registered version code}
        {--validator-email= : Email of the approving faculty or administrator}
        {--dry-run : Validate and roll back all database changes}';

    protected $description = 'Import approved faculty validation for a research form';

    public function handle(
        ResearchFormVersionTargetService $targets,
    ): int {
        $transactionStarted = false;

        try {
            $form = trim(
                (string) (
                    $this->option('form')
                    ?? ''
                ),
            );

            $this->assertKnownForm($form);

            $version = $targets->resolve(
                form: $form,
                versionCode: $this->option(
                    'form-version',
                ),
                operation: 'Research validation import',
            );

            $targets->assertValidationImportAllowed(
                $version,
            );

            $validatorEmail = trim(
                (string) (
                    $this->option(
                        'validator-email',
                    )
                    ?? ''
                ),
            );

            if ($validatorEmail === '') {
                throw new RuntimeException(
                    'The --validator-email option is required.',
                );
            }

            $validator = User::query()
                ->where('email', $validatorEmail)
                ->whereIn(
                    'role',
                    [
                        'faculty',
                        'admin',
                    ],
                )
                ->first();

            if (! $validator) {
                throw new RuntimeException(
                    'The validator must be an existing '
                        .'faculty or admin user.',
                );
            }

            $requestedPath = trim(
                (string) $this->argument('file'),
            );

            $path = realpath($requestedPath);

            if (
                $path === false
                || ! is_file($path)
                || ! is_readable($path)
            ) {
                throw new RuntimeException(
                    'CSV file is missing or unreadable: '
                        .$requestedPath,
                );
            }

            $rows = $this->readCsv($path);

            DB::beginTransaction();
            $transactionStarted = true;

            $version = ResearchFormVersion::query()
                ->whereKey(
                    $version->form_version_id,
                )
                ->lockForUpdate()
                ->firstOrFail();

            $targets->assertValidationImportAllowed(
                $version,
            );

            $questions = $this->verifyReviewedContent(
                rows: $rows,
                version: $version,
            );

            $validatedAt = now();

            Question::query()
                ->where(
                    'form_version_id',
                    $version->form_version_id,
                )
                ->whereIn(
                    'question_id',
                    $questions->modelKeys(),
                )
                ->update([
                    'research_validated_at' => $validatedAt,

                    'research_validated_by' => $validator->user_id,
                ]);

            $auditExitCode = $this->call(
                'research:audit-forms',
                [
                    '--form' => $version->research_form,

                    '--form-version' => $version->version_code,

                    '--require-validation' => true,
                ],
            );

            if ($auditExitCode !== self::SUCCESS) {
                throw new RuntimeException(
                    'The selected version failed the '
                        .'final validation audit.',
                );
            }

            if ((bool) $this->option('dry-run')) {
                DB::rollBack();
                $transactionStarted = false;

                $this->info(
                    'Dry-run approval passed for '
                        ."{$version->version_code}; all "
                        .'database changes were rolled back.',
                );

                return self::SUCCESS;
            }

            DB::commit();
            $transactionStarted = false;

            $this->info(
                'Imported faculty validation for '
                    ."{$version->version_code}.",
            );

            $this->warn(
                'Return to the administrator research-form '
                    .'page and confirm the version-level '
                    .'validation transition.',
            );

            return self::SUCCESS;
        } catch (Throwable $exception) {
            if (
                $transactionStarted
                && DB::transactionLevel() > 0
            ) {
                DB::rollBack();
            }

            $this->error(
                $exception->getMessage(),
            );

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
                static function (mixed $value): string {
                    $header = (string) $value;

                    $header = preg_replace(
                        '/^\xEF\xBB\xBF/',
                        '',
                        $header,
                    ) ?? $header;

                    return mb_strtolower(
                        trim(
                            $header,
                            " \t\n\r\0\x0B\"",
                        ),
                    );
                },
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
                'Could not find the CSV header row containing the required approval fields.',
            );
        }

        if (count($headers) !== count(array_unique($headers))) {
            throw new RuntimeException('The CSV contains duplicate header names.');
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

        if (count($rows) !== 60) {
            throw new RuntimeException(
                'The approved CSV must contain exactly 60 data rows; found '
                    .count($rows).'.',
            );
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, string|int>>  $rows
     * @return EloquentCollection<int, Question>
     */
    private function verifyReviewedContent(
        array $rows,
        ResearchFormVersion $version,
    ): EloquentCollection {
        $form = $version->research_form;
        $questions = Question::query()
            ->where(
                'research_form',
                $form,
            )
            ->where(
                'form_version_id',
                $version->form_version_id,
            )
            ->where('is_active', true)
            ->with([
                'choices',
                'competency.domain',
            ])
            ->orderBy('form_position')
            ->lockForUpdate()
            ->get();

        if ($questions->count() !== 60) {
            throw new RuntimeException(
                "{$form} must have exactly 60 active database questions before approval; found {$questions->count()}.",
            );
        }

        $questionsByPosition = $questions->keyBy('form_position');
        $seenPositions = [];
        $errors = [];

        foreach ($rows as $row) {
            $line = (int) $row['_line'];
            $rowForm = (string) $row['research_form'];

            if ($rowForm !== $form) {
                $errors[] = "Line {$line}: expected research_form {$form}; found {$rowForm}.";

                continue;
            }

            $positionValue = (string) $row['form_position'];

            if (! preg_match('/^\d+$/', $positionValue)) {
                $errors[] = "Line {$line}: form_position must be an integer.";

                continue;
            }

            $position = (int) $positionValue;

            if ($position < 1 || $position > 60) {
                $errors[] = "Line {$line}: form_position must be from 1 through 60.";

                continue;
            }

            if (isset($seenPositions[$position])) {
                $errors[] = "Line {$line}: duplicate form_position {$position}.";

                continue;
            }

            $seenPositions[$position] = true;
            $question = $questionsByPosition->get($position);

            if ($question === null) {
                $errors[] = "Line {$line}: no active database question exists at position {$position}.";

                continue;
            }

            $verdict = mb_strtolower((string) $row['review_verdict']);

            if ($verdict !== 'approve') {
                $errors[] = "Position {$position}: review_verdict must be Approve; found {$row['review_verdict']}.";
            }

            $permission = mb_strtolower(
                (string) $row['copyright_permission'],
            );

            if (! in_array(
                $permission,
                self::APPROVED_PERMISSION_STATUSES,
                true,
            )) {
                $errors[] = "Position {$position}: copyright_permission is unresolved.";
            }

            $questionId = trim((string) ($row['question_id'] ?? ''));

            if ($questionId !== '' && $questionId !== $question->question_id) {
                $errors[] = "Position {$position}: question_id does not match the database.";
            }

            if (str_contains(
                mb_strtoupper($question->question_text),
                'DEVELOPMENT MOCK',
            )) {
                $errors[] = "Position {$position}: development placeholder content cannot be approved.";
            }

            $domainNumber = (string) (
                $question->competency?->domain?->domain_number
                ?? ''
            );

            $this->compare(
                errors: $errors,
                position: $position,
                field: 'domain_number',
                csvValue: (string) $row['domain_number'],
                databaseValue: $domainNumber,
            );

            $this->compare(
                errors: $errors,
                position: $position,
                field: 'competency_code',
                csvValue: mb_strtoupper((string) $row['competency_code']),
                databaseValue: (string) (
                    $question->competency?->competency_code
                    ?? ''
                ),
            );

            $this->compare(
                errors: $errors,
                position: $position,
                field: 'question_text',
                csvValue: (string) $row['question_text'],
                databaseValue: trim($question->question_text),
            );

            $this->compare(
                errors: $errors,
                position: $position,
                field: 'rationale',
                csvValue: (string) $row['rationale'],
                databaseValue: trim(
                    (string) $question->hypercorrection_rationale,
                ),
            );

            $this->compare(
                errors: $errors,
                position: $position,
                field: 'cognitive_level',
                csvValue: mb_strtolower(
                    (string) $row['cognitive_level'],
                ),
                databaseValue: (string) $question->cognitive_level,
            );

            $choices = $question->choices->keyBy('choice_letter');

            if ($choices->count() !== 4) {
                $errors[] = "Position {$position}: database must contain exactly four choices.";
            }

            foreach (['A', 'B', 'C', 'D'] as $letter) {
                $choice = $choices->get($letter);

                if ($choice === null) {
                    $errors[] = "Position {$position}: database choice {$letter} is missing.";

                    continue;
                }

                $this->compare(
                    errors: $errors,
                    position: $position,
                    field: 'choice_'.mb_strtolower($letter),
                    csvValue: (string) $row['choice_'.mb_strtolower($letter)],
                    databaseValue: trim($choice->choice_text),
                );
            }

            $correctChoices = $question->choices
                ->where('is_correct', true);
            $databaseCorrectChoice = $correctChoices->count() === 1
                ? (string) $correctChoices->first()->choice_letter
                : '';

            $this->compare(
                errors: $errors,
                position: $position,
                field: 'correct_choice',
                csvValue: mb_strtoupper((string) $row['correct_choice']),
                databaseValue: $databaseCorrectChoice,
            );
        }

        $positions = array_keys($seenPositions);
        sort($positions);

        if ($positions !== range(1, 60)) {
            $errors[] = 'The approved CSV positions must uniquely cover 1 through 60.';
        }

        if ($errors !== []) {
            $preview = array_slice($errors, 0, 20);

            if (count($errors) > count($preview)) {
                $preview[] = '...and '
                    .(count($errors) - count($preview))
                    .' more issue(s).';
            }

            throw new RuntimeException(
                "Approval stopped:\n - ".implode("\n - ", $preview),
            );
        }

        return $questions;
    }

    /**
     * @param  array<int, string>  $errors
     */
    private function compare(
        array &$errors,
        int $position,
        string $field,
        string $csvValue,
        string $databaseValue,
    ): void {
        if ($csvValue !== $databaseValue) {
            $errors[] = "Position {$position}: {$field} does not match the imported database content.";
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
}
