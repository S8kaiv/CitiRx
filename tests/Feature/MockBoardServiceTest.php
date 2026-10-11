<?php

use App\Models\AssessmentResponse;
use App\Models\AssessmentSession;
use App\Models\Cohort;
use App\Models\Question;
use App\Models\TosCompetency;
use App\Models\User;
use App\Models\UserKnowledgeState;
use App\Services\MockBoardService;
use Database\Seeders\ResearchQuestionSeeder;
use Database\Seeders\TosSeeder;

beforeEach(function () {
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

    $this->seed(TosSeeder::class);
    $this->seed(ResearchQuestionSeeder::class);

    $this->cohort = Cohort::query()->create([
        'cohort_name' => 'Mock Board Service Test',
        'academic_year' => '2026-2027',
        'target_phle_date' => now()->addYear()->toDateString(),
        'post_test_opens_at' => now()->subHour(),
        'post_test_closes_at' => now()->addHour(),
    ]);

    $this->user = User::factory()->create([
        'cohort_id' => $this->cohort->cohort_id,
        'is_diagnostic_completed' => true,
        'predicted_readiness_pct' => 50,
        'total_xp' => 125,
        'streak_count' => 4,
    ]);

    TosCompetency::query()
        ->get()
        ->each(function (TosCompetency $competency): void {
            UserKnowledgeState::query()->create([
                'user_id' => $this->user->user_id,
                'competency_id' => $competency->competency_id,
                'current_mastery_p_l' => 0.5000,
                'total_attempts' => 2,
                'total_correct' => 1,
                'last_evaluated_at' => now(),
            ]);
        });

    $this->service = app(MockBoardService::class);
});

it('reports that an eligible student can start Form B', function () {
    $status = $this->service->status($this->user);

    expect($status['can_start'])
        ->toBeTrue()
        ->and($status['reason'])
        ->toBeNull()
        ->and($status['availability'])
        ->toHaveCount(6)
        ->and($status['availability']->pluck('available')->all())
        ->toBe([10, 10, 10, 10, 10, 10])
        ->and($status['duration_minutes'])
        ->toBe(72)
        ->and($status['form_version'])
        ->toBe('post_test_b_v1');
});

it('blocks a student who has not completed the diagnostic', function () {
    $this->user->forceFill([
        'is_diagnostic_completed' => false,
    ])->save();

    $status = $this->service->status($this->user->fresh());

    expect($status['can_start'])
        ->toBeFalse()
        ->and($status['reason'])
        ->toContain('Complete the diagnostic');

    expect(
        fn () => $this->service->startPostTest(
            $this->user->fresh(),
        ),
    )->toThrow(LogicException::class);
});

it('blocks a student outside the cohort post-test window', function () {
    $this->cohort->forceFill([
        'post_test_opens_at' => now()->subHours(2),
        'post_test_closes_at' => now()->subHour(),
    ])->save();

    $status = $this->service->status(
        $this->user->fresh(),
    );

    expect($status['can_start'])
        ->toBeFalse()
        ->and($status['reason'])
        ->toContain('not currently available');
});

it('creates one session and exactly 60 empty response rows', function () {
    $session = $this->service->startPostTest($this->user);

    $responses = AssessmentResponse::query()
        ->where('session_id', $session->session_id)
        ->orderBy('item_position')
        ->get();

    expect($session->session_type)
        ->toBe('mock_board')
        ->and($session->research_phase)
        ->toBe('post_test')
        ->and($session->target_length)
        ->toBe(60)
        ->and($session->total_items)
        ->toBe(60)
        ->and($session->correct_items)
        ->toBe(0)
        ->and($session->xp_awarded)
        ->toBe(0)
        ->and($session->served_question_ids)
        ->toHaveCount(60)
        ->and($session->domain_mastery_snapshot)
        ->toHaveCount(6)
        ->and($session->expires_at)
        ->not->toBeNull()
        ->and($responses)
        ->toHaveCount(60)
        ->and($responses->pluck('item_position')->all())
        ->toBe(range(1, 60))
        ->and($responses->whereNotNull('selected_choice_id'))
        ->toHaveCount(0)
        ->and($responses->whereNotNull('is_correct'))
        ->toHaveCount(0);
});

it('returns the existing attempt instead of creating a duplicate', function () {
    $first = $this->service->startPostTest($this->user);
    $second = $this->service->startPostTest($this->user->fresh());

    expect($second->session_id)
        ->toBe($first->session_id)
        ->and(
            AssessmentSession::query()
                ->where('user_id', $this->user->user_id)
                ->where('session_type', 'mock_board')
                ->where('research_phase', 'post_test')
                ->count(),
        )
        ->toBe(1)
        ->and(
            AssessmentResponse::query()
                ->where('session_id', $first->session_id)
                ->count(),
        )
        ->toBe(60);
});

