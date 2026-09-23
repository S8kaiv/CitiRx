<?php

use App\Models\AssessmentResponse;
use App\Models\AssessmentSession;
use App\Models\Cohort;
use App\Models\Question;
use App\Models\ResponseTelemetryLog;
use App\Models\RxVault;
use App\Models\TosCompetency;
use App\Models\User;
use App\Models\UserBadge;
use App\Models\UserKnowledgeState;
use App\Models\XpTransaction;
use App\Services\MockBoardService;
use App\Services\PracticeService;
use App\Services\ResearchFormService;
use Carbon\Carbon;
use Database\Seeders\ResearchQuestionSeeder;
use Database\Seeders\TierAndLevelSeeder;
use Database\Seeders\TosSeeder;
use Illuminate\Support\Str;

function createMockBoardKnowledgeStatesFor(User $user): void
{
    $timestamp = now();

    $rows = TosCompetency::query()
        ->pluck('competency_id')
        ->map(fn (string $competencyId): array => [
            'state_id' => (string) Str::uuid(),
            'user_id' => $user->user_id,
            'competency_id' => $competencyId,
            'current_mastery_p_l' => 0.5000,
            'total_attempts' => 2,
            'total_correct' => 1,
            'last_evaluated_at' => $timestamp,
        ])
        ->all();

    UserKnowledgeState::query()->insert($rows);
}

function createMockBoardStudentFor(
    Cohort $cohort,
    array $attributes = [],
): User {
    $user = User::factory()->create(array_merge([
        'cohort_id' => $cohort->cohort_id,
        'role' => 'student',
        'is_diagnostic_completed' => true,
        'predicted_readiness_pct' => 50,
        'total_xp' => 125,
        'current_level' => 2,
        'streak_count' => 4,
        'last_active_date' => now()->subDay()->toDateString(),
    ], $attributes));

    createMockBoardKnowledgeStatesFor($user);

    return $user;
}

beforeEach(function () {
    Carbon::setTestNow('2026-09-23 12:00:00');

    $this->withoutVite();

    config()->set(
        'citirx.research.allow_unvalidated_forms',
        true,
    );

    config()->set(
        'citirx.research.post_test_duration_minutes',
        72,
    );

    config()->set(
        'citirx.research.post_test_form_version',
        'post_test_b_v1',
    );

    $this->seed([
        TierAndLevelSeeder::class,
        TosSeeder::class,
        ResearchQuestionSeeder::class,
    ]);

    $this->cohort = Cohort::query()->create([
        'cohort_name' => 'Mock Board Feature Test',
        'academic_year' => '2026-2027',
        'target_phle_date' => now()->addYear()->toDateString(),
        'post_test_opens_at' => now()->subHour(),
        'post_test_closes_at' => now()->addHour(),
    ]);

    $this->student = createMockBoardStudentFor(
        $this->cohort,
    );

    $this->mockBoard = app(MockBoardService::class);
    $this->researchForms = app(ResearchFormService::class);
});

afterEach(function () {
    Carbon::setTestNow();
});

it('redirects a guest away from the Mock Board', function () {
    $this->get(route('mock-board.intro'))
        ->assertRedirect(route('login'));
});

it('blocks non-student roles from every Mock Board route', function (
    string $role,
) {
    $session = $this->mockBoard->startPostTest(
        $this->student,
    );

    $response = AssessmentResponse::query()
        ->where('session_id', $session->session_id)
        ->firstOrFail();

    $choiceId = Question::query()
        ->with('choices')
        ->findOrFail($response->question_id)
        ->choices
        ->firstOrFail()
        ->choice_id;

    $nonStudent = User::factory()->create([
        'role' => $role,
    ]);

    $this->actingAs($nonStudent)
        ->get(route('mock-board.intro'))
        ->assertForbidden();

    $this->actingAs($nonStudent)
        ->post(route('mock-board.start'))
        ->assertForbidden();

    $this->actingAs($nonStudent)
        ->get(route('mock-board.take', $session))
        ->assertForbidden();

    $this->actingAs($nonStudent)
        ->patchJson(
            route('mock-board.answer', [
                'session' => $session,
                'question' => $response->question_id,
            ]),
            ['selected_choice_id' => $choiceId],
        )
        ->assertForbidden();

    $this->actingAs($nonStudent)
        ->post(route('mock-board.submit', $session))
        ->assertForbidden();

    $this->actingAs($nonStudent)
        ->get(route('mock-board.results', $session))
        ->assertForbidden();
})->with([
    'faculty',
    'admin',
]);

