<?php

use App\Models\AssessmentResponse;
use App\Models\AssessmentSession;
use App\Models\Cohort;
use App\Models\Question;
use App\Models\QuestionChoice;
use App\Models\ResponseTelemetryLog;
use App\Models\TosCompetency;
use App\Models\TosDomain;
use App\Models\User;
use App\Services\ResearchAnalyticsService;

beforeEach(function () {
    $this->withoutVite();

    config()->set('citirx.research.pre_test_form_version', 'pre_test_a_v1');
    config()->set('citirx.research.post_test_form_version', 'post_test_b_v1');
});

function createResearchAnalyticsPair(
    User $user,
    Question $question,
    QuestionChoice $correctChoice,
    QuestionChoice $wrongChoice,
    bool $preCorrect,
    bool $postCorrect,
    bool $postComplete,
    string $submissionReason,
): void {
    $pre = AssessmentSession::query()->create([
        'user_id' => $user->user_id,
        'session_type' => 'diagnostic',
        'research_phase' => 'pre_test',
        'exam_form_version' => 'pre_test_a_v1',
        'served_question_ids' => [$question->question_id],
        'target_length' => 1,
        'total_items' => 1,
        'correct_items' => $preCorrect ? 1 : 0,
        'started_at' => now()->subHours(2),
        'completed_at' => now()->subHour(),
    ]);

    ResponseTelemetryLog::query()->create([
        'session_id' => $pre->session_id,
        'user_id' => $user->user_id,
        'question_id' => $question->question_id,
        'competency_id' => $question->competency_id,
        'selected_choice_id' => $preCorrect
            ? $correctChoice->choice_id
            : $wrongChoice->choice_id,
        'is_correct' => $preCorrect,
        'response_time_seconds' => 30,
        'item_position' => 1,
        'is_speed_flagged' => false,
        'prior_p_l' => 0.5,
        'posterior_p_l' => 0.5,
    ]);

    $post = AssessmentSession::query()->create([
        'user_id' => $user->user_id,
        'session_type' => 'mock_board',
        'research_phase' => 'post_test',
        'exam_form_version' => 'post_test_b_v1',
        'served_question_ids' => [$question->question_id],
        'target_length' => 1,
        'total_items' => 1,
        'correct_items' => $postCorrect && $postComplete ? 1 : 0,
        'readiness_snapshot_pct' => 50,
        'submission_reason' => $submissionReason,
        'started_at' => now()->subMinutes(30),
        'completed_at' => now(),
    ]);

    AssessmentResponse::query()->create([
        'session_id' => $post->session_id,
        'question_id' => $question->question_id,
        'selected_choice_id' => $postComplete
            ? ($postCorrect ? $correctChoice->choice_id : $wrongChoice->choice_id)
            : null,
        'item_position' => 1,
        'is_correct' => $postComplete ? $postCorrect : null,
        'answered_at' => $postComplete ? now() : null,
    ]);
}

it('calculates the known signed-rank example', function () {
    $result = app(ResearchAnalyticsService::class)
        ->wilcoxonSignedRank([1, 2, -3, 4, 5]);

    expect($result['n'])->toBe(5)
        ->and($result['w_plus'])->toBe(12.0)
        ->and($result['w_minus'])->toBe(3.0)
        ->and($result['w_statistic'])->toBe(3.0)
        ->and($result['p_value'])->toBe(0.3125)
        ->and($result['method'])->toBe('exact two-sided signed-rank distribution');
});

it('assigns correct midranks when absolute differences are tied', function () {
    $result = app(ResearchAnalyticsService::class)
        ->wilcoxonSignedRank([1, 1, -1, 2, -2]);

    expect($result['w_plus'])->toBe(8.5)
        ->and($result['w_minus'])->toBe(6.5)
        ->and($result['w_statistic'])->toBe(6.5)
        ->and($result['method'])->toContain('tie correction');
});

it('applies continuity correction symmetrically', function () {
    $service = app(ResearchAnalyticsService::class);
    $positive = $service->wilcoxonSignedRank([1, 1, -1, 2, -2]);
    $negative = $service->wilcoxonSignedRank([-1, -1, 1, -2, 2]);

    expect($positive['z'])
        ->toBe(round(-$negative['z'], 6))
        ->and($positive['p_value'])
        ->toBe($negative['p_value']);
});

