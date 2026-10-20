<?php

namespace App\Console\Commands;

use App\Models\Question;
use App\Models\ResearchFormVersion;
use App\Services\ResearchFormVersionTargetService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ExportResearchFormsForReview extends Command
{
    private const HEADERS = [
        'research_form',
        'question_id',
        'form_position',
        'domain_number',
        'domain_name',
        'competency_code',
        'question_text',
        'choice_a',
        'choice_b',
        'choice_c',
        'choice_d',
        'correct_choice',
        'rationale',
        'cognitive_level',
        'faculty_difficulty_rating',
        'source_reference',
        'copyright_permission',
        'review_verdict',
        'reviewer_notes',
        'reviewer_name',
        'review_date',
    ];

    protected $signature = 'research:export-forms
        {--form= : pre_test_a or post_test_b}
        {--form-version= : Exact registered version code}
        {--out= : Absolute output CSV path}
        {--force : Replace the output file if it already exists}';

    protected $description =
        'Export a research form as a CSV for faculty review';

    public function handle(
        ResearchFormVersionTargetService $targets,
    ): int {
        try {
            $form = trim(
                (string) $this->option('form'),
            );

            $outputPath = trim(
                (string) $this->option('out'),
            );

            $this->assertKnownForm($form);

            $version = $targets->resolve(
                form: $form,
                versionCode: $this->option(
                    'form-version',
                ),
                operation: 'Research-form review export',
            );

            $this->assertOutputPath(
                $outputPath,
            );

            $questions = $this->loadQuestions(
                $version,
            );

            $this->assertExportable(
                $form,
                $questions,
            );

            $this->writeCsv(
                questions: $questions,
                outputPath: $outputPath,
                force: (bool) $this->option(
                    'force',
                ),
            );

            $this->info(
                "Exported {$questions->count()} "
                    .'questions from '
                    ."{$version->version_code}.",
            );

            $this->line(
                "Output: {$outputPath}",
            );

            $this->warn(
                'The exported version is not formally '
                    .'validated until its approved faculty '
                    .'review is imported.',
            );

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error(
                $exception->getMessage(),
            );

            return self::FAILURE;
        }
    }

    /**
     * @return Collection<int, Question>
     */
    private function loadQuestions(
        ResearchFormVersion $version,
    ): Collection {
        return Question::query()
            ->where(
                'research_form',
                $version->research_form,
            )
            ->where(
                'form_version_id',
                $version->form_version_id,
            )
            ->where('is_active', true)
            ->with([
                'choices' => fn ($query) => $query->orderBy(
                    'choice_letter',
                ),

                'competency.domain',
            ])
            ->orderBy('form_position')
            ->get();
    }

    /**
     * @param  Collection<int, Question>  $questions
     */
    private function assertExportable(
        string $form,
        Collection $questions,
    ): void {
        if ($questions->count() !== 60) {
            throw new RuntimeException(
                "{$form} must contain exactly "
                    .'60 active questions; found '
                    .$questions->count().'.'
            );
        }

        $positions = $questions
            ->pluck('form_position')
            ->map(
                fn (mixed $position): int => (int) $position
            )
            ->sort()
            ->values()
            ->all();

        if ($positions !== range(1, 60)) {
            throw new RuntimeException(
                "{$form} positions must uniquely "
                    .'cover 1 through 60.'
            );
        }

        foreach ($questions as $question) {
            $position = (int) (
                $question->form_position
            );

            if (
                $question->competency === null
                || $question->competency->domain
                === null
            ) {
                throw new RuntimeException(
                    "Position {$position} has no "
                        .'competency or domain.'
                );
            }

            $letters = $question
                ->choices
                ->pluck('choice_letter')
                ->map(
                    fn (mixed $letter): string => mb_strtoupper(
                        (string) $letter
                    )
                )
                ->sort()
                ->values()
                ->all();

            if (
                $question->choices->count() !== 4
                || $letters
                !== ['A', 'B', 'C', 'D']
            ) {
                throw new RuntimeException(
                    "Position {$position} must "
                        .'have choices A, B, C, '
                        .'and D exactly once.'
                );
            }

            $correctChoices = $question
                ->choices
                ->where(
                    'is_correct',
                    true,
                );

            if ($correctChoices->count() !== 1) {
                throw new RuntimeException(
                    "Position {$position} must "
                        .'have exactly one '
                        .'correct choice.'
                );
            }

            if (
                trim(
                    (string) $question
                        ->question_text
                ) === ''
            ) {
                throw new RuntimeException(
                    "Position {$position} has "
                        .'an empty question.'
                );
            }

            if (
                trim(
                    (string) $question
                        ->hypercorrection_rationale
                ) === ''
            ) {
                throw new RuntimeException(
                    "Position {$position} has "
                        .'an empty rationale.'
                );
            }

            if (
                ! in_array(
                    $question->cognitive_level,
                    Question::COGNITIVE_LEVELS,
                    true,
                )
            ) {
                throw new RuntimeException(
                    "Position {$position} has "
                        .'an invalid cognitive level.'
                );
            }
        }
    }

    /**
     * @param  Collection<int, Question>  $questions
     */
    private function writeCsv(
        Collection $questions,
        string $outputPath,
        bool $force,
    ): void {
        $directory = dirname(
            $outputPath
        );

        File::ensureDirectoryExists(
            $directory
        );

        if (
            File::exists($outputPath)
            && ! $force
        ) {
            throw new RuntimeException(
                "Output already exists: {$outputPath}. "
                    .'Use --force only if you '
                    .'intend to replace it.'
            );
        }

        $temporaryPath = $outputPath
            .'.tmp-'
            .Str::uuid();

        $handle = null;

        try {
            $handle = fopen(
                $temporaryPath,
                'wb'
            );

            if ($handle === false) {
                throw new RuntimeException(
                    'Could not create temporary '
                        ."CSV file: {$temporaryPath}"
                );
            }

            // UTF-8 BOM improves compatibility
            // when the CSV is opened in Excel.
            if (
                fwrite(
                    $handle,
                    "\xEF\xBB\xBF"
                ) === false
            ) {
                throw new RuntimeException(
                    'Could not write the CSV '
                        .'encoding marker.'
                );
            }

            $this->putCsvRow(
                $handle,
                self::HEADERS,
            );

            foreach ($questions as $question) {
                $choices = $question
                    ->choices
                    ->keyBy('choice_letter');

                $correctChoice = $question
                    ->choices
                    ->firstWhere(
                        'is_correct',
                        true,
                    );

                $this->putCsvRow(
                    $handle,
                    [
                        $question->research_form,

                        $question->question_id,

                        $question->form_position,

                        $question
                            ->competency
                            ->domain
                            ->domain_number,

                        $question
                            ->competency
                            ->domain
                            ->domain_name,

                        $question
                            ->competency
                            ->competency_code,

                        $question->question_text,

                        $choices
                            ->get('A')
                            ->choice_text,

                        $choices
                            ->get('B')
                            ->choice_text,

                        $choices
                            ->get('C')
                            ->choice_text,

                        $choices
                            ->get('D')
                            ->choice_text,

                        $correctChoice
                            ->choice_letter,

                        $question
                            ->hypercorrection_rationale,

                        $question
                            ->cognitive_level,

                        // Faculty supplies this.
                        '',

                        // Add the exact source used.
                        '',

                        'Pending verification',

                        'Pending',

                        '',

                        '',

                        '',
                    ],
                );
            }

            if (! fflush($handle)) {
                throw new RuntimeException(
                    'Could not finish writing '
                        .'the CSV file.'
                );
            }

            fclose($handle);
            $handle = null;

            /*
             * Check again immediately before
             * moving the completed temporary file.
             */
            if (
                File::exists($outputPath)
                && ! $force
            ) {
                throw new RuntimeException(
                    'Output was created while '
                        ."exporting: {$outputPath}. "
                        .'Nothing was overwritten.'
                );
            }

            if (
                File::exists($outputPath)
                && ! File::delete($outputPath)
            ) {
                throw new RuntimeException(
                    'Could not replace existing '
                        ."file: {$outputPath}"
                );
            }

            if (
                ! File::move(
                    $temporaryPath,
                    $outputPath,
                )
            ) {
                throw new RuntimeException(
                    'Could not move the completed '
                        .'CSV to its destination.'
                );
            }
        } catch (Throwable $exception) {
            if (is_resource($handle)) {
                fclose($handle);
            }

            if (File::exists($temporaryPath)) {
                File::delete(
                    $temporaryPath
                );
            }

            throw $exception;
        }
    }

    /**
     * @param  resource  $handle
     * @param  array<int, mixed>  $values
     */
    private function putCsvRow(
        $handle,
        array $values,
    ): void {
        $result = fputcsv(
            $handle,
            $values,
            ',',
            '"',
            '',
        );

        if ($result === false) {
            throw new RuntimeException(
                'Could not write a row to '
                    .'the CSV file.'
            );
        }
    }

    private function assertKnownForm(
        string $form
    ): void {
        if (
            ! in_array(
                $form,
                [
                    Question::FORM_PRE_TEST_A,
                    Question::FORM_POST_TEST_B,
                ],
                true,
            )
        ) {
            throw new RuntimeException(
                'The --form option must be '
                    .'pre_test_a or post_test_b.'
            );
        }
    }

    private function assertOutputPath(
        string $outputPath
    ): void {
        if ($outputPath === '') {
            throw new RuntimeException(
                'The --out option is required.'
            );
        }

        if (
            ! $this->isAbsolutePath(
                $outputPath
            )
        ) {
            throw new RuntimeException(
                'The --out value must be an '
                    .'absolute file path.'
            );
        }

        if (
            mb_strtolower(
                pathinfo(
                    $outputPath,
                    PATHINFO_EXTENSION,
                )
            ) !== 'csv'
        ) {
            throw new RuntimeException(
                'The output filename must use '
                    .'the .csv extension.'
            );
        }
    }

    private function isAbsolutePath(
        string $path
    ): bool {
        return str_starts_with(
            $path,
            '/',
        )
            || preg_match(
                '/^[A-Za-z]:[\\\\\/]/',
                $path,
            ) === 1
            || str_starts_with(
                $path,
                '\\\\',
            )
            || str_starts_with(
                $path,
                '//',
            );
    }
}
