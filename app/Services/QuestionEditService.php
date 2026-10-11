<?php

namespace App\Services;

use App\Models\Question;
use App\Models\QuestionChoice;
use App\Models\QuestionEdit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

class QuestionEditService
{
    private const CHOICE_LETTERS = [
        'A',
        'B',
        'C',
        'D',
    ];

    public function __construct(
        protected QuestionLockService $lock,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateContent(
        User $editor,
        Question $question,
        array $payload,
        int $expectedVersion,
        string $reason,
    ): Question {
        $this->assertAdministrator(
            $editor,
        );

        $reason = $this->validatedReason(
            $reason,
        );

        return DB::transaction(
            function () use (
                $editor,
                $question,
                $payload,
                $expectedVersion,
                $reason,
            ): Question {
                $question = Question::query()
                    ->whereKey(
                        $question->question_id,
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->assertVersion(
                    $question,
                    $expectedVersion,
                );

                if (
                    $this->lock->isLocked(
                        $question,
                    )
                ) {
                    throw new LogicException(
                        'This question is locked: '
                            .$this->lock->lockReason(
                                $question,
                            ),
                    );
                }

                $choices = QuestionChoice::query()
                    ->where(
                        'question_id',
                        $question->question_id,
                    )
                    ->orderBy(
                        'choice_letter',
                    )
                    ->lockForUpdate()
                    ->get()
                    ->keyBy(
                        'choice_letter',
                    );

                $this->assertExistingChoiceStructure(
                    $choices->keys()
                        ->all(),
                    $choices
                        ->filter(
                            fn (
                                QuestionChoice $choice,
                            ): bool => $choice
                                ->is_correct,
                        )
                        ->count(),
                );

                $newQuestionText = trim(
                    (string) (
                        $payload[
                            'question_text'
                        ] ?? ''
                    ),
                );

                if ($newQuestionText === '') {
                    throw new InvalidArgumentException(
                        'Question text cannot be empty.',
                    );
                }

                $newRationale =
                    $this->nullableText(
                        $payload[
                            'hypercorrection_rationale'
                        ] ?? null,
                    );

                $newCognitiveLevel =
                    $this->nullableText(
                        $payload[
                            'cognitive_level'
                        ] ?? null,
                    );

                if (
                    $newCognitiveLevel !== null
                    && ! in_array(
                        $newCognitiveLevel,
                        Question::COGNITIVE_LEVELS,
                        true,
                    )
                ) {
                    throw new InvalidArgumentException(
                        'The cognitive level is invalid.',
                    );
                }

                /*
                 * Research-form content must retain a rationale
                 * and cognitive classification.
                 */
                if (
                    $question->research_form !== null
                    && $newRationale === null
                ) {
                    throw new InvalidArgumentException(
                        'Research questions require a rationale.',
                    );
                }

                if (
                    $question->research_form !== null
                    && $newCognitiveLevel === null
                ) {
                    throw new InvalidArgumentException(
                        'Research questions require a cognitive level.',
                    );
                }

                $newChoiceTexts =
                    $this->validatedChoiceTexts(
                        $payload[
                            'choices'
                        ] ?? null,
                    );

                $newCorrectLetter = strtoupper(
                    trim(
                        (string) (
                            $payload[
                                'correct_choice'
                            ] ?? ''
                        ),
                    ),
                );

                if (
                    ! in_array(
                        $newCorrectLetter,
                        self::CHOICE_LETTERS,
                        true,
                    )
                ) {
                    throw new InvalidArgumentException(
                        'The correct choice must be A, B, C, or D.',
                    );
                }

                $oldCorrectLetter = $choices
                    ->first(
                        fn (
                            QuestionChoice $choice,
                        ): bool => $choice
                            ->is_correct,
                    )
                    ?->choice_letter;

                if ($oldCorrectLetter === null) {
                    throw new LogicException(
                        'The stored question does not have '
                            .'exactly one correct choice.',
                    );
                }

                $changes = [];

                $this->addChange(
                    $changes,
                    'question_text',
                    $question->question_text,
                    $newQuestionText,
                );

                $this->addChange(
                    $changes,
                    'hypercorrection_rationale',
                    $question
                        ->hypercorrection_rationale,
                    $newRationale,
                );

                $this->addChange(
                    $changes,
                    'cognitive_level',
                    $question->cognitive_level,
                    $newCognitiveLevel,
                );

                foreach (
                    self::CHOICE_LETTERS as $letter
                ) {
                    /** @var QuestionChoice $choice */
                    $choice = $choices->get(
                        $letter,
                    );

                    $this->addChange(
                        $changes,
                        "choice_{$letter}_text",
                        $choice->choice_text,
                        $newChoiceTexts[
                            $letter
                        ],
                    );
                }

                $this->addChange(
                    $changes,
                    'correct_choice',
                    $oldCorrectLetter,
                    $newCorrectLetter,
                );

                /*
                 * A valid no-op does not create audit noise
                 * or increment the version.
                 */
                if ($changes === []) {
                    $question->load([
                        'choices' => fn ($query) => $query
                            ->orderBy(
                                'choice_letter',
                            ),
                    ]);

                    return $question;
                }

                $versionBefore =
                    (int) $question->edit_version;

                $versionAfter =
                    $versionBefore + 1;

                $question->forceFill([
                    'question_text' => $newQuestionText,

                    'hypercorrection_rationale' => $newRationale,

                    'cognitive_level' => $newCognitiveLevel,

                    'edit_version' => $versionAfter,
                ])->save();

                foreach (
                    self::CHOICE_LETTERS as $letter
                ) {
                    /** @var QuestionChoice $choice */
                    $choice = $choices->get(
                        $letter,
                    );

                    $choice->forceFill([
                        'choice_text' => $newChoiceTexts[
                                $letter
                            ],

                        'is_correct' => $letter
                            === $newCorrectLetter,
                    ]);

                    if ($choice->isDirty()) {
                        $choice->save();
                    }
                }

                $correctCount =
                    QuestionChoice::query()
                        ->where(
                            'question_id',
                            $question->question_id,
                        )
                        ->where(
                            'is_correct',
                            true,
                        )
                        ->count();

                if ($correctCount !== 1) {
                    throw new LogicException(
                        'A question must have exactly '
                            .'one correct choice.',
                    );
                }

                $this->recordAudit(
                    question: $question,
                    editor: $editor,
                    action: 'content_updated',
                    changes: $changes,
                    reason: $reason,
                    versionBefore: $versionBefore,
                    versionAfter: $versionAfter,
                );

                $question->refresh();

                $question->load([
                    'choices' => fn ($query) => $query
                        ->orderBy(
                            'choice_letter',
                        ),
                ]);

                return $question;
            },
        );
    }

    public function setActive(
        User $editor,
        Question $question,
        bool $active,
        int $expectedVersion,
        string $reason,
    ): Question {
        $this->assertAdministrator(
            $editor,
        );

        $reason = $this->validatedReason(
            $reason,
        );

        return DB::transaction(
            function () use (
                $editor,
                $question,
                $active,
                $expectedVersion,
                $reason,
            ): Question {
                $question = Question::query()
                    ->whereKey(
                        $question->question_id,
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                /*
                 * Make the operation idempotent.
                 *
                 * Sending "inactive" twice keeps the question
                 * inactive instead of toggling it back on.
                 */
                if (
                    (bool) $question->is_active
                    === $active
                ) {
                    return $question;
                }

                $this->assertVersion(
                    $question,
                    $expectedVersion,
                );

                /*
                 * Locked questions may be retired but cannot
                 * re-enter a live pool without replacement
                 * and formal revalidation.
                 */
                if (
                    $active
                    && $this->lock->isLocked(
                        $question,
                    )
                ) {
                    throw new LogicException(
                        'A served or validated question '
                            .'cannot be reactivated. Create '
                            .'and validate a replacement item.',
                    );
                }

                $versionBefore =
                    (int) $question->edit_version;

                $versionAfter =
                    $versionBefore + 1;

                $oldActive =
                    (bool) $question->is_active;

                $question->forceFill([
                    'is_active' => $active,
                    'edit_version' => $versionAfter,
                ])->save();

                $this->recordAudit(
                    question: $question,
                    editor: $editor,
                    action: $active
                        ? 'reactivated'
                        : 'deactivated',
                    changes: [
                        'is_active' => [
                            'old' => $oldActive,
                            'new' => $active,
                        ],
                    ],
                    reason: $reason,
                    versionBefore: $versionBefore,
                    versionAfter: $versionAfter,
                );

                $question->refresh();

                return $question;
            },
        );
    }

    private function assertAdministrator(
        User $editor,
    ): void {
        if ($editor->role !== 'admin') {
            throw new LogicException(
                'Only administrators may modify '
                    .'the question bank.',
            );
        }
    }

    private function assertVersion(
        Question $question,
        int $expectedVersion,
    ): void {
        if (
            (int) $question->edit_version
            !== $expectedVersion
        ) {
            throw new LogicException(
                'This question was changed by another '
                    .'administrator. Reload the page '
                    .'before trying again.',
            );
        }
    }

    /**
     * @param  array<int, string>  $letters
     */
    private function assertExistingChoiceStructure(
        array $letters,
        int $correctCount,
    ): void {
        sort($letters);

        if ($letters !== self::CHOICE_LETTERS) {
            throw new LogicException(
                'The stored question must contain '
                    .'choices A, B, C, and D.',
            );
        }

        if ($correctCount !== 1) {
            throw new LogicException(
                'The stored question must contain '
                    .'exactly one correct choice.',
            );
        }
    }

    /**
     * @return array<string, string>
     */
    private function validatedChoiceTexts(
        mixed $payload,
    ): array {
        if (! is_array($payload)) {
            throw new InvalidArgumentException(
                'Choices must be an array.',
            );
        }

        $letters = array_keys(
            $payload,
        );

        sort($letters);

        if ($letters !== self::CHOICE_LETTERS) {
            throw new InvalidArgumentException(
                'Choices A, B, C, and D '
                    .'are required.',
            );
        }

        $texts = [];

        foreach (
            self::CHOICE_LETTERS as $letter
        ) {
            if (
                ! is_array(
                    $payload[$letter],
                )
            ) {
                throw new InvalidArgumentException(
                    "Choice {$letter} is invalid.",
                );
            }

            $text = trim(
                (string) (
                    $payload[
                        $letter
                    ]['text'] ?? ''
                ),
            );

            if ($text === '') {
                throw new InvalidArgumentException(
                    "Choice {$letter} cannot be empty.",
                );
            }

            $texts[$letter] = $text;
        }

        /*
         * Prevent four choices that only differ in
         * capitalization or extra spaces.
         */
        $normalized = collect($texts)
            ->map(
                function (
                    string $text,
                ): string {
                    $text = mb_strtolower(
                        trim($text),
                    );

                    return preg_replace(
                        '/\s+/u',
                        ' ',
                        $text,
                    ) ?? $text;
                },
            );

        if (
            $normalized->unique()->count()
            !== count(self::CHOICE_LETTERS)
        ) {
            throw new InvalidArgumentException(
                'Every answer choice must have '
                    .'different text.',
            );
        }

        return $texts;
    }

    private function nullableText(
        mixed $value,
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim(
            (string) $value,
        );

        return $value === ''
            ? null
            : $value;
    }

    private function validatedReason(
        string $reason,
    ): string {
        $reason = trim(
            $reason,
        );

        if (mb_strlen($reason) < 5) {
            throw new InvalidArgumentException(
                'A meaningful change reason '
                    .'is required.',
            );
        }

        if (mb_strlen($reason) > 500) {
            throw new InvalidArgumentException(
                'The change reason may not exceed '
                    .'500 characters.',
            );
        }

        return $reason;
    }

    /**
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     */
    private function addChange(
        array &$changes,
        string $field,
        mixed $old,
        mixed $new,
    ): void {
        if ($old === $new) {
            return;
        }

        $changes[$field] = [
            'old' => $old,
            'new' => $new,
        ];
    }

    /**
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     */
    private function recordAudit(
        Question $question,
        User $editor,
        string $action,
        array $changes,
        string $reason,
        int $versionBefore,
        int $versionAfter,
    ): void {
        QuestionEdit::query()->create([
            'question_id' => $question->question_id,

            'editor_id' => $editor->user_id,

            'action' => $action,

            'changes' => $changes,

            'reason' => $reason,

            'version_before' => $versionBefore,

            'version_after' => $versionAfter,

            'edited_at' => now(),
        ]);
    }
}