it('requires a completed diagnostic before starting', function () {
    $this->student->forceFill([
        'is_diagnostic_completed' => false,
    ])->save();

    $this->actingAs($this->student)
        ->from(route('mock-board.intro'))
        ->post(route('mock-board.start'))
        ->assertRedirect(route('mock-board.intro'))
        ->assertSessionHas(
            'error',
            fn (string $message): bool => str_contains(
                $message,
                'Complete the diagnostic',
            ),
        );

    expect(AssessmentSession::query()->count())
        ->toBe(0);
});

it('requires the student to belong to a cohort', function () {
    $student = createMockBoardStudentFor(
        $this->cohort,
        ['cohort_id' => null],
    );

    $this->actingAs($student)
        ->from(route('mock-board.intro'))
        ->post(route('mock-board.start'))
        ->assertRedirect(route('mock-board.intro'))
        ->assertSessionHas(
            'error',
            fn (string $message): bool => str_contains(
                $message,
                'not currently available',
            ),
        );

    expect(
        AssessmentSession::query()
            ->where('user_id', $student->user_id)
            ->count(),
    )->toBe(0);
});

it('requires an open cohort post-test window', function () {
    $this->cohort->forceFill([
        'post_test_opens_at' => now()->subHours(2),
        'post_test_closes_at' => now()->subHour(),
    ])->save();

    $this->actingAs($this->student)
        ->from(route('mock-board.intro'))
        ->post(route('mock-board.start'))
        ->assertRedirect(route('mock-board.intro'))
        ->assertSessionHas(
            'error',
            fn (string $message): bool => str_contains(
                $message,
                'not currently available',
            ),
        );
});

it('validates the complete fixed Form B blueprint', function () {
    $questions = $this->researchForms->load(
        Question::FORM_POST_TEST_B,
    );

    $positions = $questions
        ->pluck('form_position')
        ->map(fn ($position): int => (int) $position)
        ->sort()
        ->values()
        ->all();

    $subjectCounts = $questions
        ->groupBy(
            fn (Question $question) => $question
                ->competency
                ->domain_id,
        )
        ->map(fn ($group): int => $group->count())
        ->sort()
        ->values()
        ->all();

    expect($questions)
        ->toHaveCount(60)
        ->and($positions)
        ->toBe(range(1, 60))
        ->and($subjectCounts)
        ->toBe([10, 10, 10, 10, 10, 10]);

    foreach ($questions as $question) {
        expect($question->choices)
            ->toHaveCount(4)
            ->and(
                $question->choices
                    ->where('is_correct', true),
            )
            ->toHaveCount(1);
    }
});

it('rejects malformed Form B structures', function () {
    $question = Question::query()
        ->with(['choices', 'competency'])
        ->where(
            'research_form',
            Question::FORM_POST_TEST_B,
        )
        ->orderBy('form_position')
        ->firstOrFail();

    $question->forceFill(['is_active' => false])->save();

    expect(
        fn () => $this->researchForms->load(
            Question::FORM_POST_TEST_B,
        ),
    )->toThrow(RuntimeException::class);

    $question->forceFill(['is_active' => true])->save();

    $originalCompetencyId = $question->competency_id;

    $otherCompetency = TosCompetency::query()
        ->where(
            'domain_id',
            '!=',
            $question->competency->domain_id,
        )
        ->firstOrFail();

    $question->forceFill([
        'competency_id' => $otherCompetency->competency_id,
    ])->save();

    expect(
        fn () => $this->researchForms->load(
            Question::FORM_POST_TEST_B,
        ),
    )->toThrow(RuntimeException::class);

    $question->forceFill([
        'competency_id' => $originalCompetencyId,
    ])->save();

    $originalPosition = $question->form_position;

    $question->forceFill(['form_position' => 61])->save();

    expect(
        fn () => $this->researchForms->load(
            Question::FORM_POST_TEST_B,
        ),
    )->toThrow(RuntimeException::class);

    $question->forceFill([
        'form_position' => $originalPosition,
    ])->save();

    $choice = $question->choices()->firstOrFail();

    $choiceData = [
        'choice_letter' => $choice->choice_letter,
        'choice_text' => $choice->choice_text,
        'is_correct' => $choice->is_correct,
        'distractor_fallacy_note' => $choice->distractor_fallacy_note,
    ];

    $choice->delete();

    expect(
        fn () => $this->researchForms->load(
            Question::FORM_POST_TEST_B,
        ),
    )->toThrow(RuntimeException::class);

    $question->choices()->create($choiceData);

    $wrongChoice = $question->choices()
        ->where('is_correct', false)
        ->firstOrFail();

    $wrongChoice->forceFill(['is_correct' => true])->save();

    expect(
        fn () => $this->researchForms->load(
            Question::FORM_POST_TEST_B,
        ),
    )->toThrow(RuntimeException::class);
});

