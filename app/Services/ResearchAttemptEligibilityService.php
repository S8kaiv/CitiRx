<?php

namespace App\Services;

use App\Models\Question;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class ResearchAttemptEligibilityService
{
    /**
     * Duplicate-attempt policy:
     *
     * - Use the earliest eligible completed pre-test.
     * - Use the earliest eligible started post-test.
     * - Exclude later eligible attempts as duplicates.
     * - The selected pre-test must be completed before the selected
     *   post-test begins.
     *
     * Missing or unexpected form versions are excluded and counted.
     *
     * @return array<string, mixed>
     */
    public function dataset(
        string $preTestVersion,
        string $postTestVersion,
    ): array {
        $preCandidates = $this->candidateSessions(
            sessionType: 'diagnostic',
            researchPhase: 'pre_test',
        );

        $postCandidates = $this->candidateSessions(
            sessionType: 'mock_board',
            researchPhase: 'post_test',
        );

        [$eligiblePre, $preStats] = $this->eligibleSessions(
            candidates: $preCandidates,
            phase: 'pre_test',
            expectedVersion: $preTestVersion,
            expectedForm: Question::FORM_PRE_TEST_A,
        );

        [$eligiblePost, $postStats] = $this->eligibleSessions(
            candidates: $postCandidates,
            phase: 'post_test',
            expectedVersion: $postTestVersion,
            expectedForm: Question::FORM_POST_TEST_B,
        );

        /*
         * The first valid completed pre-test is the research baseline.
         */
        $preSessions = $eligiblePre
            ->sortBy(
                fn (object $session): string => $session->completed_at.'|'.$session->session_id,
            )
            ->groupBy('user_id')
            ->map(
                fn (Collection $sessions): object => $sessions->first(),
            );

        /*
         * The first valid started post-test is the research outcome.
         */
        $postSessions = $eligiblePost
            ->sortBy(
                fn (object $session): string => $session->started_at.'|'.$session->session_id,
            )
            ->groupBy('user_id')
            ->map(
                fn (Collection $sessions): object => $sessions->first(),
            );

        $duplicatePre = max(
            0,
            $eligiblePre->count() - $preSessions->count(),
        );

        $duplicatePost = max(
            0,
            $eligiblePost->count() - $postSessions->count(),
        );

        $pairs = collect();
        $invalidChronology = 0;

        $pairedUserIds = $preSessions
            ->keys()
            ->intersect($postSessions->keys());

        foreach ($pairedUserIds as $userId) {
            $pre = $preSessions->get($userId);
            $post = $postSessions->get($userId);

            if (! $this->precedesPostTest($pre, $post)) {
                $invalidChronology++;

                continue;
            }

            $pairs->push([
                'user_id' => (string) $userId,
                'cohort_id' => $post->cohort_id,
                'cohort_name' => $post->cohort_name
                    ?? 'Unassigned',
                'academic_year' => $post->academic_year,

                'pre_session_id' => $pre->session_id,
                'post_session_id' => $post->session_id,

                /*
                 * Both scores always use the fixed denominator of 60.
                 */
                'pre_score' => $this->percentage(
                    (int) $pre->correct_items,
                ),

                'post_score' => $this->percentage(
                    (int) $post->correct_items,
                ),

                'readiness_snapshot_pct' => $post->readiness_snapshot_pct === null
                        ? null
                        : (float) $post->readiness_snapshot_pct,

                'submission_reason' => $post->submission_reason,

                'answered_items' => (int) $post->answered_items,

                'expected_items' => ResearchFormService::TOTAL_ITEMS,

                /*
                 * A valid unanswered row remains part of the
                 * denominator but prevents complete-case inclusion.
                 */
                'is_complete' => (int) $post->answered_items
                    === ResearchFormService::TOTAL_ITEMS,

                'pre_domain_scores' => $pre->domain_scores,

                'post_domain_scores' => $post->domain_scores,
            ]);
        }

        return [
            'preSessions' => $preSessions,
            'postSessions' => $postSessions,
            'pairs' => $pairs->values(),

            'eligibility' => [
                'pre_candidates' => $preCandidates->count(),

                'post_candidates' => $postCandidates->count(),

                'pre_selected' => $preSessions->count(),

                'post_selected' => $postSessions->count(),

                'pre_missing_version' => $preStats['missing_version'],

                'pre_wrong_version' => $preStats['wrong_version'],

                'post_missing_version' => $postStats['missing_version'],

                'post_wrong_version' => $postStats['wrong_version'],

                'pre_integrity_failures' => $preStats['integrity_failures'],

                'post_integrity_failures' => $postStats['integrity_failures'],

                'duplicate_pre_attempts' => $duplicatePre,

                'duplicate_post_attempts' => $duplicatePost,

                'invalid_chronology' => $invalidChronology,

                /*
                 * These are useful for debugging but contain only
                 * reason names and counts—never participant details.
                 */
                'pre_integrity_reasons' => $preStats['integrity_reasons'],

                'post_integrity_reasons' => $postStats['integrity_reasons'],
            ],
        ];
    }

    /**
     * Load all finalized candidate sessions, including sessions with
     * missing or unexpected versions. Those sessions must be counted
     * as exclusions rather than silently disappearing.
     */
    private function candidateSessions(
        string $sessionType,
        string $researchPhase,
    ): Collection {
        return DB::table('assessment_sessions as s')
            ->join(
                'users as u',
                'u.user_id',
                '=',
                's.user_id',
            )
            ->leftJoin(
                'cohorts as c',
                'c.cohort_id',
                '=',
                'u.cohort_id',
            )
            ->where('u.role', 'student')
            ->where('s.session_type', $sessionType)
            ->where('s.research_phase', $researchPhase)
            ->whereNotNull('s.completed_at')
            ->orderBy('s.completed_at')
            ->orderBy('s.session_id')
            ->get([
                's.session_id',
                's.user_id',
                's.exam_form_version',
                's.served_question_ids',
                's.target_length',
                's.total_items',
                's.correct_items',
                's.readiness_snapshot_pct',
                's.submission_reason',
                's.started_at',
                's.completed_at',
                'u.cohort_id',
                'c.cohort_name',
                'c.academic_year',
            ]);
    }

    /**
     * @return array{0: Collection, 1: array<string, mixed>}
     */
    private function eligibleSessions(
        Collection $candidates,
        string $phase,
        string $expectedVersion,
        string $expectedForm,
    ): array {
        $stats = [
            'missing_version' => 0,
            'wrong_version' => 0,
            'integrity_failures' => 0,
            'integrity_reasons' => [],
        ];

        $versionMatches = collect();

        foreach ($candidates as $session) {
            $actualVersion = trim(
                (string) ($session->exam_form_version ?? ''),
            );

            if ($actualVersion === '') {
                $stats['missing_version']++;

                continue;
            }

            if ($actualVersion !== $expectedVersion) {
                $stats['wrong_version']++;

                continue;
            }

            $versionMatches->push($session);
        }

        $rowsBySession = $this->responseRows(
            phase: $phase,
            sessionIds: $versionMatches
                ->pluck('session_id')
                ->values(),
        )->groupBy('session_id');

        $eligible = collect();

        foreach ($versionMatches as $session) {
            $rows = $rowsBySession->get(
                $session->session_id,
                collect(),
            );

            $reasons = $this->integrityReasons(
                session: $session,
                rows: $rows,
                phase: $phase,
                expectedForm: $expectedForm,
            );

            if ($reasons !== []) {
                $stats['integrity_failures']++;

                foreach ($reasons as $reason) {
                    $stats['integrity_reasons'][$reason] =
                        ($stats['integrity_reasons'][$reason] ?? 0)
                        + 1;
                }

                continue;
            }

            $session->response_rows = $rows->count();

            $session->answered_items = $rows
                ->filter(
                    fn (object $row): bool => $row->selected_choice_id !== null,
                )
                ->count();

            $session->domain_scores =
                $this->domainScores($rows);

            $eligible->push($session);
        }

        ksort($stats['integrity_reasons']);

        return [
            $eligible,
            $stats,
        ];
    }

    /**
     * Load all item records in one query per phase.
     *
     * This avoids running 60 queries for every student.
     */
    private function responseRows(
        string $phase,
        Collection $sessionIds,
    ): Collection {
        if ($sessionIds->isEmpty()) {
            return collect();
        }

        if ($phase === 'pre_test') {
            return DB::table(
                'response_telemetry_logs as r',
            )
                ->join(
                    'questions as q',
                    'q.question_id',
                    '=',
                    'r.question_id',
                )
                ->join(
                    'tos_competencies as c',
                    'c.competency_id',
                    '=',
                    'q.competency_id',
                )
                ->join(
                    'tos_domains as d',
                    'd.domain_id',
                    '=',
                    'c.domain_id',
                )
                ->leftJoin(
                    'question_choices as selected',
                    'selected.choice_id',
                    '=',
                    'r.selected_choice_id',
                )
                ->whereIn(
                    'r.session_id',
                    $sessionIds->all(),
                )
                ->orderBy('r.session_id')
                ->orderBy('r.item_position')
                ->get([
                    'r.log_id as response_id',
                    'r.session_id',
                    'r.user_id as response_user_id',
                    'r.question_id',
                    'r.competency_id as response_competency_id',
                    'r.selected_choice_id',
                    'r.item_position',
                    'r.is_correct as response_is_correct',

                    'q.competency_id as question_competency_id',
                    'q.research_form as question_research_form',

                    'd.domain_id',
                    'd.domain_number',

                    'selected.question_id as selected_choice_question_id',
                    'selected.is_correct as selected_choice_is_correct',
                ]);
        }

        return DB::table('assessment_responses as r')
            ->join(
                'questions as q',
                'q.question_id',
                '=',
                'r.question_id',
            )
            ->join(
                'tos_competencies as c',
                'c.competency_id',
                '=',
                'q.competency_id',
            )
            ->join(
                'tos_domains as d',
                'd.domain_id',
                '=',
                'c.domain_id',
            )
            ->leftJoin(
                'question_choices as selected',
                'selected.choice_id',
                '=',
                'r.selected_choice_id',
            )
            ->whereIn(
                'r.session_id',
                $sessionIds->all(),
            )
            ->orderBy('r.session_id')
            ->orderBy('r.item_position')
            ->get([
                'r.response_id',
                'r.session_id',
                'r.question_id',
                'r.selected_choice_id',
                'r.item_position',
                'r.is_correct as response_is_correct',

                'q.competency_id as question_competency_id',
                'q.research_form as question_research_form',

                'd.domain_id',
                'd.domain_number',

                'selected.question_id as selected_choice_question_id',
                'selected.is_correct as selected_choice_is_correct',
            ]);
    }

    /**
     * @return array<int, string>
     */
    private function integrityReasons(
        object $session,
        Collection $rows,
        string $phase,
        string $expectedForm,
    ): array {
        $reasons = [];

        if (
            (int) $session->total_items
            !== ResearchFormService::TOTAL_ITEMS
        ) {
            $reasons[] = 'wrong_total_items';
        }

        if (
            (int) $session->target_length
            !== ResearchFormService::TOTAL_ITEMS
        ) {
            $reasons[] = 'wrong_target_length';
        }

        if (! $this->hasValidOwnTimeline($session)) {
            $reasons[] = 'invalid_session_timeline';
        }

        $servedIds = $this->servedQuestionIds(
            $session->served_question_ids,
        );

        if (
            $servedIds->count()
            !== ResearchFormService::TOTAL_ITEMS
        ) {
            $reasons[] = 'wrong_served_question_count';
        }

        if (
            $servedIds->unique()->count()
            !== ResearchFormService::TOTAL_ITEMS
        ) {
            $reasons[] = 'duplicate_served_question_ids';
        }

        if (
            $rows->count()
            !== ResearchFormService::TOTAL_ITEMS
        ) {
            $reasons[] = 'wrong_response_row_count';
        }

        $responseQuestionIds = $rows
            ->pluck('question_id')
            ->map(fn (mixed $id): string => (string) $id);

        if (
            $responseQuestionIds->unique()->count()
            !== ResearchFormService::TOTAL_ITEMS
        ) {
            $reasons[] = 'duplicate_response_question_ids';
        }

        $sortedServedIds = $servedIds
            ->sort()
            ->values()
            ->all();

        $sortedResponseIds = $responseQuestionIds
            ->sort()
            ->values()
            ->all();

        if ($sortedServedIds !== $sortedResponseIds) {
            $reasons[] = 'responses_do_not_match_served_questions';
        }

        $positions = $rows
            ->pluck('item_position')
            ->map(fn (mixed $position): int => (int) $position)
            ->sort()
            ->values()
            ->all();

        if (
            $positions
            !== range(
                1,
                ResearchFormService::TOTAL_ITEMS,
            )
        ) {
            $reasons[] = 'invalid_item_positions';
        }

        /*
         * Verify that item position N still refers to the Nth served
         * question. Matching only the unordered IDs is not enough.
         */
        $orderedResponseIds = $rows
            ->sortBy('item_position')
            ->pluck('question_id')
            ->map(fn (mixed $id): string => (string) $id)
            ->values()
            ->all();

        if ($servedIds->values()->all() !== $orderedResponseIds) {
            $reasons[] = 'response_order_does_not_match_served_order';
        }

        if (
            $rows->contains(
                fn (object $row): bool => (string) $row->question_research_form
                    !== $expectedForm,
            )
        ) {
            $reasons[] = 'wrong_question_form';
        }

        $domainCounts = $rows->countBy(
            fn (object $row): int => (int) $row->domain_number,
        );

        foreach (
            range(
                1,
                ResearchFormService::SUBJECT_COUNT,
            ) as $domainNumber
        ) {
            if (
                (int) $domainCounts->get(
                    $domainNumber,
                    0,
                )
                !== ResearchFormService::ITEMS_PER_SUBJECT
            ) {
                $reasons[] = 'invalid_domain_distribution';

                break;
            }
        }

        foreach ($rows as $row) {
            if (
                $phase === 'pre_test'
                && (string) $row->response_user_id
                    !== (string) $session->user_id
            ) {
                $reasons[] = 'response_user_mismatch';
            }

            if (
                $phase === 'pre_test'
                && (string) $row->response_competency_id
                    !== (string) $row->question_competency_id
            ) {
                $reasons[] = 'response_competency_mismatch';
            }

            $hasSelectedChoice =
                $row->selected_choice_id !== null;

            if (! $hasSelectedChoice) {
                /*
                 * The diagnostic requires every answer.
                 */
                if ($phase === 'pre_test') {
                    $reasons[] =
                        'unanswered_pre_test_item';
                }

                /*
                 * An unanswered post-test row may be null or false,
                 * but it must never be marked correct.
                 */
                if (
                    (int) $row->response_is_correct === 1
                ) {
                    $reasons[] =
                        'unanswered_item_marked_correct';
                }

                continue;
            }

            if (
                $row->selected_choice_question_id === null
                || (string) $row->selected_choice_question_id
                    !== (string) $row->question_id
            ) {
                $reasons[] =
                    'selected_choice_question_mismatch';

                continue;
            }

            if (
                $row->response_is_correct === null
                || $row->selected_choice_is_correct === null
                || (int) $row->response_is_correct
                    !== (int) $row->selected_choice_is_correct
            ) {
                $reasons[] =
                    'incorrect_answer_key_result';
            }
        }

        $rowCorrectTotal = $rows
            ->filter(
                fn (object $row): bool => (int) $row->response_is_correct === 1,
            )
            ->count();

        if (
            $session->correct_items === null
            || (int) $session->correct_items
                !== $rowCorrectTotal
        ) {
            $reasons[] =
                'session_correct_total_mismatch';
        }

        return array_values(
            array_unique($reasons),
        );
    }

    private function servedQuestionIds(
        mixed $value,
    ): Collection {
        if (is_string($value)) {
            $decoded = json_decode(
                $value,
                true,
            );

            $value = is_array($decoded)
                ? $decoded
                : [];
        }

        if (! is_array($value)) {
            return collect();
        }

        return collect($value)
            ->map(
                fn (mixed $id): string => trim((string) $id),
            )
            ->filter(
                fn (string $id): bool => $id !== '',
            )
            ->values();
    }

    private function domainScores(
        Collection $rows,
    ): array {
        return $rows
            ->groupBy('domain_id')
            ->map(
                function (Collection $domainRows): float {
                    $correct = $domainRows
                        ->filter(
                            fn (object $row): bool => (int) $row->response_is_correct
                                === 1,
                        )
                        ->count();

                    /*
                     * Each valid domain has exactly 10 rows.
                     * Unanswered post-test rows therefore remain
                     * incorrect and stay in the denominator.
                     */
                    return $correct * 100.0
                        / ResearchFormService::ITEMS_PER_SUBJECT;
                },
            )
            ->all();
    }

    private function hasValidOwnTimeline(
        object $session,
    ): bool {
        if (
            empty($session->started_at)
            || empty($session->completed_at)
        ) {
            return false;
        }

        try {
            return CarbonImmutable::parse(
                $session->started_at,
            )->lt(
                CarbonImmutable::parse(
                    $session->completed_at,
                ),
            );
        } catch (Throwable) {
            return false;
        }
    }

    private function precedesPostTest(
        object $pre,
        object $post,
    ): bool {
        try {
            /*
             * Strictly earlier—not equal.
             */
            return CarbonImmutable::parse(
                $pre->completed_at,
            )->lt(
                CarbonImmutable::parse(
                    $post->started_at,
                ),
            );
        } catch (Throwable) {
            return false;
        }
    }

    private function percentage(
        int $correctItems,
    ): float {
        return $correctItems * 100.0
            / ResearchFormService::TOTAL_ITEMS;
    }
}
