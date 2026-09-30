<?php

namespace App\Services;

use App\Models\TosDomain;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ResearchAnalyticsService
{
    private const EPSILON = 1.0E-9;

    private const EXACT_MAX_N = 25;

    public function fullReport(): array
    {
        $dataset = $this->loadDataset();
        $allPairs = $dataset['pairs'];
        $completePairs = $allPairs
            ->where('is_complete', true)
            ->values();
        $domainScores = $this->loadDomainScores($allPairs);

        return [
            'generated_at' => now(),
            'form_versions' => [
                'pre_test' => $this->preTestVersion(),
                'post_test' => $this->postTestVersion(),
            ],
            'funnel' => $this->sampleFunnelFromDataset($dataset),
            'primary' => $this->analysePairs(
                $completePairs,
                $domainScores,
                'complete-case',
            ),
            'sensitivity' => $this->analysePairs(
                $allPairs,
                $domainScores,
                'all-finalized-attempts',
            ),
            'readiness' => [
                'primary' => $this->readinessAccuracyFor($completePairs),
                'sensitivity' => $this->readinessAccuracyFor($allPairs),
            ],
            'breakdown' => $this->submissionBreakdownFromDataset($dataset),
            'cohorts' => $this->cohortSummaries($dataset),
        ];
    }

    public function sampleFunnel(): array
    {
        return $this->sampleFunnelFromDataset(
            $this->loadDataset(),
        );
    }

    public function pairedAnalysis(bool $completeOnly): array
    {
        $dataset = $this->loadDataset();
        $pairs = $completeOnly
            ? $dataset['pairs']->where('is_complete', true)->values()
            : $dataset['pairs'];

        return $this->analysePairs(
            $pairs,
            $this->loadDomainScores($dataset['pairs']),
            $completeOnly
                ? 'complete-case'
                : 'all-finalized-attempts',
        );
    }

    public function readinessAccuracy(bool $completeOnly = false): array
    {
        $pairs = $this->loadDataset()['pairs'];

        if ($completeOnly) {
            $pairs = $pairs->where('is_complete', true)->values();
        }

        return $this->readinessAccuracyFor($pairs);
    }

    public function submissionBreakdown(): array
    {
        return $this->submissionBreakdownFromDataset(
            $this->loadDataset(),
        );
    }

    /**
     * @param  array<int, float|int>  $differences
     * @return array<string, float|int|string|null>
     */
    public function wilcoxonSignedRank(array $differences): array
    {
        $inputCount = count($differences);
        $nonzero = array_values(array_filter(
            array_map('floatval', $differences),
            fn (float $difference): bool => abs($difference) > self::EPSILON,
        ));
        $n = count($nonzero);

        if ($n === 0) {
            return [
                'input_n' => $inputCount,
                'n' => 0,
                'zero_differences' => $inputCount,
                'w_plus' => 0.0,
                'w_minus' => 0.0,
                'w_statistic' => 0.0,
                'z' => null,
                'p_value' => 1.0,
                'method' => 'not applicable (no nonzero differences)',
            ];
        }

        $absoluteValues = array_map('abs', $nonzero);
        $ranks = $this->averageRanks($absoluteValues);
        $wPlus = 0.0;
        $wMinus = 0.0;

        foreach ($nonzero as $index => $difference) {
            if ($difference > 0) {
                $wPlus += $ranks[$index];
            } else {
                $wMinus += $ranks[$index];
            }
        }

        $wStatistic = min($wPlus, $wMinus);
        $tieCounts = $this->tieCounts($absoluteValues);
        $hasTies = collect($tieCounts)->contains(
            fn (int $count): bool => $count > 1,
        );

        if (! $hasTies && $n <= self::EXACT_MAX_N) {
            return [
                'input_n' => $inputCount,
                'n' => $n,
                'zero_differences' => $inputCount - $n,
                'w_plus' => round($wPlus, 2),
                'w_minus' => round($wMinus, 2),
                'w_statistic' => round($wStatistic, 2),
                'z' => null,
                'p_value' => round(
                    $this->exactTwoSidedPValue($n, (int) round($wPlus)),
                    6,
                ),
                'method' => 'exact two-sided signed-rank distribution',
            ];
        }

        $mean = $n * ($n + 1) / 4.0;
        $variance = $n * ($n + 1) * (2 * $n + 1) / 24.0;

        foreach ($tieCounts as $count) {
            if ($count > 1) {
                $variance -= (($count ** 3) - $count) / 48.0;
            }
        }

        if ($variance <= self::EPSILON) {
            return [
                'input_n' => $inputCount,
                'n' => $n,
                'zero_differences' => $inputCount - $n,
                'w_plus' => round($wPlus, 2),
                'w_minus' => round($wMinus, 2),
                'w_statistic' => round($wStatistic, 2),
                'z' => null,
                'p_value' => 1.0,
                'method' => 'not applicable (degenerate variance)',
            ];
        }

        $deviation = $wPlus - $mean;
        $continuityCorrection = match (true) {
            $deviation > 0 => 0.5,
            $deviation < 0 => -0.5,
            default => 0.0,
        };
        $z = ($deviation - $continuityCorrection) / sqrt($variance);
        $pValue = 2.0 * (1.0 - $this->normalCdf(abs($z)));

        return [
            'input_n' => $inputCount,
            'n' => $n,
            'zero_differences' => $inputCount - $n,
            'w_plus' => round($wPlus, 2),
            'w_minus' => round($wMinus, 2),
            'w_statistic' => round($wStatistic, 2),
            'z' => round($z, 6),
            'p_value' => round(max(0.0, min(1.0, $pValue)), 6),
            'method' => 'normal approximation with continuity and tie correction',
        ];
    }

    /**
     * @param  array<int, float|int>  $values
     * @return array<string, float|int|null>
     */
    public function descriptives(array $values): array
    {
        $values = array_values(array_map('floatval', $values));
        $n = count($values);

        if ($n === 0) {
            return [
                'n' => 0,
                'mean' => null,
                'median' => null,
                'sd' => null,
                'min' => null,
                'max' => null,
            ];
        }

        sort($values, SORT_NUMERIC);
        $mean = array_sum($values) / $n;
        $median = $n % 2 === 0
            ? ($values[intdiv($n, 2) - 1] + $values[intdiv($n, 2)]) / 2.0
            : $values[intdiv($n, 2)];
        $sumSquaredDeviations = array_sum(array_map(
            fn (float $value): float => ($value - $mean) ** 2,
            $values,
        ));

        return [
            'n' => $n,
            'mean' => round($mean, 2),
            'median' => round($median, 2),
            'sd' => round(
                $n > 1
                    ? sqrt($sumSquaredDeviations / ($n - 1))
                    : 0.0,
                2,
            ),
            'min' => round($values[0], 2),
            'max' => round($values[$n - 1], 2),
        ];
    }

    /**
     * @return array{pre_sessions: Collection, post_sessions: Collection, pairs: Collection}
     */
    private function loadDataset(): array
    {
        $preSessions = DB::table('assessment_sessions as s')
            ->join('users as u', 'u.user_id', '=', 's.user_id')
            ->leftJoin('cohorts as c', 'c.cohort_id', '=', 'u.cohort_id')
            ->where('s.session_type', 'diagnostic')
            ->where('s.research_phase', 'pre_test')
            ->where('s.exam_form_version', $this->preTestVersion())
            ->whereNotNull('s.completed_at')
            ->where('s.total_items', '>', 0)
            ->orderBy('s.completed_at')
            ->orderBy('s.session_id')
            ->get([
                's.session_id',
                's.user_id',
                's.total_items',
                's.correct_items',
                's.completed_at',
                'u.cohort_id',
                'c.cohort_name',
                'c.academic_year',
            ])
            ->unique('user_id')
            ->keyBy('user_id');

        $postSessions = DB::table('assessment_sessions as s')
            ->join('users as u', 'u.user_id', '=', 's.user_id')
            ->leftJoin('cohorts as c', 'c.cohort_id', '=', 'u.cohort_id')
            ->leftJoin('assessment_responses as r', 'r.session_id', '=', 's.session_id')
            ->where('s.session_type', 'mock_board')
            ->where('s.research_phase', 'post_test')
            ->where('s.exam_form_version', $this->postTestVersion())
            ->whereNotNull('s.completed_at')
            ->where('s.total_items', '>', 0)
            ->groupBy([
                's.session_id',
                's.user_id',
                's.total_items',
                's.correct_items',
                's.target_length',
                's.readiness_snapshot_pct',
                's.submission_reason',
                's.completed_at',
                'u.cohort_id',
                'c.cohort_name',
                'c.academic_year',
            ])
            ->orderBy('s.completed_at')
            ->orderBy('s.session_id')
            ->get([
                's.session_id',
                's.user_id',
                's.total_items',
                's.correct_items',
                's.target_length',
                's.readiness_snapshot_pct',
                's.submission_reason',
                's.completed_at',
                'u.cohort_id',
                'c.cohort_name',
                'c.academic_year',
                DB::raw('COUNT(r.response_id) AS response_rows'),
                DB::raw('SUM(CASE WHEN r.selected_choice_id IS NOT NULL THEN 1 ELSE 0 END) AS answered_items'),
            ])
            ->unique('user_id')
            ->keyBy('user_id');

        $pairs = $preSessions
            ->keys()
            ->intersect($postSessions->keys())
            ->map(function (string $userId) use ($preSessions, $postSessions): array {
                $pre = $preSessions->get($userId);
                $post = $postSessions->get($userId);
                $expectedItems = (int) ($post->target_length ?: $post->total_items);
                $isComplete = $expectedItems > 0
                    && (int) $post->response_rows === $expectedItems
                    && (int) $post->answered_items === $expectedItems;

                return [
                    'user_id' => $userId,
                    'cohort_id' => $post->cohort_id,
                    'cohort_name' => $post->cohort_name ?? 'Unassigned',
                    'academic_year' => $post->academic_year,
                    'pre_session_id' => $pre->session_id,
                    'post_session_id' => $post->session_id,
                    'pre_score' => $this->percentage(
                        (int) $pre->correct_items,
                        (int) $pre->total_items,
                    ),
                    'post_score' => $this->percentage(
                        (int) $post->correct_items,
                        (int) $post->total_items,
                    ),
                    'readiness_snapshot_pct' => $post->readiness_snapshot_pct === null
                        ? null
                        : (float) $post->readiness_snapshot_pct,
                    'submission_reason' => $post->submission_reason,
                    'answered_items' => (int) $post->answered_items,
                    'expected_items' => $expectedItems,
                    'is_complete' => $isComplete,
                ];
            })
            ->values();

        return compact('preSessions', 'postSessions', 'pairs');
    }

    private function analysePairs(
        Collection $pairs,
        array $domainScores,
        string $population,
    ): array {
        $pre = $pairs->pluck('pre_score')
            ->map(fn ($score): float => (float) $score)
            ->all();
        $post = $pairs->pluck('post_score')
            ->map(fn ($score): float => (float) $score)
            ->all();
        $differences = $pairs
            ->map(fn (array $pair): float => $pair['post_score'] - $pair['pre_score'])
            ->all();

        return [
            'population' => $population,
            'n_pairs' => $pairs->count(),
            'pre' => $this->descriptives($pre),
            'post' => $this->descriptives($post),
            'diff' => $this->descriptives($differences),
            'wilcoxon' => $this->wilcoxonSignedRank($differences),
            'per_domain' => $this->analyseDomains($pairs, $domainScores),
        ];
    }

    private function loadDomainScores(Collection $pairs): array
    {
        if ($pairs->isEmpty()) {
            return ['pre' => collect(), 'post' => collect()];
        }

        $pre = DB::table('response_telemetry_logs as r')
            ->join('tos_competencies as c', 'c.competency_id', '=', 'r.competency_id')
            ->whereIn('r.session_id', $pairs->pluck('pre_session_id'))
            ->groupBy('r.session_id', 'c.domain_id')
            ->get([
                'r.session_id',
                'c.domain_id',
                DB::raw('SUM(CASE WHEN r.is_correct = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(*) AS score'),
            ])
            ->mapWithKeys(fn (object $row): array => [
                $this->domainScoreKey($row->session_id, $row->domain_id) => (float) $row->score,
            ]);

        $post = DB::table('assessment_responses as r')
            ->join('questions as q', 'q.question_id', '=', 'r.question_id')
            ->join('tos_competencies as c', 'c.competency_id', '=', 'q.competency_id')
            ->whereIn('r.session_id', $pairs->pluck('post_session_id'))
            ->groupBy('r.session_id', 'c.domain_id')
            ->get([
                'r.session_id',
                'c.domain_id',
                DB::raw('SUM(CASE WHEN r.is_correct = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(*) AS score'),
            ])
            ->mapWithKeys(fn (object $row): array => [
                $this->domainScoreKey($row->session_id, $row->domain_id) => (float) $row->score,
            ]);

        return compact('pre', 'post');
    }

    private function analyseDomains(Collection $pairs, array $scores): array
    {
        $domains = TosDomain::query()
            ->orderBy('domain_number')
            ->get(['domain_id', 'domain_number', 'domain_name']);
        $familySize = max(1, $domains->count());

        return $domains->map(function (TosDomain $domain) use ($pairs, $scores, $familySize): array {
            $pre = [];
            $post = [];
            $differences = [];

            foreach ($pairs as $pair) {
                $preKey = $this->domainScoreKey(
                    $pair['pre_session_id'],
                    $domain->domain_id,
                );
                $postKey = $this->domainScoreKey(
                    $pair['post_session_id'],
                    $domain->domain_id,
                );

                if (! $scores['pre']->has($preKey) || ! $scores['post']->has($postKey)) {
                    continue;
                }

                $preScore = (float) $scores['pre']->get($preKey);
                $postScore = (float) $scores['post']->get($postKey);
                $pre[] = $preScore;
                $post[] = $postScore;
                $differences[] = $postScore - $preScore;
            }

            $wilcoxon = $this->wilcoxonSignedRank($differences);
            $adjustedP = min(1.0, (float) $wilcoxon['p_value'] * $familySize);

            return [
                'domain_id' => $domain->domain_id,
                'domain_number' => $domain->domain_number,
                'domain_name' => $domain->domain_name,
                'n_pairs' => count($differences),
                'pre' => $this->descriptives($pre),
                'post' => $this->descriptives($post),
                'diff' => $this->descriptives($differences),
                'wilcoxon' => $wilcoxon,
                'bonferroni_p_value' => round($adjustedP, 6),
                'significant_at_05' => $wilcoxon['n'] > 0
                    && $adjustedP < 0.05,
            ];
        })->all();
    }

    private function readinessAccuracyFor(Collection $pairs): array
    {
        $errors = $pairs
            ->filter(fn (array $pair): bool => $pair['readiness_snapshot_pct'] !== null)
            ->map(fn (array $pair): float => $pair['readiness_snapshot_pct'] - $pair['post_score'])
            ->values();

        if ($errors->isEmpty()) {
            return [
                'n' => 0,
                'mae' => null,
                'rmse' => null,
                'mean_error' => null,
            ];
        }

        return [
            'n' => $errors->count(),
            'mae' => round($errors->map(fn (float $error): float => abs($error))->avg(), 4),
            'rmse' => round(sqrt($errors->map(fn (float $error): float => $error ** 2)->avg()), 4),
            'mean_error' => round($errors->avg(), 4),
        ];
    }

    private function sampleFunnelFromDataset(array $dataset): array
    {
        $preUserIds = $dataset['preSessions']->keys();
        $postUserIds = $dataset['postSessions']->keys();
        $pairedUserIds = $preUserIds->intersect($postUserIds);

        return [
            'pre_completed' => $preUserIds->count(),
            'post_finalized' => $postUserIds->count(),
            'paired_finalized' => $pairedUserIds->count(),
            'paired_complete' => $dataset['pairs']->where('is_complete', true)->count(),
            'pre_only' => $preUserIds->diff($postUserIds)->count(),
            'post_only' => $postUserIds->diff($preUserIds)->count(),
            'excluded_missing_pair' => $preUserIds->diff($postUserIds)->count()
                + $postUserIds->diff($preUserIds)->count(),
        ];
    }

    private function submissionBreakdownFromDataset(array $dataset): array
    {
        $result = [
            'total' => $dataset['postSessions']->count(),
            'manual_complete' => 0,
            'manual_incomplete' => 0,
            'expired_complete' => 0,
            'expired_incomplete' => 0,
            'unknown' => 0,
        ];

        foreach ($dataset['postSessions'] as $session) {
            $expected = (int) ($session->target_length ?: $session->total_items);
            $complete = $expected > 0
                && (int) $session->response_rows === $expected
                && (int) $session->answered_items === $expected;

            if (! in_array($session->submission_reason, ['manual', 'expired'], true)) {
                $result['unknown']++;

                continue;
            }

            $key = $session->submission_reason.'_'.($complete ? 'complete' : 'incomplete');
            $result[$key]++;
        }

        return $result;
    }

    private function cohortSummaries(array $dataset): array
    {
        $cohorts = DB::table('cohorts as c')
            ->leftJoin('users as u', function ($join): void {
                $join->on('u.cohort_id', '=', 'c.cohort_id')
                    ->where('u.role', 'student')
                    ->whereNull('u.deleted_at');
            })
            ->groupBy('c.cohort_id', 'c.cohort_name', 'c.academic_year')
            ->orderBy('c.academic_year')
            ->orderBy('c.cohort_name')
            ->get([
                'c.cohort_id',
                'c.cohort_name',
                'c.academic_year',
                DB::raw('COUNT(u.user_id) AS enrolled_students'),
            ]);

        return $cohorts->map(function (object $cohort) use ($dataset): array {
            $pre = $dataset['preSessions']->filter(
                fn (object $session): bool => $session->cohort_id === $cohort->cohort_id,
            );
            $post = $dataset['postSessions']->filter(
                fn (object $session): bool => $session->cohort_id === $cohort->cohort_id,
            );
            $pairs = $dataset['pairs']->where('cohort_id', $cohort->cohort_id);
            $completePairs = $pairs->where('is_complete', true);
            $changes = $completePairs
                ->map(fn (array $pair): float => $pair['post_score'] - $pair['pre_score'])
                ->all();

            return [
                'cohort_id' => $cohort->cohort_id,
                'cohort_name' => $cohort->cohort_name,
                'academic_year' => $cohort->academic_year,
                'enrolled_students' => (int) $cohort->enrolled_students,
                'pre_completed' => $pre->count(),
                'post_finalized' => $post->count(),
                'paired_finalized' => $pairs->count(),
                'paired_complete' => $completePairs->count(),
                'mean_change_complete' => $this->descriptives($changes)['mean'],
            ];
        })->all();
    }

    /** @return array<int, float> */
    private function averageRanks(array $values): array
    {
        $indexed = collect($values)
            ->map(fn (float $value, int $index): array => [
                'index' => $index,
                'value' => $value,
            ])
            ->sortBy('value', SORT_NUMERIC)
            ->values();
        $ranks = array_fill(0, count($values), 0.0);
        $start = 0;

        while ($start < $indexed->count()) {
            $end = $start;

            while (
                $end + 1 < $indexed->count()
                && abs($indexed[$end + 1]['value'] - $indexed[$start]['value']) <= self::EPSILON
            ) {
                $end++;
            }

            $averageRank = (($start + 1) + ($end + 1)) / 2.0;

            for ($position = $start; $position <= $end; $position++) {
                $ranks[$indexed[$position]['index']] = $averageRank;
            }

            $start = $end + 1;
        }

        return $ranks;
    }

    /** @return array<int, int> */
    private function tieCounts(array $values): array
    {
        sort($values, SORT_NUMERIC);
        $counts = [];
        $start = 0;

        while ($start < count($values)) {
            $end = $start;

            while (
                $end + 1 < count($values)
                && abs($values[$end + 1] - $values[$start]) <= self::EPSILON
            ) {
                $end++;
            }

            $counts[] = $end - $start + 1;
            $start = $end + 1;
        }

        return $counts;
    }

    private function exactTwoSidedPValue(int $n, int $observedWPlus): float
    {
        $totalRank = intdiv($n * ($n + 1), 2);
        $counts = array_fill(0, $totalRank + 1, 0);
        $counts[0] = 1;

        for ($rank = 1; $rank <= $n; $rank++) {
            for ($sum = $totalRank; $sum >= $rank; $sum--) {
                $counts[$sum] += $counts[$sum - $rank];
            }
        }

        $lower = array_sum(array_slice($counts, 0, $observedWPlus + 1));
        $upper = array_sum(array_slice($counts, $observedWPlus));
        $outcomes = 2 ** $n;

        return min(1.0, 2.0 * min($lower / $outcomes, $upper / $outcomes));
    }

    private function normalCdf(float $z): float
    {
        $sign = $z < 0 ? -1.0 : 1.0;
        $x = abs($z) / sqrt(2.0);
        $t = 1.0 / (1.0 + 0.3275911 * $x);
        $erf = 1.0 - (((((1.061405429 * $t - 1.453152027) * $t)
            + 1.421413741) * $t - 0.284496736) * $t
            + 0.254829592) * $t * exp(-$x * $x);

        return 0.5 * (1.0 + $sign * $erf);
    }

    private function percentage(int $correct, int $total): float
    {
        return $total > 0 ? ($correct * 100.0) / $total : 0.0;
    }

    private function domainScoreKey(string $sessionId, int|string $domainId): string
    {
        return $sessionId.'|'.$domainId;
    }

    private function preTestVersion(): string
    {
        return (string) config('citirx.research.pre_test_form_version', 'pre_test_a_v1');
    }

    private function postTestVersion(): string
    {
        return (string) config('citirx.research.post_test_form_version', 'post_test_b_v1');
    }
}
