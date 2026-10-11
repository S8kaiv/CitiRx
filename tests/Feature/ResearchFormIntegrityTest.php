<?php

use App\Models\Question;
use Database\Seeders\ResearchQuestionSeeder;
use Database\Seeders\TosSeeder;

beforeEach(function (): void {
    /*
     * The test database uses development research
     * questions until faculty validation is complete.
     */
    config()->set(
        'citirx.research.allow_unvalidated_forms',
        true,
    );

    /*
     * Seed only the data required by this test.
     * RefreshDatabase is already enabled for all
     * Feature tests through tests/Pest.php.
     */
    $this->seed(TosSeeder::class);
    $this->seed(
        ResearchQuestionSeeder::class
    );
});

it(
    'maintains the integrity of both research forms',
    function (): void {
        $forms = [
            Question::FORM_PRE_TEST_A,
            Question::FORM_POST_TEST_B,
        ];

        $questionIdsByForm = [];

        foreach ($forms as $form) {
            /*
             * Load the complete form once and reuse
             * it for the remaining assertions.
             */
            $questions = Question::query()
                ->where(
                    'research_form',
                    $form,
                )
                ->where(
                    'is_active',
                    true,
                )
                ->with([
                    'choices' => fn ($query) => $query->orderBy(
                        'choice_letter'
                    ),

                    'competency.domain',
                ])
                ->orderBy(
                    'form_position'
                )
                ->get();

            /*
             * Requirement 1:
             * Exactly 60 active questions.
             */
            $this->assertCount(
                60,
                $questions,
                "{$form} must contain exactly "
                    .'60 active questions.'
            );

            /*
             * Requirement 2:
             * Positions must uniquely cover 1-60.
             */
            $positions = $questions
                ->pluck('form_position')
                ->map(
                    fn (mixed $position): int => (int) $position
                )
                ->sort()
                ->values()
                ->all();

            $this->assertSame(
                range(1, 60),
                $positions,
                "{$form} positions must "
                    .'uniquely cover 1 through 60.'
            );

            /*
             * Requirement 3:
             * Ten questions in each of six domains.
             */
            $domainCounts = Question::query()
                ->where(
                    'questions.research_form',
                    $form,
                )
                ->where(
                    'questions.is_active',
                    true,
                )
                ->join(
                    'tos_competencies',
                    'tos_competencies.competency_id',
                    '=',
                    'questions.competency_id',
                )
                ->join(
                    'tos_domains',
                    'tos_domains.domain_id',
                    '=',
                    'tos_competencies.domain_id',
                )
                ->selectRaw(
                    'tos_domains.domain_number, '
                        .'COUNT(*) AS item_count'
                )
                ->groupBy(
                    'tos_domains.domain_number'
                )
                ->orderBy(
                    'tos_domains.domain_number'
                )
                ->pluck(
                    'item_count',
                    'domain_number',
                )
                ->mapWithKeys(
                    fn (
                        mixed $count,
                        mixed $domainNumber,
                    ): array => [
                        (int) $domainNumber => (int) $count,
                    ]
                )
                ->all();

            $this->assertSame(
                [
                    1 => 10,
                    2 => 10,
                    3 => 10,
                    4 => 10,
                    5 => 10,
                    6 => 10,
                ],
                $domainCounts,
                "{$form} must contain exactly "
                    .'10 questions per domain.'
            );

            foreach ($questions as $question) {
                $position = (int) (
                    $question->form_position
                );

                /*
                 * Every question must be connected
                 * to a competency and domain.
                 */
                $this->assertNotNull(
                    $question->competency,
                    "{$form} position {$position} "
                        .'has no competency.'
                );

                $this->assertNotNull(
                    $question->competency?->domain,
                    "{$form} position {$position} "
                        .'has no domain.'
                );

                /*
                 * Requirements 5 and 6:
                 * Four choices, A-D, with exactly
                 * one correct answer.
                 */
                $this->assertCount(
                    4,
                    $question->choices,
                    "{$form} position {$position} "
                        .'must have four choices.'
                );

                $choiceLetters = $question
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

                $this->assertSame(
                    ['A', 'B', 'C', 'D'],
                    $choiceLetters,
                    "{$form} position {$position} "
                        .'must contain choices '
                        .'A, B, C, and D exactly once.'
                );

                $correctChoiceCount = $question
                    ->choices
                    ->where(
                        'is_correct',
                        true,
                    )
                    ->count();

                $this->assertSame(
                    1,
                    $correctChoiceCount,
                    "{$form} position {$position} "
                        .'must have exactly one '
                        .'correct choice.'
                );

                /*
                 * Ensure no choice contains an
                 * empty value.
                 */
                foreach (
                    $question->choices as $choice
                ) {
                    $this->assertNotSame(
                        '',
                        trim(
                            (string) $choice
                                ->choice_text
                        ),
                        "{$form} position "
                            ."{$position} choice "
                            ."{$choice->choice_letter} "
                            .'is empty.'
                    );
                }

                /*
                 * Requirement 7:
                 * Every question must have a
                 * non-empty rationale.
                 */
                $this->assertNotSame(
                    '',
                    trim(
                        (string) $question
                            ->hypercorrection_rationale
                    ),
                    "{$form} position {$position} "
                        .'has no rationale.'
                );

                /*
                 * Requirement 8:
                 * Cognitive level must use a value
                 * supported by the Question model.
                 */
                $this->assertContains(
                    $question->cognitive_level,
                    Question::COGNITIVE_LEVELS,
                    "{$form} position {$position} "
                        .'has an invalid '
                        .'cognitive level.'
                );

                /*
                 * Requirement 10:
                 * Validation metadata must be
                 * complete as a pair.
                 *
                 * Both may be null during local
                 * development. It is invalid for
                 * only one field to be present.
                 */
                $hasValidationDate =
                    $question
                        ->research_validated_at
                    !== null;

                $hasValidator =
                    $question
                        ->research_validated_by
                    !== null;

                $this->assertSame(
                    $hasValidationDate,
                    $hasValidator,
                    "{$form} position {$position} "
                        .'has incomplete validation '
                        .'metadata.'
                );
            }

            /*
             * Requirement 9:
             * Detect duplicate normalized stems.
             */
            $duplicateStems = $questions
                ->groupBy(
                    function (
                        Question $question
                    ): string {
                        $stem = mb_strtolower(
                            trim(
                                $question
                                    ->question_text
                            )
                        );

                        $stem = preg_replace(
                            '/[^\pL\pN]+/u',
                            ' ',
                            $stem,
                        ) ?? $stem;

                        return trim(
                            preg_replace(
                                '/\s+/u',
                                ' ',
                                $stem,
                            ) ?? $stem
                        );
                    }
                )
                ->filter(
                    fn ($group): bool => $group->count() > 1
                );

            $duplicatePositions =
                $duplicateStems
                    ->map(
                        fn ($group): string => $group
                            ->pluck(
                                'form_position'
                            )
                            ->sort()
                            ->implode(', ')
                    )
                    ->values()
                    ->implode('; ');

            $this->assertTrue(
                $duplicateStems->isEmpty(),
                "{$form} contains duplicate "
                    .'question stems at positions: '
                    .$duplicatePositions
            );

            $questionIdsByForm[$form] =
                $questions->pluck(
                    'question_id'
                );
        }

        /*
         * Requirement 4:
         * Form A and Form B must not share IDs.
         */
        $overlappingIds =
            $questionIdsByForm[
                Question::FORM_PRE_TEST_A
            ]
                ->intersect(
                    $questionIdsByForm[
                        Question::FORM_POST_TEST_B
                    ]
                )
                ->values();

        $this->assertCount(
            0,
            $overlappingIds,
            'Form A and Form B must not '
                .'share question IDs.'
        );
    }
);
