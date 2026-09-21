<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\QuestionChoice;
use App\Models\TosCompetency;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ResearchQuestionSeeder extends Seeder
{
    private const QUESTIONS_PER_SUBJECT = 10;

    private const QUESTIONS_PER_FORM = 60;

    /**
     * Development-only scaled TOS quotas.
     *
     * These create structurally equivalent Form A and Form B pools.
     * They are not substitutes for faculty-authored, CVI-validated items.
     */
    private const COMPONENT_QUOTAS = [
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

    /**
     * Suggested 10-item cognitive-level scaling per subject.
     * Each list must contain exactly ten entries.
     */
    private const COGNITIVE_LEVELS = [
        1 => [
            Question::COGNITIVE_KNOWLEDGE,
            Question::COGNITIVE_KNOWLEDGE,
            Question::COGNITIVE_KNOWLEDGE,
            Question::COGNITIVE_COMPREHENSION,
            Question::COGNITIVE_COMPREHENSION,
            Question::COGNITIVE_COMPREHENSION,
            Question::COGNITIVE_COMPREHENSION,
            Question::COGNITIVE_APPLICATION,
            Question::COGNITIVE_ANALYSIS,
            Question::COGNITIVE_EVALUATION,
        ],

        2 => [
            Question::COGNITIVE_KNOWLEDGE,
            Question::COGNITIVE_KNOWLEDGE,
            Question::COGNITIVE_KNOWLEDGE,
            Question::COGNITIVE_COMPREHENSION,
            Question::COGNITIVE_COMPREHENSION,
            Question::COGNITIVE_COMPREHENSION,
            Question::COGNITIVE_APPLICATION,
            Question::COGNITIVE_APPLICATION,
            Question::COGNITIVE_ANALYSIS,
            Question::COGNITIVE_EVALUATION,
        ],

        3 => [
            Question::COGNITIVE_KNOWLEDGE,
            Question::COGNITIVE_KNOWLEDGE,
            Question::COGNITIVE_COMPREHENSION,
            Question::COGNITIVE_COMPREHENSION,
            Question::COGNITIVE_APPLICATION,
            Question::COGNITIVE_APPLICATION,
            Question::COGNITIVE_APPLICATION,
            Question::COGNITIVE_APPLICATION,
            Question::COGNITIVE_ANALYSIS,
            Question::COGNITIVE_SYNTHESIS,
        ],

        4 => [
            Question::COGNITIVE_KNOWLEDGE,
            Question::COGNITIVE_KNOWLEDGE,
            Question::COGNITIVE_COMPREHENSION,
            Question::COGNITIVE_COMPREHENSION,
            Question::COGNITIVE_APPLICATION,
            Question::COGNITIVE_APPLICATION,
            Question::COGNITIVE_APPLICATION,
            Question::COGNITIVE_APPLICATION,
            Question::COGNITIVE_ANALYSIS,
            Question::COGNITIVE_SYNTHESIS,
        ],

        5 => [
            Question::COGNITIVE_KNOWLEDGE,
            Question::COGNITIVE_KNOWLEDGE,
            Question::COGNITIVE_COMPREHENSION,
            Question::COGNITIVE_COMPREHENSION,
            Question::COGNITIVE_APPLICATION,
            Question::COGNITIVE_APPLICATION,
            Question::COGNITIVE_APPLICATION,
            Question::COGNITIVE_APPLICATION,
            Question::COGNITIVE_ANALYSIS,
            Question::COGNITIVE_SYNTHESIS,
        ],

        6 => [
            Question::COGNITIVE_KNOWLEDGE,
            Question::COGNITIVE_COMPREHENSION,
            Question::COGNITIVE_COMPREHENSION,
            Question::COGNITIVE_COMPREHENSION,
            Question::COGNITIVE_APPLICATION,
            Question::COGNITIVE_APPLICATION,
            Question::COGNITIVE_APPLICATION,
            Question::COGNITIVE_ANALYSIS,
            Question::COGNITIVE_ANALYSIS,
            Question::COGNITIVE_EVALUATION,
        ],
    ];

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException(
                'Development mock research questions must never be seeded in production.'
            );
        }

        $this->validateBlueprint();

        $requiredCodes = $this->requiredCompetencyCodes();

        $competencies = TosCompetency::query()
            ->with('domain')
            ->whereIn('competency_code', $requiredCodes)
            ->get()
            ->keyBy('competency_code');

        $missingCodes = array_values(array_diff(
            $requiredCodes,
            $competencies->keys()->all(),
        ));

        if ($missingCodes !== []) {
            throw new RuntimeException(
                'Missing TOS competencies: '.implode(', ', $missingCodes)
            );
        }

        DB::transaction(function () use ($competencies): void {
            foreach (self::COMPONENT_QUOTAS as $form => $subjects) {
                $formPosition = 1;

                foreach ($subjects as $subjectNumber => $componentQuotas) {
                    $subjectItemIndex = 0;
                    $cognitiveLevels = self::COGNITIVE_LEVELS[$subjectNumber];

                    // Offset Form B labels while preserving identical counts.
                    if ($form === Question::FORM_POST_TEST_B) {
                        $cognitiveLevels = [
                            ...array_slice($cognitiveLevels, 3),
                            ...array_slice($cognitiveLevels, 0, 3),
                        ];
                    }

                    foreach ($componentQuotas as $code => $quantity) {
                        $competency = $competencies->get($code);

                        for ($componentItem = 1; $componentItem <= $quantity; $componentItem++) {
                            $cognitiveLevel = $cognitiveLevels[$subjectItemIndex];

                            $this->seedMockQuestion(
                                competency: $competency,
                                form: $form,
                                formPosition: $formPosition,
                                subjectNumber: $subjectNumber,
                                componentItem: $componentItem,
                                cognitiveLevel: $cognitiveLevel,
                            );

                            $subjectItemIndex++;
                            $formPosition++;
                        }
                    }
                }

                $this->assertSeededForm($form);
            }
        });

        $this->command?->info(
            'Research mock seeder: 60 Form A + 60 Form B development-only questions.'
        );
    }

    private function seedMockQuestion(
        TosCompetency $competency,
        string $form,
        int $formPosition,
        int $subjectNumber,
        int $componentItem,
        string $cognitiveLevel,
    ): void {
        $formLabel = $form === Question::FORM_PRE_TEST_A
            ? 'Form A'
            : 'Form B';

        $positionLabel = str_pad(
            (string) $formPosition,
            2,
            '0',
            STR_PAD_LEFT,
        );

        $questionText = sprintf(
            '[DEVELOPMENT MOCK - NOT VALIDATED] %s item %s. Subject %d: %s; component: %s; component item: %d; cognitive tag: %s. Which option is explicitly marked as the correct mock response?',
            $formLabel,
            $positionLabel,
            $subjectNumber,
            $competency->domain->domain_name,
            $competency->title,
            $componentItem,
            $cognitiveLevel,
        );

        $question = Question::updateOrCreate(
            [
                'research_form' => $form,
                'form_position' => $formPosition,
            ],
            [
                'competency_id' => $competency->competency_id,
                'question_text' => $questionText,
                'hypercorrection_rationale' => 'Development-only placeholder rationale. Replace this entire item after Pharmacy faculty review and content validation.',
                'is_diagnostic_pool' => $form === Question::FORM_PRE_TEST_A,
                'cognitive_level' => $cognitiveLevel,
                'difficulty_index_p' => null,
                'speed_flag_count' => 0,
                'is_active' => true,
            ]
        );

        $letters = ['A', 'B', 'C', 'D'];
        $correctLetter = $letters[($formPosition - 1) % count($letters)];

        foreach ($letters as $letter) {
            $isCorrect = $letter === $correctLetter;

            QuestionChoice::updateOrCreate(
                [
                    'question_id' => $question->question_id,
                    'choice_letter' => $letter,
                ],
                [
                    'choice_text' => $isCorrect
                        ? "Mock option {$letter} - designated correct response"
                        : "Mock option {$letter} - development distractor",
                    'is_correct' => $isCorrect,
                    'distractor_fallacy_note' => $isCorrect
                        ? null
                        : 'Development-only distractor; not a clinically validated misconception.',
                ]
            );
        }
    }

    private function validateBlueprint(): void
    {
        foreach (self::COMPONENT_QUOTAS as $form => $subjects) {
            if (count($subjects) !== 6) {
                throw new RuntimeException("{$form} must contain six subjects.");
            }

            $formTotal = 0;

            foreach ($subjects as $subjectNumber => $quotas) {
                $subjectTotal = array_sum($quotas);

                if ($subjectTotal !== self::QUESTIONS_PER_SUBJECT) {
                    throw new RuntimeException(
                        "{$form} subject {$subjectNumber} must contain exactly 10 questions."
                    );
                }

                if (
                    ! isset(self::COGNITIVE_LEVELS[$subjectNumber])
                    || count(self::COGNITIVE_LEVELS[$subjectNumber])
                        !== self::QUESTIONS_PER_SUBJECT
                ) {
                    throw new RuntimeException(
                        "Subject {$subjectNumber} must contain exactly 10 cognitive labels."
                    );
                }

                $formTotal += $subjectTotal;
            }

            if ($formTotal !== self::QUESTIONS_PER_FORM) {
                throw new RuntimeException("{$form} must contain exactly 60 questions.");
            }
        }
    }

    /** @return array<int, string> */
    private function requiredCompetencyCodes(): array
    {
        $codes = [];

        foreach (self::COMPONENT_QUOTAS as $subjects) {
            foreach ($subjects as $quotas) {
                $codes = [
                    ...$codes,
                    ...array_keys($quotas),
                ];
            }
        }

        $codes = array_values(array_unique($codes));
        sort($codes);

        return $codes;
    }

    private function assertSeededForm(string $form): void
    {
        $questions = Question::query()
            ->where('research_form', $form)
            ->withCount([
                'choices',
                'choices as correct_choices_count' => fn ($query) => $query->where('is_correct', true),
            ])
            ->orderBy('form_position')
            ->get();

        if ($questions->count() !== self::QUESTIONS_PER_FORM) {
            throw new RuntimeException(
                "{$form} must contain exactly 60 seeded questions."
            );
        }

        if (
            $questions->pluck('form_position')->all()
            !== range(1, self::QUESTIONS_PER_FORM)
        ) {
            throw new RuntimeException(
                "{$form} positions must cover 1 through 60 exactly once."
            );
        }

        $invalidChoiceCount = $questions
            ->filter(function (Question $question): bool {
                return $question->choices_count !== 4
                    || $question->correct_choices_count !== 1;
            })
            ->count();

        if ($invalidChoiceCount > 0) {
            throw new RuntimeException(
                "{$form} contains questions with an invalid answer-key structure."
            );
        }
    }
}