it('handles zero differences and empty readiness data', function () {
    $service = app(ResearchAnalyticsService::class);
    $wilcoxon = $service->wilcoxonSignedRank([0, 0, 0]);
    $readiness = $service->readinessAccuracy();

    expect($wilcoxon['n'])->toBe(0)
        ->and($wilcoxon['p_value'])->toBe(1.0)
        ->and($readiness['n'])->toBe(0)
        ->and($readiness['mae'])->toBeNull()
        ->and($readiness['rmse'])->toBeNull();
});

it('calculates descriptive statistics with sample standard deviation', function () {
    $result = app(ResearchAnalyticsService::class)
        ->descriptives([10, 20, 30, 40, 50]);

    expect($result['n'])->toBe(5)
        ->and($result['mean'])->toBe(30.0)
        ->and($result['median'])->toBe(30.0)
        ->and($result['sd'])->toBe(15.81)
        ->and($result['min'])->toBe(10.0)
        ->and($result['max'])->toBe(50.0);
});

it('separates complete cases from finalized incomplete attempts', function () {
    $cohort = Cohort::query()->create([
        'cohort_name' => 'Research Analytics Test Cohort',
        'academic_year' => '2026-2027',
    ]);
    $domain = TosDomain::query()->create([
        'domain_number' => 1,
        'domain_name' => 'Test Domain',
        'prc_weight_percentage' => 10.00,
    ]);
    $competency = TosCompetency::query()->create([
        'domain_id' => $domain->domain_id,
        'competency_code' => 'TEST-01',
        'title' => 'Test Competency',
        'order_index' => 1,
        'bkt_transition_p_t' => 0.1,
        'tos_weight_percentage' => 100,
    ]);
    $question = Question::query()->create([
        'competency_id' => $competency->competency_id,
        'question_text' => 'Analytics fixture question',
        'hypercorrection_rationale' => 'Fixture rationale',
        'is_diagnostic_pool' => true,
        'is_active' => true,
        'research_form' => Question::FORM_PRE_TEST_A,
        'form_position' => 1,
        'cognitive_level' => Question::COGNITIVE_KNOWLEDGE,
    ]);
    $correctChoice = QuestionChoice::query()->create([
        'question_id' => $question->question_id,
        'choice_letter' => 'A',
        'choice_text' => 'Correct',
        'is_correct' => true,
    ]);
    $wrongChoice = QuestionChoice::query()->create([
        'question_id' => $question->question_id,
        'choice_letter' => 'B',
        'choice_text' => 'Incorrect',
        'is_correct' => false,
    ]);
    $completeStudent = User::factory()->create([
        'cohort_id' => $cohort->cohort_id,
    ]);
    $expiredStudent = User::factory()->create([
        'cohort_id' => $cohort->cohort_id,
    ]);

    createResearchAnalyticsPair(
        $completeStudent,
        $question,
        $correctChoice,
        $wrongChoice,
        false,
        true,
        true,
        'manual',
    );
    createResearchAnalyticsPair(
        $expiredStudent,
        $question,
        $correctChoice,
        $wrongChoice,
        true,
        false,
        false,
        'expired',
    );

    $report = app(ResearchAnalyticsService::class)->fullReport();

    expect($report['funnel']['paired_finalized'])->toBe(2)
        ->and($report['funnel']['paired_complete'])->toBe(1)
        ->and($report['primary']['n_pairs'])->toBe(1)
        ->and($report['sensitivity']['n_pairs'])->toBe(2)
        ->and($report['primary']['per_domain'][0]['n_pairs'])->toBe(1)
        ->and($report['breakdown']['manual_complete'])->toBe(1)
        ->and($report['breakdown']['expired_incomplete'])->toBe(1)
        ->and($report['readiness']['primary']['mae'])->toBe(50.0)
        ->and($report['cohorts'][0]['paired_complete'])->toBe(1);
});

it('allows only administrators to view research analytics', function () {
    $student = User::factory()->create(['role' => 'student']);
    $admin = User::factory()->create(['role' => 'admin']);

    $this->get(route('admin.research-analytics.index'))
        ->assertRedirect(route('login'));

    $this->actingAs($student)
        ->get(route('admin.research-analytics.index'))
        ->assertForbidden();

    $this->actingAs($admin)
        ->get(route('admin.research-analytics.index'))
        ->assertOk()
        ->assertSee('Research Analytics');
});
