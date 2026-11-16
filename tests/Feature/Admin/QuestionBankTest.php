<?php

use App\Models\Question;
use App\Models\QuestionChoice;
use App\Models\QuestionEdit;
use App\Models\ResearchFormVersion;
use App\Models\TosCompetency;
use App\Models\TosDomain;
use App\Models\User;
use App\Services\AdminDashboardService;
use App\Services\QuestionEditService;
use App\Services\QuestionLockService;
use App\Services\QuestionUsageService;
use Carbon\CarbonImmutable;

beforeEach(function (): void {
    $this->withoutVite();
});

function questionBankAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function questionBankCompetency(): TosCompetency
{
    $domain = TosDomain::query()->firstOrCreate(
        ['domain_number' => 1],
        [
            'domain_name' => 'Question Bank Test Domain',
            'prc_weight_percentage' => 16.67,
        ],
    );

    return TosCompetency::query()->firstOrCreate(
        ['competency_code' => 'QB-TEST-01'],
        [
            'domain_id' => $domain->domain_id,
            'title' => 'Question Bank Test Competency',
            'order_index' => 1,
            'bkt_transition_p_t' => 0.1000,
            'tos_weight_percentage' => 50.00,
        ],
    );
}

/** @param array<string, mixed> $overrides */
function questionBankQuestion(array $overrides = []): Question
{
    $internal = [];

    foreach ([
        'first_served_at',
        'edit_version',
        'research_validated_at',
        'research_validated_by',
    ] as $field) {
        if (array_key_exists($field, $overrides)) {
            $internal[$field] = $overrides[$field];
            unset($overrides[$field]);
        }
    }

    $question = Question::query()->create(array_merge([
        'competency_id' => questionBankCompetency()->competency_id,
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
    ], $overrides));

    foreach ([
        'A' => 'Choice Alpha',
        'B' => 'Choice Bravo',
        'C' => 'Choice Charlie',
        'D' => 'Choice Delta',
    ] as $letter => $text) {
        QuestionChoice::query()->create([
            'question_id' => $question->question_id,
            'choice_letter' => $letter,
            'choice_text' => $text,
            'is_correct' => $letter === 'A',
            'distractor_fallacy_note' => null,
        ]);
    }

    if ($internal !== []) {
        $question->forceFill($internal)->save();
    }

    return $question->fresh([
        'choices' => fn ($query) => $query->orderBy('choice_letter'),
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
    $question->loadMissing('choices');

    $payload = [
        'edit_version' => $question->edit_version,
        'question_text' => $question->question_text,
        'hypercorrection_rationale' => $question->hypercorrection_rationale,
        'cognitive_level' => $question->cognitive_level,
        'choices' => $question->choices
            ->mapWithKeys(
                fn (QuestionChoice $choice): array => [
                    $choice->choice_letter => [
                        'text' => $choice->choice_text,
                    ],
                ],
            )
            ->all(),
        'correct_choice' => $question->choices
            ->firstWhere('is_correct', true)
            ?->choice_letter,
    ];

    return array_replace_recursive($payload, $overrides);
}

it('allows only administrators to access the question bank', function (): void {
    $question = questionBankQuestion();
    $student = User::factory()->create(['role' => 'student']);
    $faculty = User::factory()->create(['role' => 'faculty']);
    $admin = questionBankAdmin();

    $this->get(route('admin.questions.index'))
        ->assertRedirect(route('login'));

    $this->actingAs($student)
        ->get(route('admin.questions.index'))
        ->assertForbidden();

    $this->actingAs($faculty)
        ->get(route('admin.questions.index'))
        ->assertForbidden();

    $this->actingAs($admin)
        ->get(route('admin.questions.index'))
        ->assertOk()
        ->assertSee('Question Bank');

    $this->get(route('admin.questions.show', [
        'question' => $question->question_id,
    ]))->assertOk();
});

it('identifies an unserved unvalidated question as editable', function (): void {
    $question = questionBankQuestion();
    $lock = app(QuestionLockService::class);

    expect($lock->isLocked($question))
        ->toBeFalse()
        ->and($lock->lockReason($question))
        ->toBeNull();
});

it('updates a draft without a reason and records changed fields', function (): void {
    $question = questionBankQuestion();
    $admin = questionBankAdmin();

    $this->actingAs($admin)
        ->put(
            route('admin.questions.update', [
                'question' => $question->question_id,
            ]),
            questionBankPayload($question, [
                'question_text' => 'What is the revised question text?',
                'correct_choice' => 'B',
            ]),
        )
        ->assertRedirect(route('admin.questions.show', [
            'question' => $question->question_id,
        ]))
        ->assertSessionHasNoErrors();

    $question->refresh();
    $correctChoice = $question->choices()
        ->where('is_correct', true)
        ->firstOrFail();
    $edit = QuestionEdit::query()->firstOrFail();

    expect($question->question_text)
        ->toBe('What is the revised question text?')
        ->and($question->edit_version)
        ->toBe(2)
        ->and($correctChoice->choice_letter)
        ->toBe('B')
        ->and($edit->action)
        ->toBe('content_updated')
        ->and($edit->editor_id)
        ->toBe($admin->user_id)
        ->and($edit->version_before)
        ->toBe(1)
        ->and($edit->version_after)
        ->toBe(2)
        ->and($edit->reason)
        ->toBeNull()
        ->and($edit->changes)
        ->toHaveKeys(['question_text', 'correct_choice']);
});

it('does not create audit noise for an unchanged submission', function (): void {
    $question = questionBankQuestion();
    $admin = questionBankAdmin();

    $this->actingAs($admin)
        ->put(
            route('admin.questions.update', [
                'question' => $question->question_id,
            ]),
            questionBankPayload($question),
        )
        ->assertRedirect();

    expect(QuestionEdit::query()->count())
        ->toBe(0)
        ->and($question->fresh()->edit_version)
        ->toBe(1);
});

it('locks a question immediately when marked as served', function (): void {
    $question = questionBankQuestion();
    $servedAt = CarbonImmutable::now()
        ->subHour()
        ->startOfSecond();

    app(QuestionUsageService::class)->markServed(
        [$question->question_id],
        $servedAt,
    );

    $question->refresh();

    expect($question->first_served_at?->equalTo($servedAt))
        ->toBeTrue()
        ->and(app(QuestionLockService::class)->isLocked($question))
        ->toBeTrue();
});

it('does not replace the original first served timestamp', function (): void {
    $question = questionBankQuestion();
    $first = CarbonImmutable::now()->subHours(2)->startOfSecond();
    $later = CarbonImmutable::now()->startOfSecond();
    $usage = app(QuestionUsageService::class);

    $usage->markServed([$question->question_id], $first);
    $usage->markServed([$question->question_id], $later);

    expect($question->fresh()->first_served_at?->equalTo($first))
        ->toBeTrue();
});

it('rejects content edits after a question was served', function (): void {
    $question = questionBankQuestion();

    app(QuestionUsageService::class)->markServed([
        $question->question_id,
    ]);

    $admin = questionBankAdmin();

    $this->actingAs($admin)
        ->put(
            route('admin.questions.update', [
                'question' => $question->question_id,
            ]),
            questionBankPayload(
                $question->fresh(['choices']),
                ['question_text' => 'This change must be rejected.'],
            ),
        )
        ->assertSessionHas('error');

    expect($question->fresh()->question_text)
        ->not->toBe('This change must be rejected.')
        ->and(QuestionEdit::query()->count())
        ->toBe(0);
});

it('locks questions with complete or partial validation metadata', function (): void {
    $admin = questionBankAdmin();

    $validated = questionBankQuestion([
        'research_validated_at' => now(),
        'research_validated_by' => $admin->user_id,
    ]);
    $partial = questionBankQuestion([
        'research_validated_at' => now(),
        'research_validated_by' => null,
    ]);

    $lock = app(QuestionLockService::class);

    expect($lock->isLocked($validated))
        ->toBeTrue()
        ->and($lock->isLocked($partial))
        ->toBeTrue();
});

it('rejects stale administrator submissions', function (): void {
    $question = questionBankQuestion();
    $admin = questionBankAdmin();

    app(QuestionEditService::class)->updateContent(
        editor: $admin,
        question: $question,
        payload: questionBankPayload($question, [
            'question_text' => 'Newer administrator version.',
        ]),
        expectedVersion: 1,
        reason: null,
    );

    $stalePayload = questionBankPayload(
        $question->fresh(['choices']),
        [
            'edit_version' => 1,
            'question_text' => 'Stale browser version.',
        ],
    );

    $this->actingAs($admin)
        ->put(
            route('admin.questions.update', [
                'question' => $question->question_id,
            ]),
            $stalePayload,
        )
        ->assertSessionHas('error');

    expect($question->fresh()->question_text)
        ->toBe('Newer administrator version.');
});

it('rejects duplicate answer choice text', function (): void {
    $question = questionBankQuestion();
    $admin = questionBankAdmin();
    $payload = questionBankPayload($question);

    $payload['choices']['B']['text'] =
        $payload['choices']['A']['text'];

    $this->actingAs($admin)
        ->put(
            route('admin.questions.update', [
                'question' => $question->question_id,
            ]),
            $payload,
        )
        ->assertSessionHas('error');

    expect(QuestionEdit::query()->count())->toBe(0);
});

it('requires a reason to deactivate a question', function (): void {
    $admin = questionBankAdmin();
    $question = questionBankQuestion();

    $this->actingAs($admin)
        ->patch(
            route('admin.questions.status', [
                'question' => $question->question_id,
            ]),
            [
                'active' => false,
                'edit_version' => $question->edit_version,
            ],
        )
        ->assertSessionHasErrors('change_reason');

    expect($question->fresh()->is_active)
        ->toBeTrue()
        ->and(QuestionEdit::query()->count())
        ->toBe(0);
});

it('allows a locked question to be deactivated and audits it', function (): void {
    $admin = questionBankAdmin();

    $question = questionBankQuestion([
        'research_validated_at' => now(),
        'research_validated_by' => $admin->user_id,
    ]);

    $this->actingAs($admin)
        ->patch(
            route('admin.questions.status', [
                'question' => $question->question_id,
            ]),
            [
                'active' => false,
                'edit_version' => 1,
                'change_reason' => 'Faculty identified an ambiguous distractor.',
            ],
        )
        ->assertRedirect();

    $question->refresh();
    $edit = QuestionEdit::query()->firstOrFail();

    expect($question->is_active)
        ->toBeFalse()
        ->and($question->edit_version)
        ->toBe(2)
        ->and($edit->action)
        ->toBe('deactivated')
        ->and($edit->changes['is_active']['old'])
        ->toBeTrue()
        ->and($edit->changes['is_active']['new'])
        ->toBeFalse();
});

it('reactivates an eligible draft without a typed reason', function (): void {
    $admin = questionBankAdmin();
    $question = questionBankQuestion(['is_active' => false]);

    $this->actingAs($admin)
        ->patch(
            route('admin.questions.status', [
                'question' => $question->question_id,
            ]),
            [
                'active' => true,
                'edit_version' => $question->edit_version,
            ],
        )
        ->assertSessionHasNoErrors();

    $edit = QuestionEdit::query()->firstOrFail();

    expect($question->fresh()->is_active)
        ->toBeTrue()
        ->and($edit->action)
        ->toBe('reactivated')
        ->and($edit->reason)
        ->toBeNull();
});

it('prevents a locked retired question from being reactivated', function (): void {
    $admin = questionBankAdmin();

    $question = questionBankQuestion([
        'research_validated_at' => now(),
        'research_validated_by' => $admin->user_id,
        'is_active' => false,
    ]);

    $this->actingAs($admin)
        ->patch(
            route('admin.questions.status', [
                'question' => $question->question_id,
            ]),
            [
                'active' => true,
                'edit_version' => 1,
            ],
        )
        ->assertSessionHas('error');

    expect($question->fresh()->is_active)
        ->toBeFalse()
        ->and(QuestionEdit::query()->count())
        ->toBe(0);
});

it('makes repeated status requests idempotent', function (): void {
    $admin = questionBankAdmin();
    $question = questionBankQuestion();

    $payload = [
        'active' => false,
        'edit_version' => 1,
        'change_reason' => 'Temporarily remove this draft question.',
    ];

    $url = route('admin.questions.status', [
        'question' => $question->question_id,
    ]);

    $this->actingAs($admin)
        ->patch($url, $payload)
        ->assertRedirect();

    $this->patch($url, $payload)
        ->assertRedirect();

    expect($question->fresh()->is_active)
        ->toBeFalse()
        ->and(QuestionEdit::query()->count())
        ->toBe(1);
});

it('prevents direct service use by a non administrator', function (): void {
    $question = questionBankQuestion();
    $faculty = User::factory()->create(['role' => 'faculty']);

    expect(fn () => app(QuestionEditService::class)->updateContent(
        editor: $faculty,
        question: $question,
        payload: questionBankPayload($question),
        expectedVersion: 1,
        reason: null,
    ))->toThrow(LogicException::class);
});

it('keeps question audit records immutable', function (): void {
    $question = questionBankQuestion();
    $admin = questionBankAdmin();

    app(QuestionEditService::class)->updateContent(
        editor: $admin,
        question: $question,
        payload: questionBankPayload($question, [
            'question_text' => 'Audited revised question.',
        ]),
        expectedVersion: 1,
        reason: null,
    );

    $edit = QuestionEdit::query()->firstOrFail();

    expect(fn () => $edit->delete())
        ->toThrow(LogicException::class);
});

it('keeps the dashboard review count aligned with the question filter', function (): void {
    $administrator = questionBankAdmin();

    $reviewVersion = ResearchFormVersion::query()->create([
        'research_form' => Question::FORM_PRE_TEST_A,
        'version_number' => 90,
        'version_code' => 'pre_test_a_review_test',
        'status' => ResearchFormVersion::STATUS_IN_REVIEW,
        'is_active' => false,
        'active_slot' => null,
        'created_by' => $administrator->user_id,
        'lock_version' => 1,
    ]);

    $draftVersion = ResearchFormVersion::query()->create([
        'research_form' => Question::FORM_PRE_TEST_A,
        'version_number' => 91,
        'version_code' => 'pre_test_a_draft_test',
        'status' => ResearchFormVersion::STATUS_DRAFT,
        'is_active' => false,
        'active_slot' => null,
        'created_by' => $administrator->user_id,
        'lock_version' => 1,
    ]);

    $reviewQuestion = questionBankQuestion([
        'question_text' => 'Question submitted for validation review.',
        'research_form' => Question::FORM_PRE_TEST_A,
        'form_position' => 1,
        'form_version_id' => $reviewVersion->form_version_id,
    ]);

    $draftQuestion = questionBankQuestion([
        'question_text' => 'Question still belonging to a draft version.',
        'research_form' => Question::FORM_PRE_TEST_A,
        'form_position' => 1,
        'form_version_id' => $draftVersion->form_version_id,
    ]);

    $dashboardData = app(AdminDashboardService::class)->data();

    expect($dashboardData['questionStats']['needing_review'])
        ->toBe(1);

    $this->actingAs($administrator)
        ->get(route('admin.questions.index', [
            'status' => 'awaiting_validation',
        ]))
        ->assertOk()
        ->assertSeeText($reviewQuestion->question_text)
        ->assertSeeText($reviewVersion->version_code)
        ->assertDontSeeText($draftQuestion->question_text)
        ->assertDontSeeText($draftVersion->version_code);
});
