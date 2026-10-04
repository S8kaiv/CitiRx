<?php

use App\Models\AssessmentResponse;
use App\Models\AssessmentSession;
use App\Models\Cohort;
use App\Models\Question;
use App\Models\QuestionChoice;
use App\Models\ResponseTelemetryLog;
use App\Models\User;
use App\Services\ResearchAnalyticsService;
use App\Services\ResearchAttemptEligibilityService;
use Database\Seeders\ResearchQuestionSeeder;
use Database\Seeders\TosSeeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->withoutVite();

    config()->set(
        'citirx.research.pre_test_form_version',
        'pre_test_a_v1',
    );

    config()->set(
        'citirx.research.post_test_form_version',
        'post_test_b_v1',
    );
});

/**
 * @return Collection<int, Question>
 */
function analyticsFormQuestions(
    string $form,
): Collection {
    return Question::query()
        ->with([
            'choices',
            'competency.domain',
        ])
        ->where('research_form', $form)
        ->where('is_active', true)
        ->orderBy('form_position')
        ->get();
}

function analyticsChoice(
    Question $question,
    bool $correct,
): QuestionChoice {
    $choice = $question->choices->first(
        fn (QuestionChoice $choice): bool => $choice->is_correct === $correct,
    );

    if (! $choice) {
        throw new RuntimeException(
            'The analytics fixture question has no matching choice.',
        );
    }

    return $choice;
}

/**
 * Create a structurally valid 60-item Form A/Form B pair.
 *
 * @return array{pre: AssessmentSession, post: AssessmentSession}
 */
function createFullAnalyticsPair(
    User $user,
    int $preCorrectItems,
    int $postCorrectItems,
    int $postAnsweredItems,
    string $submissionReason,
    int $timelineOffsetMinutes = 0,
): array {
    if (
        $preCorrectItems < 0
        || $preCorrectItems > 60
        || $postCorrectItems < 0
        || $postCorrectItems > $postAnsweredItems
        || $postAnsweredItems < 0
        || $postAnsweredItems > 60
    ) {
        throw new InvalidArgumentException(
            'Invalid analytics fixture totals.',
        );
    }

    $preQuestions = analyticsFormQuestions(
        Question::FORM_PRE_TEST_A,
    );

    $postQuestions = analyticsFormQuestions(
        Question::FORM_POST_TEST_B,
    );

    if (
        $preQuestions->count() !== 60
        || $postQuestions->count() !== 60
    ) {
        throw new RuntimeException(
            'The analytics fixture requires two 60-item forms.',
        );
    }

    $base = now()->addMinutes(
        $timelineOffsetMinutes,
    );

    $pre = AssessmentSession::query()->create([
        'user_id' => $user->user_id,
        'session_type' => 'diagnostic',
        'research_phase' => 'pre_test',
        'exam_form_version' => 'pre_test_a_v1',

        'served_question_ids' => $preQuestions
            ->pluck('question_id')
            ->values()
            ->all(),

        'target_length' => 60,
        'total_items' => 60,
        'correct_items' => $preCorrectItems,

        'started_at' => $base->copy()->subHours(3),

        'completed_at' => $base->copy()->subHours(2),
    ]);

    $telemetryRows = [];

    foreach (
        $preQuestions->values() as $index => $question
    ) {
        $isCorrect =
            $index < $preCorrectItems;

        $choice = analyticsChoice(
            $question,
            $isCorrect,
        );

        $telemetryRows[] = [
            'log_id' => (string) Str::uuid(),
            'session_id' => $pre->session_id,
            'user_id' => $user->user_id,
            'question_id' => $question->question_id,
            'competency_id' => $question->competency_id,
            'selected_choice_id' => $choice->choice_id,
            'is_correct' => $isCorrect,
            'response_time_seconds' => 30,
            'item_position' => $index + 1,
            'is_speed_flagged' => false,
            'prior_p_l' => 0.5,
            'posterior_p_l' => 0.5,
            'created_at' => $base->copy()->subHours(2),
        ];
    }

    ResponseTelemetryLog::query()->insert(
        $telemetryRows,
    );

    $post = AssessmentSession::query()->create([
        'user_id' => $user->user_id,
        'session_type' => 'mock_board',
        'research_phase' => 'post_test',
        'exam_form_version' => 'post_test_b_v1',

        'served_question_ids' => $postQuestions
            ->pluck('question_id')
            ->values()
            ->all(),

        'target_length' => 60,
        'total_items' => 60,
        'correct_items' => $postCorrectItems,

        'readiness_snapshot_pct' => 50,
        'submission_reason' => $submissionReason,

        'started_at' => $base->copy()->subHour(),

        'completed_at' => $base,
    ]);

    $responseRows = [];

    foreach (
        $postQuestions->values() as $index => $question
    ) {
        $answered =
            $index < $postAnsweredItems;

        $isCorrect =
            $answered
            && $index < $postCorrectItems;

        $choice = $answered
            ? analyticsChoice(
                $question,
                $isCorrect,
            )
            : null;

        $responseRows[] = [
            'response_id' => (string) Str::uuid(),
            'session_id' => $post->session_id,
            'question_id' => $question->question_id,

            'selected_choice_id' => $choice?->choice_id,

            'item_position' => $index + 1,

            'is_correct' => $answered
                    ? $isCorrect
                    : null,

            'answered_at' => $answered
                    ? $base
                    : null,

            'created_at' => $base,
            'updated_at' => $base,
        ];
    }

    AssessmentResponse::query()->insert(
        $responseRows,
    );

    return compact('pre', 'post');
}

