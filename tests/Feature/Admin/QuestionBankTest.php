<?php

use App\Models\Question;
use App\Models\QuestionChoice;
use App\Models\QuestionEdit;
use App\Models\TosCompetency;
use App\Models\TosDomain;
use App\Models\User;
use App\Services\QuestionEditService;
use App\Services\QuestionLockService;
use App\Services\QuestionUsageService;
use Carbon\CarbonImmutable;
use LogicException;

beforeEach(function () {
    $this->withoutVite();
});

function questionBankAdmin(): User
{
    return User::factory()->create([
        'role' => 'admin',
    ]);
}

function questionBankCompetency(): TosCompetency
{
    $domain = TosDomain::query()
        ->firstOrCreate(
            [
                'domain_number' => 1,
            ],
            [
                'domain_name' => 'Question Bank Test Domain',

                /*
                 * Use a value supported by DECIMAL(4, 2).
                 */
                'prc_weight_percentage' => 16.67,
            ],
        );

    return TosCompetency::query()
        ->firstOrCreate(
            [
                'competency_code' => 'QB-TEST-01',
            ],
            [
                'domain_id' => $domain->domain_id,

                'title' => 'Question Bank Test Competency',

                'order_index' => 1,

                'bkt_transition_p_t' => 0.1000,

                'tos_weight_percentage' => 50.00,
            ],
        );
}

/**
 * @param  array<string, mixed>  $overrides
 */