it('keeps Form A and Form B question IDs disjoint', function () {
    $formAIds = Question::query()
        ->where(
            'research_form',
            Question::FORM_PRE_TEST_A,
        )
        ->pluck('question_id')
        ->all();

    $formBIds = Question::query()
        ->where(
            'research_form',
            Question::FORM_POST_TEST_B,
        )
        ->pluck('question_id')
        ->all();

    expect($formAIds)
        ->toHaveCount(60)
        ->and($formBIds)
        ->toHaveCount(60)
        ->and(array_intersect($formAIds, $formBIds))
        ->toBe([]);
});

it('blocks unvalidated research items when the override is disabled', function () {
    config()->set(
        'citirx.research.allow_unvalidated_forms',
        false,
    );

    expect(
        fn () => $this->researchForms->load(
            Question::FORM_POST_TEST_B,
        ),
    )->toThrow(RuntimeException::class);

    $status = $this->mockBoard->status(
        $this->student,
    );

    expect($status['can_start'])
        ->toBeFalse()
        ->and($status['availability']->pluck('available')->all())
        ->toBe([0, 0, 0, 0, 0, 0]);
});

it('resumes one incomplete session after repeated Start requests', function () {
    $firstResponse = $this->actingAs($this->student)
        ->post(route('mock-board.start'));

    $session = AssessmentSession::query()
        ->where('user_id', $this->student->user_id)
        ->where('session_type', 'mock_board')
        ->firstOrFail();

    $firstResponse->assertRedirect(
        route('mock-board.take', $session),
    );

    $this->actingAs($this->student)
        ->post(route('mock-board.start'))
        ->assertRedirect(
            route('mock-board.take', $session),
        );

    expect(
        AssessmentSession::query()
            ->where('user_id', $this->student->user_id)
            ->where('session_type', 'mock_board')
            ->where('research_phase', 'post_test')
            ->count(),
    )
        ->toBe(1)
        ->and(
            AssessmentResponse::query()
                ->where('session_id', $session->session_id)
                ->count(),
        )
        ->toBe(60);
});

it('prevents another attempt after completion', function () {
    $session = $this->mockBoard->startPostTest(
        $this->student,
    );

    $this->mockBoard->submit($session, 'manual');

    $this->actingAs($this->student)
        ->from(route('mock-board.intro'))
        ->post(route('mock-board.start'))
        ->assertRedirect(route('mock-board.intro'))
        ->assertSessionHas(
            'error',
            fn (string $message): bool => str_contains(
                $message,
                'already completed',
            ),
        );

    expect(
        AssessmentSession::query()
            ->where('user_id', $this->student->user_id)
            ->where('session_type', 'mock_board')
            ->count(),
    )->toBe(1);
});