it('saves and clears one answer without scoring it early', function () {
    $session = $this->service->startPostTest($this->user);

    $response = AssessmentResponse::query()
        ->where('session_id', $session->session_id)
        ->firstOrFail();

    $question = Question::query()
        ->with('choices')
        ->findOrFail($response->question_id);

    $choice = $question->choices->first();

    $this->service->saveAnswer(
        $this->user,
        $session,
        $question->question_id,
        $choice->choice_id,
    );

    $response->refresh();

    expect($response->selected_choice_id)
        ->toBe($choice->choice_id)
        ->and($response->answered_at)
        ->not->toBeNull()
        ->and($response->is_correct)
        ->toBeNull();

    $this->service->saveAnswer(
        $this->user,
        $session,
        $question->question_id,
        null,
    );

    $response->refresh();

    expect($response->selected_choice_id)
        ->toBeNull()
        ->and($response->answered_at)
        ->toBeNull()
        ->and($response->is_correct)
        ->toBeNull();
});

it('rejects a choice that belongs to a different question', function () {
    $session = $this->service->startPostTest($this->user);

    $responses = AssessmentResponse::query()
        ->where('session_id', $session->session_id)
        ->orderBy('item_position')
        ->take(2)
        ->get();

    $otherChoice = Question::query()
        ->with('choices')
        ->findOrFail($responses[1]->question_id)
        ->choices
        ->first();

    expect(
        fn () => $this->service->saveAnswer(
            $this->user,
            $session,
            $responses[0]->question_id,
            $otherChoice->choice_id,
        ),
    )->toThrow(InvalidArgumentException::class);
});

it('rejects autosave from a different student', function () {
    $session = $this->service->startPostTest($this->user);

    $otherUser = User::factory()->create([
        'cohort_id' => $this->cohort->cohort_id,
        'is_diagnostic_completed' => true,
    ]);

    $response = AssessmentResponse::query()
        ->where('session_id', $session->session_id)
        ->firstOrFail();

    $choice = Question::query()
        ->with('choices')
        ->findOrFail($response->question_id)
        ->choices
        ->first();

    expect(
        fn () => $this->service->saveAnswer(
            $otherUser,
            $session,
            $response->question_id,
            $choice->choice_id,
        ),
    )->toThrow(LogicException::class);
});

it('scores answered rows while keeping unanswered rows in the denominator', function () {
    $session = $this->service->startPostTest($this->user);

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

    $correctChoice = $firstQuestion->choices->firstWhere(
        'is_correct',
        true,
    );

    $wrongChoice = $secondQuestion->choices->firstWhere(
        'is_correct',
        false,
    );

    $masteryBefore = UserKnowledgeState::query()
        ->where('user_id', $this->user->user_id)
        ->orderBy('competency_id')
        ->pluck('current_mastery_p_l', 'competency_id')
        ->all();

    $userBefore = $this->user->fresh();

    $this->service->saveAnswer(
        $this->user,
        $session,
        $firstQuestion->question_id,
        $correctChoice->choice_id,
    );

    $this->service->saveAnswer(
        $this->user,
        $session,
        $secondQuestion->question_id,
        $wrongChoice->choice_id,
    );

    $result = $this->service->submit(
        $session,
        'manual',
    );

    $session->refresh();
    $userAfter = $this->user->fresh();

    $masteryAfter = UserKnowledgeState::query()
        ->where('user_id', $this->user->user_id)
        ->orderBy('competency_id')
        ->pluck('current_mastery_p_l', 'competency_id')
        ->all();

    expect($result['total'])
        ->toBe(60)
        ->and($result['answered'])
        ->toBe(2)
        ->and($result['unanswered'])
        ->toBe(58)
        ->and($result['correct'])
        ->toBe(1)
        ->and($result['per_domain'])
        ->toHaveCount(6)
        ->and($session->completed_at)
        ->not->toBeNull()
        ->and($session->submission_reason)
        ->toBe('manual')
        ->and($session->correct_items)
        ->toBe(1)
        ->and($session->xp_awarded)
        ->toBe(0)
        ->and($userAfter->total_xp)
        ->toBe($userBefore->total_xp)
        ->and($userAfter->streak_count)
        ->toBe($userBefore->streak_count)
        ->and($userAfter->predicted_readiness_pct)
        ->toBe($userBefore->predicted_readiness_pct)
        ->and($masteryAfter)
        ->toBe($masteryBefore)
        ->and(
            AssessmentResponse::query()
                ->where('session_id', $session->session_id)
                ->whereNull('selected_choice_id')
                ->whereNull('is_correct')
                ->count(),
        )
        ->toBe(58);
});