it('enforces full 60-item research eligibility', function () {
    $this->seed([
        TosSeeder::class,
        ResearchQuestionSeeder::class,
    ]);

    $cohort = Cohort::query()->create([
        'cohort_name' => 'Eligibility Test Cohort',

        'academic_year' => '2026-2027',
    ]);

    /*
     * 1. Fully valid complete pair.
     */
    $completeStudent = User::factory()->create([
        'role' => 'student',
        'cohort_id' => $cohort->cohort_id,
    ]);

    createFullAnalyticsPair(
        user: $completeStudent,
        preCorrectItems: 20,
        postCorrectItems: 40,
        postAnsweredItems: 60,
        submissionReason: 'manual',
    );

    /*
     * 2. Valid finalized pair with ten unanswered rows.
     * It belongs in sensitivity analysis but not primary analysis.
     */
    $incompleteStudent = User::factory()->create([
        'role' => 'student',
        'cohort_id' => $cohort->cohort_id,
    ]);

    createFullAnalyticsPair(
        user: $incompleteStudent,
        preCorrectItems: 30,
        postCorrectItems: 20,
        postAnsweredItems: 50,
        submissionReason: 'expired',
    );

    /*
     * 3. Missing response row: integrity failure.
     */
    $missingRowStudent = User::factory()->create([
        'role' => 'student',
        'cohort_id' => $cohort->cohort_id,
    ]);

    $missingRowPair = createFullAnalyticsPair(
        user: $missingRowStudent,
        preCorrectItems: 20,
        postCorrectItems: 20,
        postAnsweredItems: 60,
        submissionReason: 'manual',
    );

    AssessmentResponse::query()
        ->where(
            'session_id',
            $missingRowPair['post']->session_id,
        )
        ->where('item_position', 60)
        ->delete();

    /*
     * 4. Unexpected Form A version.
     */
    $wrongVersionStudent = User::factory()->create([
        'role' => 'student',
        'cohort_id' => $cohort->cohort_id,
    ]);

    $wrongVersionPair = createFullAnalyticsPair(
        user: $wrongVersionStudent,
        preCorrectItems: 20,
        postCorrectItems: 20,
        postAnsweredItems: 60,
        submissionReason: 'manual',
    );

    $wrongVersionPair['pre']->forceFill([
        'exam_form_version' => 'pre_test_a_old',
    ])->save();

    /*
     * 5. Post-test began before the pre-test was completed.
     */
    $chronologyStudent = User::factory()->create([
        'role' => 'student',
        'cohort_id' => $cohort->cohort_id,
    ]);

    $chronologyPair = createFullAnalyticsPair(
        user: $chronologyStudent,
        preCorrectItems: 20,
        postCorrectItems: 20,
        postAnsweredItems: 60,
        submissionReason: 'manual',
    );

    $chronologyPair['post']->forceFill([
        'started_at' => $chronologyPair['pre']
            ->completed_at
            ->copy()
            ->subMinute(),
    ])->save();

    /*
     * 6. Session says it contained only one item.
     */
    $wrongTotalStudent = User::factory()->create([
        'role' => 'student',
        'cohort_id' => $cohort->cohort_id,
    ]);

    $wrongTotalPair = createFullAnalyticsPair(
        user: $wrongTotalStudent,
        preCorrectItems: 20,
        postCorrectItems: 20,
        postAnsweredItems: 60,
        submissionReason: 'manual',
    );

    $wrongTotalPair['pre']->forceFill([
        'total_items' => 1,
    ])->save();

    /*
     * 7. Summary correct total disagrees with item rows.
     */
    $correctMismatchStudent = User::factory()->create([
        'role' => 'student',
        'cohort_id' => $cohort->cohort_id,
    ]);

    $correctMismatchPair = createFullAnalyticsPair(
        user: $correctMismatchStudent,
        preCorrectItems: 20,
        postCorrectItems: 20,
        postAnsweredItems: 60,
        submissionReason: 'manual',
    );

    $correctMismatchPair['post']->forceFill([
        'correct_items' => 21,
    ])->save();

    /*
     * 8. Response question does not match the served list.
     */
    $questionMismatchStudent = User::factory()->create([
        'role' => 'student',
        'cohort_id' => $cohort->cohort_id,
    ]);

    $questionMismatchPair = createFullAnalyticsPair(
        user: $questionMismatchStudent,
        preCorrectItems: 20,
        postCorrectItems: 20,
        postAnsweredItems: 60,
        submissionReason: 'manual',
    );

    $foreignQuestion = analyticsFormQuestions(
        Question::FORM_PRE_TEST_A,
    )->firstOrFail();

    AssessmentResponse::query()
        ->where(
            'session_id',
            $questionMismatchPair['post']->session_id,
        )
        ->where('item_position', 1)
        ->update([
            'question_id' => $foreignQuestion->question_id,

            'selected_choice_id' => null,
            'is_correct' => null,
            'answered_at' => null,
        ]);

    /*
     * 9–10. Two valid pairs for one user.
     * The earlier pair is selected and the later one is excluded.
     */
    $duplicateStudent = User::factory()->create([
        'role' => 'student',
        'cohort_id' => $cohort->cohort_id,
    ]);

    createFullAnalyticsPair(
        user: $duplicateStudent,
        preCorrectItems: 15,
        postCorrectItems: 30,
        postAnsweredItems: 60,
        submissionReason: 'manual',
        timelineOffsetMinutes: -240,
    );

    createFullAnalyticsPair(
        user: $duplicateStudent,
        preCorrectItems: 25,
        postCorrectItems: 35,
        postAnsweredItems: 60,
        submissionReason: 'manual',
    );

    $dataset = app(
        ResearchAttemptEligibilityService::class,
    )->dataset(
        preTestVersion: 'pre_test_a_v1',
        postTestVersion: 'post_test_b_v1',
    );

    $report = app(
        ResearchAnalyticsService::class,
    )->fullReport();

    /*
     * Eligible pairs:
     *
     * - complete student
     * - incomplete student
     * - duplicate student, using the earliest pair
     */
    expect($report['funnel']['paired_finalized'])
        ->toBe(3)
        ->and($report['funnel']['paired_complete'])
        ->toBe(2)
        ->and($report['primary']['n_pairs'])
        ->toBe(2)
        ->and($report['sensitivity']['n_pairs'])
        ->toBe(3)

        ->and(
            $report['funnel']['excluded_pre_wrong_version'],
        )
        ->toBe(1)

        ->and(
            $report['funnel']['excluded_pre_integrity'],
        )
        ->toBe(1)

        ->and(
            $report['funnel']['excluded_post_integrity'],
        )
        ->toBe(3)

        ->and(
            $report['funnel']['excluded_duplicate_pre'],
        )
        ->toBe(1)

        ->and(
            $report['funnel']['excluded_duplicate_post'],
        )
        ->toBe(1)

        ->and(
            $report['funnel']['excluded_invalid_chronology'],
        )
        ->toBe(1);

    /*
     * The incomplete student's post-test score remains 20/60.
     * It must not become 20/50.
     */
    $incompletePair = $dataset['pairs']
        ->firstWhere(
            'user_id',
            $incompleteStudent->user_id,
        );

    expect($incompletePair)
        ->not->toBeNull()
        ->and($incompletePair['answered_items'])
        ->toBe(50)
        ->and($incompletePair['expected_items'])
        ->toBe(60)
        ->and($incompletePair['is_complete'])
        ->toBeFalse()
        ->and(round($incompletePair['post_score'], 4))
        ->toBe(33.3333);

    /*
     * A missing database row is not treated as an unanswered item.
     * The whole post-test is excluded.
     */
    expect(
        $dataset['pairs']->contains(
            fn (array $pair): bool => $pair['user_id']
                === $missingRowStudent->user_id,
        ),
    )->toBeFalse();
});