it('prevents a student from accessing another student session', function () {
    $session = $this->mockBoard->startPostTest(
        $this->student,
    );

    $response = AssessmentResponse::query()
        ->where('session_id', $session->session_id)
        ->firstOrFail();

    $choiceId = Question::query()
        ->with('choices')
        ->findOrFail($response->question_id)
        ->choices
        ->firstOrFail()
        ->choice_id;

    $otherStudent = createMockBoardStudentFor(
        $this->cohort,
    );

    $this->actingAs($otherStudent)
        ->get(route('mock-board.take', $session))
        ->assertForbidden();

    $this->actingAs($otherStudent)
        ->patchJson(
            route('mock-board.answer', [
                'session' => $session,
                'question' => $response->question_id,
            ]),
            ['selected_choice_id' => $choiceId],
        )
        ->assertForbidden();

    $this->actingAs($otherStudent)
        ->post(route('mock-board.submit', $session))
        ->assertForbidden();

    $this->actingAs($otherStudent)
        ->get(route('mock-board.results', $session))
        ->assertForbidden();
});

it('serves the same fixed Form B content in a stored per-student order', function () {
    $otherStudent = createMockBoardStudentFor(
        $this->cohort,
    );

    $firstSession = $this->mockBoard->startPostTest(
        $this->student,
    );

    $secondSession = $this->mockBoard->startPostTest(
        $otherStudent,
    );

    $expectedIds = Question::query()
        ->where(
            'research_form',
            Question::FORM_POST_TEST_B,
        )
        ->pluck('question_id')
        ->sort()
        ->values()
        ->all();

    $firstContent = collect(
        $firstSession->served_question_ids,
    )->sort()->values()->all();

    $secondContent = collect(
        $secondSession->served_question_ids,
    )->sort()->values()->all();

    $firstStoredOrder = AssessmentResponse::query()
        ->where('session_id', $firstSession->session_id)
        ->orderBy('item_position')
        ->pluck('question_id')
        ->all();

    $secondStoredOrder = AssessmentResponse::query()
        ->where('session_id', $secondSession->session_id)
        ->orderBy('item_position')
        ->pluck('question_id')
        ->all();

    expect($firstContent)
        ->toBe($expectedIds)
        ->and($secondContent)
        ->toBe($expectedIds)
        ->and($firstStoredOrder)
        ->toBe($firstSession->served_question_ids)
        ->and($secondStoredOrder)
        ->toBe($secondSession->served_question_ids);
});

it('rejects a selected choice from a different question', function () {
    $session = $this->mockBoard->startPostTest(
        $this->student,
    );

    $responses = AssessmentResponse::query()
        ->where('session_id', $session->session_id)
        ->orderBy('item_position')
        ->take(2)
        ->get();

    $otherChoice = Question::query()
        ->with('choices')
        ->findOrFail($responses[1]->question_id)
        ->choices
        ->firstOrFail();

    $this->actingAs($this->student)
        ->patchJson(
            route('mock-board.answer', [
                'session' => $session,
                'question' => $responses[0]->question_id,
            ]),
            [
                'selected_choice_id' => $otherChoice->choice_id,
            ],
        )
        ->assertStatus(422)
        ->assertJsonPath(
            'message',
            'The selected choice does not belong to this question.',
        );

    expect($responses[0]->fresh()->selected_choice_id)
        ->toBeNull();
});

it('rejects changes after expiration and scores only saved answers', function () {
    $session = $this->mockBoard->startPostTest(
        $this->student,
    );

    $responses = AssessmentResponse::query()
        ->where('session_id', $session->session_id)
        ->orderBy('item_position')
        ->take(2)
        ->get();

    $firstQuestion = Question::query()
        ->with('choices')
        ->findOrFail($responses[0]->question_id);

    $secondQuestion = Question::query()
        ->with('choices')
        ->findOrFail($responses[1]->question_id);

    $correctChoice = $firstQuestion->choices
        ->firstWhere('is_correct', true);

    $lateChoice = $secondQuestion->choices->first();

    $this->mockBoard->saveAnswer(
        $this->student,
        $session,
        $firstQuestion->question_id,
        $correctChoice->choice_id,
    );

    $session->forceFill([
        'expires_at' => now()->subSecond(),
    ])->save();

    $this->actingAs($this->student)
        ->patchJson(
            route('mock-board.answer', [
                'session' => $session,
                'question' => $secondQuestion->question_id,
            ]),
            ['selected_choice_id' => $lateChoice->choice_id],
        )
        ->assertStatus(409);

    $this->actingAs($this->student)
        ->post(route('mock-board.submit', $session))
        ->assertRedirect(
            route('mock-board.results', $session),
        );

    $result = $this->mockBoard->results(
        $session->fresh(),
    );

    expect($result['total'])
        ->toBe(60)
        ->and($result['answered'])
        ->toBe(1)
        ->and($result['unanswered'])
        ->toBe(59)
        ->and($result['correct'])
        ->toBe(1)
        ->and($session->fresh()->submission_reason)
        ->toBe('expired')
        ->and($responses[0]->fresh()->selected_choice_id)
        ->toBe($correctChoice->choice_id)
        ->and($responses[0]->fresh()->is_correct)
        ->toBeTrue()
        ->and($responses[1]->fresh()->selected_choice_id)
        ->toBeNull()
        ->and($responses[1]->fresh()->is_correct)
        ->toBeNull();

    expect(
        AssessmentResponse::query()
            ->where('session_id', $session->session_id)
            ->whereNull('selected_choice_id')
            ->whereNull('is_correct')
            ->count(),
    )->toBe(59);
});