function questionBankQuestion(
    array $overrides = [],
): Question {
    $competency =
        questionBankCompetency();

    /*
     * These values freeze or internally control the question.
     *
     * Apply them only after the four choices have been created.
     */
    $internal = [];

    foreach (
        [
            'first_served_at',
            'edit_version',
            'research_validated_at',
            'research_validated_by',
        ] as $field
    ) {
        if (
            array_key_exists(
                $field,
                $overrides,
            )
        ) {
            $internal[$field] =
                $overrides[$field];

            unset(
                $overrides[$field],
            );
        }
    }

    /*
     * Initially create an unlocked question so its required
     * choices can be constructed.
     */
    $question = Question::query()->create(
        array_merge(
            [
                'competency_id' => $competency->competency_id,

                'question_text' => 'Which medicine is used in this test question?',

                'hypercorrection_rationale' => 'This is the test rationale.',

                'is_diagnostic_pool' => false,

                'difficulty_index_p' => null,

                'speed_flag_count' => 0,

                'is_active' => true,

                'research_form' => null,

                'form_position' => null,

                'cognitive_level' => Question::COGNITIVE_KNOWLEDGE,

                'research_validated_at' => null,

                'research_validated_by' => null,
            ],
            $overrides,
        ),
    );

    $texts = [
        'A' => 'Choice Alpha',
        'B' => 'Choice Bravo',
        'C' => 'Choice Charlie',
        'D' => 'Choice Delta',
    ];

    foreach (
        $texts as $letter => $text
    ) {
        QuestionChoice::query()->create([
            'question_id' => $question->question_id,

            'choice_letter' => $letter,

            'choice_text' => $text,

            'is_correct' => $letter === 'A',

            'distractor_fallacy_note' => null,
        ]);
    }

    /*
     * Choices now exist, so validation/serving metadata
     * may safely freeze the completed question.
     */
    if ($internal !== []) {
        $question->forceFill(
            $internal,
        )->save();
    }

    return $question->fresh([
        'choices' => fn ($query) => $query
            ->orderBy(
                'choice_letter',
            ),
    ]);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function questionBankPayload(
    Question $question,
    array $overrides = [],
): array {
    $question->loadMissing(
        'choices',
    );

    $payload = [
        'edit_version' => $question->edit_version,

        'change_reason' => 'Updated after administrator review.',

        'question_text' => $question->question_text,

        'hypercorrection_rationale' => $question->hypercorrection_rationale,

        'cognitive_level' => $question->cognitive_level,

        'choices' => $question->choices
            ->mapWithKeys(
                fn (
                    QuestionChoice $choice,
                ): array => [
                    $choice->choice_letter => [
                        'text' => $choice->choice_text,
                    ],
                ],
            )
            ->all(),

        'correct_choice' => $question->choices
            ->firstWhere(
                'is_correct',
                true,
            )
            ?->choice_letter,
    ];

    return array_replace_recursive(
        $payload,
        $overrides,
    );
}

it('allows only administrators to access the question bank', function () {
    $question =
        questionBankQuestion();

    $student = User::factory()->create([
        'role' => 'student',
    ]);

    $faculty = User::factory()->create([
        'role' => 'faculty',
    ]);

    $admin =
        questionBankAdmin();

    $this->get(
        route(
            'admin.questions.index',
        ),
    )->assertRedirect(
        route('login'),
    );

    $this->actingAs(
        $student,
    )->get(
        route(
            'admin.questions.index',
        ),
    )->assertForbidden();

    $this->actingAs(
        $faculty,
    )->get(
        route(
            'admin.questions.index',
        ),
    )->assertForbidden();

    $this->actingAs(
        $admin,
    )->get(
        route(
            'admin.questions.index',
        ),
    )->assertOk()
        ->assertSee(
            'Question Bank',
        );

    $this->actingAs(
        $admin,
    )->get(
        route(
            'admin.questions.show',
            [
                'question' => $question->question_id,
            ],
        ),
    )->assertOk();
});

it('identifies an unserved unvalidated question as editable', function () {
    $question =
        questionBankQuestion();

    $lock = app(
        QuestionLockService::class,
    );

    expect(
        $lock->isLocked(
            $question,
        ),
    )->toBeFalse()
        ->and(
            $lock->lockReason(
                $question,
            ),
        )->toBeNull();
});

it('updates draft content and records one audit operation', function () {
    $question =
        questionBankQuestion();

    $admin =
        questionBankAdmin();

    $payload = questionBankPayload(
        $question,
        [
            'question_text' => 'What is the revised question text?',

            'correct_choice' => 'B',
        ],
    );

    $this->actingAs(
        $admin,
    )->put(
        route(
            'admin.questions.update',
            [
                'question' => $question->question_id,
            ],
        ),
        $payload,
    )->assertRedirect(
        route(
            'admin.questions.show',
            [
                'question' => $question->question_id,
            ],
        ),
    )->assertSessionHasNoErrors();

    $question->refresh();

    expect(
        $question->question_text,
    )->toBe(
        'What is the revised question text?',
    )->and(
        $question->edit_version,
    )->toBe(
        2,
    );

    $correctChoice =
        $question->choices()
            ->where(
                'is_correct',
                true,
            )
            ->firstOrFail();

    expect(
        $correctChoice->choice_letter,
    )->toBe(
        'B',
    );

    $edit = QuestionEdit::query()
        ->firstOrFail();

    expect(
        $edit->action,
    )->toBe(
        'content_updated',
    )->and(
        $edit->editor_id,
    )->toBe(
        $admin->user_id,
    )->and(
        $edit->version_before,
    )->toBe(
        1,
    )->and(
        $edit->version_after,
    )->toBe(
        2,
    )->and(
        $edit->changes,
    )->toHaveKeys([
        'question_text',
        'correct_choice',
    ]);
});

it('does not create audit noise for an unchanged submission', function () {
    $question =
        questionBankQuestion();

    $admin =
        questionBankAdmin();

    $this->actingAs(
        $admin,
    )->put(
        route(
            'admin.questions.update',
            [
                'question' => $question->question_id,
            ],
        ),
        questionBankPayload(
            $question,
        ),
    )->assertRedirect();

    expect(
        QuestionEdit::query()->count(),
    )->toBe(
        0,
    )->and(
        $question->fresh()->edit_version,
    )->toBe(
        1,
    );
});

it('locks a question immediately when it is marked as served', function () {
    $question =
        questionBankQuestion();

    $servedAt = CarbonImmutable::now()
        ->subHour()
        ->startOfSecond();

    app(
        QuestionUsageService::class,
    )->markServed(
        [
            $question->question_id,
        ],
        $servedAt,
    );

    $question->refresh();

    expect(
        $question->first_served_at
            ?->equalTo(
                $servedAt,
            ),
    )->toBeTrue()
        ->and(
            app(
                QuestionLockService::class,
            )->isLocked(
                $question,
            ),
        )->toBeTrue();
});

it('does not replace the original first served timestamp', function () {
    $question =
        questionBankQuestion();

    $first = CarbonImmutable::now()
        ->subHours(2)
        ->startOfSecond();

    $later = CarbonImmutable::now()
        ->startOfSecond();

    $usage = app(
        QuestionUsageService::class,
    );

    $usage->markServed(
        [
            $question->question_id,
        ],
        $first,
    );

    $usage->markServed(
        [
            $question->question_id,
        ],
        $later,
    );

    expect(
        $question
            ->fresh()
            ->first_served_at
            ?->equalTo(
                $first,
            ),
    )->toBeTrue();
});

it('rejects content edits after a question was served', function () {
    $question =
        questionBankQuestion();

    app(
        QuestionUsageService::class,
    )->markServed([
        $question->question_id,
    ]);

    $admin =
        questionBankAdmin();

    $payload = questionBankPayload(
        $question->fresh([
            'choices',
        ]),
        [
            'question_text' => 'This change must be rejected.',
        ],
    );

    $this->actingAs(
        $admin,
    )->put(
        route(
            'admin.questions.update',
            [
                'question' => $question->question_id,
            ],
        ),
        $payload,
    )->assertSessionHas(
        'error',
    );

    expect(
        $question
            ->fresh()
            ->question_text,
    )->not->toBe(
        'This change must be rejected.',
    )->and(
        QuestionEdit::query()->count(),
    )->toBe(
        0,
    );
});

it('locks questions with complete or partial validation metadata', function () {
    $admin =
        questionBankAdmin();

    $validated =
        questionBankQuestion([
            'research_validated_at' => now(),

            'research_validated_by' => $admin->user_id,
        ]);

    $partial =
        questionBankQuestion([
            'research_validated_at' => now(),

            'research_validated_by' => null,
        ]);

    $lock = app(
        QuestionLockService::class,
    );

    expect(
        $lock->isLocked(
            $validated,
        ),
    )->toBeTrue()
        ->and(
            $lock->isLocked(
                $partial,
            ),
        )->toBeTrue();
});

it('rejects stale administrator submissions', function () {
    $question =
        questionBankQuestion();

    $admin =
        questionBankAdmin();

    $service = app(
        QuestionEditService::class,
    );

    $service->updateContent(
        editor: $admin,
        question: $question,
        payload: questionBankPayload(
            $question,
            [
                'question_text' => 'Newer administrator version.',
            ],
        ),
        expectedVersion: 1,
        reason: 'First administrator update.',
    );

    /*
     * Simulate an old browser tab that still believes
     * the version is 1.
     */
    $stalePayload = questionBankPayload(
        $question->fresh([
            'choices',
        ]),
        [
            'edit_version' => 1,

            'question_text' => 'Stale browser version.',
        ],
    );

    $this->actingAs(
        $admin,
    )->put(
        route(
            'admin.questions.update',
            [
                'question' => $question->question_id,
            ],
        ),
        $stalePayload,
    )->assertSessionHas(
        'error',
    );

    expect(
        $question
            ->fresh()
            ->question_text,
    )->toBe(
        'Newer administrator version.',
    );
});

it('rejects duplicate answer choice text', function () {
    $question =
        questionBankQuestion();

    $admin =
        questionBankAdmin();

    $payload = questionBankPayload(
        $question,
    );

    $payload['choices']['B']['text'] =
        $payload['choices']['A']['text'];

    $this->actingAs(
        $admin,
    )->put(
        route(
            'admin.questions.update',
            [
                'question' => $question->question_id,
            ],
        ),
        $payload,
    )->assertSessionHas(
        'error',
    );

    expect(
        QuestionEdit::query()->count(),
    )->toBe(
        0,
    );
});

it('allows a locked question to be deactivated and audits the action', function () {
    $admin =
        questionBankAdmin();

    $question =
        questionBankQuestion([
            'research_validated_at' => now(),

            'research_validated_by' => $admin->user_id,
        ]);

    $this->actingAs(
        $admin,
    )->patch(
        route(
            'admin.questions.status',
            [
                'question' => $question->question_id,
            ],
        ),
        [
            'active' => false,
            'edit_version' => 1,
            'change_reason' => 'Faculty identified an ambiguous distractor.',
        ],
    )->assertRedirect();

    $question->refresh();

    expect(
        $question->is_active,
    )->toBeFalse()
        ->and(
            $question->edit_version,
        )->toBe(
            2,
        );

    $edit = QuestionEdit::query()
        ->firstOrFail();

    expect(
        $edit->action,
    )->toBe(
        'deactivated',
    )->and(
        $edit->changes['is_active']['old'],
    )->toBeTrue()
        ->and(
            $edit->changes['is_active']['new'],
        )->toBeFalse();
});

it('prevents a locked retired question from being reactivated', function () {
    $admin =
        questionBankAdmin();

    $question =
        questionBankQuestion([
            'research_validated_at' => now(),

            'research_validated_by' => $admin->user_id,

            'is_active' => false,
        ]);

    $this->actingAs(
        $admin,
    )->patch(
        route(
            'admin.questions.status',
            [
                'question' => $question->question_id,
            ],
        ),
        [
            'active' => true,
            'edit_version' => 1,
            'change_reason' => 'Attempt to reactivate locked content.',
        ],
    )->assertSessionHas(
        'error',
    );

    expect(
        $question
            ->fresh()
            ->is_active,
    )->toBeFalse()
        ->and(
            QuestionEdit::query()->count(),
        )->toBe(
            0,
        );
});

it('makes repeated status requests idempotent', function () {
    $admin =
        questionBankAdmin();

    $question =
        questionBankQuestion();

    $payload = [
        'active' => false,
        'edit_version' => 1,
        'change_reason' => 'Temporarily remove this draft question.',
    ];

    $this->actingAs(
        $admin,
    )->patch(
        route(
            'admin.questions.status',
            [
                'question' => $question->question_id,
            ],
        ),
        $payload,
    )->assertRedirect();

    /*
     * Simulate a duplicate request carrying the original
     * version. Since the desired state is already present,
     * it should remain a harmless no-op.
     */
    $this->actingAs(
        $admin,
    )->patch(
        route(
            'admin.questions.status',
            [
                'question' => $question->question_id,
            ],
        ),
        $payload,
    )->assertRedirect();

    expect(
        $question
            ->fresh()
            ->is_active,
    )->toBeFalse()
        ->and(
            QuestionEdit::query()->count(),
        )->toBe(
            1,
        );
});

it('prevents direct service use by a non administrator', function () {
    $question =
        questionBankQuestion();

    $faculty = User::factory()->create([
        'role' => 'faculty',
    ]);

    expect(
        fn () => app(
            QuestionEditService::class,
        )->updateContent(
            editor: $faculty,
            question: $question,
            payload: questionBankPayload(
                $question,
            ),
            expectedVersion: 1,
            reason: 'Unauthorized faculty edit attempt.',
        ),
    )->toThrow(
        LogicException::class,
    );
});

it('keeps question audit records immutable', function () {
    $question =
        questionBankQuestion();

    $admin =
        questionBankAdmin();

    app(
        QuestionEditService::class,
    )->updateContent(
        editor: $admin,
        question: $question,
        payload: questionBankPayload(
            $question,
            [
                'question_text' => 'Audited revised question.',
            ],
        ),
        expectedVersion: 1,
        reason: 'Create an audit record for this test.',
    );

    $edit = QuestionEdit::query()
        ->firstOrFail();

    expect(
        fn () => $edit->delete(),
    )->toThrow(
        LogicException::class,
    );
});