it('does not expose participant identifiers', function () {
    $this->seed([
        TosSeeder::class,
        ResearchQuestionSeeder::class,
    ]);

    $cohort = Cohort::query()->create([
        'cohort_name' => 'Privacy Test Cohort',
        'academic_year' => '2026-2027',
    ]);

    $student = User::factory()->create([
        'first_name' => 'ZZZPrivacyFirst',
        'last_name' => 'ZZZPrivacyLast',
        'email' => 'zzzprivacy@example.test',
        'student_id' => 'ZZZ-PRIVATE-001',
        'role' => 'student',
        'cohort_id' => $cohort->cohort_id,
    ]);

    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    createFullAnalyticsPair(
        user: $student,
        preCorrectItems: 20,
        postCorrectItems: 40,
        postAnsweredItems: 60,
        submissionReason: 'manual',
    );

    $report = app(
        ResearchAnalyticsService::class,
    )->fullReport();

    expect($report['funnel']['paired_finalized'])
        ->toBe(1);

    $serializedReport = json_encode(
        $report,
        JSON_THROW_ON_ERROR,
    );

    $response = $this
        ->actingAs($admin)
        ->get(
            route(
                'admin.research-analytics.index',
            ),
        )
        ->assertOk();

    foreach ([
        $student->first_name,
        $student->last_name,
        $student->email,
        $student->student_id,
        $student->user_id,
    ] as $identifier) {
        $response->assertDontSee(
            $identifier,
            false,
        );

        expect($serializedReport)
            ->not->toContain($identifier);
    }
});