it('does not change learning or gamification state when submitted', function () {
    $session = $this->mockBoard->startPostTest(
        $this->student,
    );

    $response = AssessmentResponse::query()
        ->where('session_id', $session->session_id)
        ->firstOrFail();

    $question = Question::query()
        ->with('choices')
        ->findOrFail($response->question_id);

    $correctChoice = $question->choices
        ->firstWhere('is_correct', true);

    $userBefore = $this->student->fresh();

    $userSnapshot = [
        'readiness' => (string) $userBefore
            ->predicted_readiness_pct,
        'total_xp' => $userBefore->total_xp,
        'current_level' => $userBefore->current_level,
        'streak_count' => $userBefore->streak_count,
        'last_active_date' => $userBefore
            ->last_active_date
            ?->toDateString(),
    ];

    $knowledgeSnapshot = UserKnowledgeState::query()
        ->where('user_id', $this->student->user_id)
        ->orderBy('competency_id')
        ->get()
        ->map(fn (UserKnowledgeState $state): array => [
            'competency_id' => $state->competency_id,
            'mastery' => (string) $state->current_mastery_p_l,
            'attempts' => $state->total_attempts,
            'correct' => $state->total_correct,
            'evaluated_at' => $state
                ->last_evaluated_at
                ?->toDateTimeString(),
        ])
        ->all();

    $countsBefore = [
        'xp' => XpTransaction::query()
            ->where('user_id', $this->student->user_id)
            ->count(),
        'badges' => UserBadge::query()
            ->where('user_id', $this->student->user_id)
            ->count(),
        'vault' => RxVault::query()
            ->where('user_id', $this->student->user_id)
            ->count(),
        'telemetry' => ResponseTelemetryLog::query()
            ->where('user_id', $this->student->user_id)
            ->count(),
    ];

    $this->mockBoard->saveAnswer(
        $this->student,
        $session,
        $question->question_id,
        $correctChoice->choice_id,
    );

    $this->mockBoard->submit($session, 'manual');

    $userAfter = $this->student->fresh();

    $afterSnapshot = [
        'readiness' => (string) $userAfter
            ->predicted_readiness_pct,
        'total_xp' => $userAfter->total_xp,
        'current_level' => $userAfter->current_level,
        'streak_count' => $userAfter->streak_count,
        'last_active_date' => $userAfter
            ->last_active_date
            ?->toDateString(),
    ];

    $knowledgeAfter = UserKnowledgeState::query()
        ->where('user_id', $this->student->user_id)
        ->orderBy('competency_id')
        ->get()
        ->map(fn (UserKnowledgeState $state): array => [
            'competency_id' => $state->competency_id,
            'mastery' => (string) $state->current_mastery_p_l,
            'attempts' => $state->total_attempts,
            'correct' => $state->total_correct,
            'evaluated_at' => $state
                ->last_evaluated_at
                ?->toDateTimeString(),
        ])
        ->all();

    $countsAfter = [
        'xp' => XpTransaction::query()
            ->where('user_id', $this->student->user_id)
            ->count(),
        'badges' => UserBadge::query()
            ->where('user_id', $this->student->user_id)
            ->count(),
        'vault' => RxVault::query()
            ->where('user_id', $this->student->user_id)
            ->count(),
        'telemetry' => ResponseTelemetryLog::query()
            ->where('user_id', $this->student->user_id)
            ->count(),
    ];

    expect($afterSnapshot)
        ->toBe($userSnapshot)
        ->and($knowledgeAfter)
        ->toBe($knowledgeSnapshot)
        ->and($countsAfter)
        ->toBe($countsBefore)
        ->and($session->fresh()->xp_awarded)
        ->toBe(0);
});