it('submits an expired attempt and prevents further autosave', function () {
    $session = $this->service->startPostTest($this->user);

    $session->forceFill([
        'expires_at' => now()->subSecond(),
    ])->save();

    $response = AssessmentResponse::query()
        ->where('session_id', $session->session_id)
        ->firstOrFail();

    $choice = Question::query()
        ->with('choices')
        ->findOrFail($response->question_id)
        ->choices
        ->first();

    expect(
        fn () => $this->service->saveAnswer(
            $this->user,
            $session->fresh(),
            $response->question_id,
            $choice->choice_id,
        ),
    )->toThrow(LogicException::class);

    $result = $this->service->submit(
        $session->fresh(),
        'expired',
    );

    expect($result['total'])
        ->toBe(60)
        ->and($result['answered'])
        ->toBe(0)
        ->and($result['unanswered'])
        ->toBe(60)
        ->and($session->fresh()->submission_reason)
        ->toBe('expired');
});

it('returns the saved result when submit is called again', function () {
    $session = $this->service->startPostTest($this->user);

    $first = $this->service->submit($session, 'manual');
    $completedAt = $session->fresh()->completed_at;

    $second = $this->service->submit(
        $session->fresh(),
        'manual',
    );

    expect($second)
        ->toBe($first)
        ->and($session->fresh()->completed_at->equalTo($completedAt))
        ->toBeTrue()
        ->and(
            AssessmentResponse::query()
                ->where('session_id', $session->session_id)
                ->count(),
        )
        ->toBe(60);
});

it('does not expose unvalidated Form B when the override is disabled', function () {
    config()->set(
        'citirx.research.allow_unvalidated_forms',
        false,
    );

    $status = $this->service->status($this->user);

    expect($status['can_start'])
        ->toBeFalse()
        ->and($status['reason'])
        ->toContain('Form B is incomplete')
        ->and($status['availability']->pluck('available')->all())
        ->toBe([0, 0, 0, 0, 0, 0]);
});

it(
    'finalizes expired sessions without finalizing active sessions',
    function () {
        /*
         * Create an expired attempt for the primary test user.
         */
        $expiredSession =
            $this->service->startPostTest(
                $this->user,
            );

        $expiredSession->forceFill([
            'expires_at' => now()->subMinute(),
        ])->save();

        $xpBefore = $this->user
            ->fresh()
            ->total_xp;

        /*
         * Create another eligible student with an active attempt.
         */
        $activeUser = User::factory()->create([
            'cohort_id' => $this->cohort->cohort_id,

            'is_diagnostic_completed' => true,
            'predicted_readiness_pct' => 50,
            'total_xp' => 25,
            'streak_count' => 2,
        ]);

        TosCompetency::query()
            ->get()
            ->each(function (
                TosCompetency $competency,
            ) use ($activeUser): void {
                UserKnowledgeState::query()
                    ->create([
                        'user_id' => $activeUser->user_id,

                        'competency_id' => $competency
                            ->competency_id,

                        'current_mastery_p_l' => 0.5000,

                        'total_attempts' => 2,
                        'total_correct' => 1,
                        'last_evaluated_at' => now(),
                    ]);
            });

        $activeSession =
            $this->service->startPostTest(
                $activeUser,
            );

        /*
         * Run the scheduled command directly.
         */
        $this->artisan(
            'mock-board:finalize-expired',
        )
            ->expectsOutput(
                'Finalized: 1; failed: 0.',
            )
            ->assertSuccessful();

        $expiredSession->refresh();
        $activeSession->refresh();

        /*
         * The expired session must be finalized.
         */
        expect(
            $expiredSession->completed_at,
        )
            ->not->toBeNull()
            ->and(
                $expiredSession
                    ->submission_reason,
            )
            ->toBe('expired')
            ->and(
                $expiredSession->total_items,
            )
            ->toBe(60)
            ->and(
                $expiredSession->correct_items,
            )
            ->toBe(0)
            ->and(
                $expiredSession->xp_awarded,
            )
            ->toBe(0);

        /*
         * The active session must remain untouched.
         */
        expect(
            $activeSession->completed_at,
        )
            ->toBeNull()
            ->and(
                $activeSession
                    ->submission_reason,
            )
            ->toBeNull();

        /*
         * No learning or reward side effect is allowed.
         */
        expect(
            $this->user
                ->fresh()
                ->total_xp,
        )->toBe($xpBefore);

        /*
         * All unanswered expired responses remain null.
         */
        expect(
            AssessmentResponse::query()
                ->where(
                    'session_id',
                    $expiredSession->session_id,
                )
                ->whereNull(
                    'selected_choice_id',
                )
                ->whereNull('is_correct')
                ->count(),
        )->toBe(60);
    },
);