it('does not expose answer keys rationales or distractor feedback in results', function () {
    $session = $this->mockBoard->startPostTest(
        $this->student,
    );

    $response = AssessmentResponse::query()
        ->where('session_id', $session->session_id)
        ->firstOrFail();

    $question = Question::query()
        ->with('choices')
        ->findOrFail($response->question_id);

    $correctChoice = $question->choices
        ->firstWhere('is_correct', true);

    $wrongChoice = $question->choices
        ->firstWhere('is_correct', false);

    $correctMarker = 'SECRET-CORRECT-ANSWER-KEY';
    $rationaleMarker = 'SECRET-HYPERCORRECTION-RATIONALE';
    $distractorMarker = 'SECRET-DISTRACTOR-FEEDBACK';

    $question->forceFill([
        'hypercorrection_rationale' => $rationaleMarker,
    ])->save();

    $correctChoice->forceFill([
        'choice_text' => $correctMarker,
    ])->save();

    $wrongChoice->forceFill([
        'distractor_fallacy_note' => $distractorMarker,
    ])->save();

    $this->mockBoard->saveAnswer(
        $this->student,
        $session,
        $question->question_id,
        $correctChoice->choice_id,
    );

    $this->mockBoard->submit($session, 'manual');

    $this->actingAs($this->student)
        ->get(route('mock-board.results', $session))
        ->assertOk()
        ->assertViewHas(
            'result',
            fn (array $result): bool => $result['total'] === 60
                && $result['correct'] === 1,
        )
        ->assertDontSee($correctMarker, false)
        ->assertDontSee($rationaleMarker, false)
        ->assertDontSee($distractorMarker, false);
});

it('keeps every research-form question out of Practice Mode', function () {
    $practice = app(PracticeService::class);

    expect(
        fn () => $practice->startSession(
            $this->student,
            10,
        ),
    )->toThrow(RuntimeException::class);

    $competency = TosCompetency::query()->firstOrFail();

    $practiceQuestion = Question::query()->create([
        'competency_id' => $competency->competency_id,
        'question_text' => 'Practice-only control question',
        'hypercorrection_rationale' => null,
        'is_diagnostic_pool' => false,
        'research_form' => null,
        'form_position' => null,
        'cognitive_level' => null,
        'difficulty_index_p' => 0.500,
        'speed_flag_count' => 0,
        'is_active' => true,
    ]);

    $practiceSession = $practice->startSession(
        $this->student,
        10,
    );

    $selected = $practice->pickNextQuestion(
        $practiceSession,
    );

    expect($selected)
        ->not->toBeNull()
        ->and($selected->question_id)
        ->toBe($practiceQuestion->question_id)
        ->and($selected->research_form)
        ->toBeNull();
});

it('finalizes abandoned expired sessions through the command', function () {
    $expiredSession = $this->mockBoard->startPostTest(
        $this->student,
    );

    $expiredSession->forceFill([
        'expires_at' => now()->subMinute(),
    ])->save();

    $activeStudent = createMockBoardStudentFor(
        $this->cohort,
    );

    $activeSession = $this->mockBoard->startPostTest(
        $activeStudent,
    );

    $this->artisan('mock-board:finalize-expired')
        ->expectsOutput('Finalized: 1; failed: 0.')
        ->assertSuccessful();

    expect($expiredSession->fresh()->completed_at)
        ->not->toBeNull()
        ->and($expiredSession->fresh()->submission_reason)
        ->toBe('expired')
        ->and($activeSession->fresh()->completed_at)
        ->toBeNull()
        ->and($activeSession->fresh()->submission_reason)
        ->toBeNull();
});
