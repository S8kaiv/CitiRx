<?php

namespace App\Services;

use App\Models\Question;
use App\Models\ResearchFormVersion;
use App\Models\TosDomain;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ResearchAnalyticsService
{
    private const EPSILON = 1.0E-9;

    private const EXACT_MAX_N = 25;

    /**
     * @var array{
     *     pre_test: ResearchFormVersion,
     *     post_test: ResearchFormVersion
     * }|null
     */
    private ?array $resolvedResearchVersions = null;

    public function __construct(
        protected ResearchAttemptEligibilityService $attemptEligibility,
    ) {}

    public function fullReport(): array
    {
        $dataset = $this->loadDataset();

        $allPairs = $dataset['pairs'];

        $completePairs = $allPairs
            ->where('is_complete', true)
            ->values();

        $domainScores = $this->loadDomainScores(
            $allPairs,
        );

        return [
            'generated_at' => now(),

            'form_versions' => [
                'pre_test' => $this
                    ->researchVersions()['pre_test']
                    ->version_code,

                'post_test' => $this
                    ->researchVersions()['post_test']
                    ->version_code,
            ],

            'form_version_ids' => [
                'pre_test' => $this
                    ->researchVersions()['pre_test']
                    ->form_version_id,

                'post_test' => $this
                    ->researchVersions()['post_test']
                    ->form_version_id,
            ],

            'eligibility' => $dataset['eligibility'],

            'funnel' => $this->sampleFunnelFromDataset(
                $dataset,
            ),

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
                'primary' => $this->readinessAccuracyFor(
                    $completePairs,
                ),

                'sensitivity' => $this->readinessAccuracyFor(
                    $allPairs,
                ),
            ],

            'breakdown' => $this->submissionBreakdownFromDataset(
                $dataset,
            ),

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
                'p_value' => $this->exactTwoSidedPValue(
                    $n,
                    (int) round($wPlus),
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
            'p_value' => max(0.0, min(1.0, $pValue)),
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
            'sd' => $n > 1
                ? round(
                    sqrt($sumSquaredDeviations / ($n - 1)),
                    2,
                )
                : null,
            'min' => round($values[0], 2),
            'max' => round($values[$n - 1], 2),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function loadDataset(): array
    {
        $versions = $this->researchVersions();

        return $this->attemptEligibility->dataset(
            preTestVersion: $versions['pre_test'],
            postTestVersion: $versions['post_test'],
        );
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

    private function loadDomainScores(
        Collection $pairs,
    ): array {
        $pre = collect();
        $post = collect();

        foreach ($pairs as $pair) {
            foreach (
                $pair['pre_domain_scores'] as $domainId => $score
            ) {
                $pre->put(
                    $this->domainScoreKey(
                        $pair['pre_session_id'],
                        $domainId,
                    ),
                    (float) $score,
                );
            }

            foreach (
                $pair['post_domain_scores'] as $domainId => $score
            ) {
                $post->put(
                    $this->domainScoreKey(
                        $pair['post_session_id'],
                        $domainId,
                    ),
                    (float) $score,
                );
            }
        }

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
                'bonferroni_p_value' => $adjustedP,
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

    private function sampleFunnelFromDataset(
        array $dataset,
    ): array {
        $preUserIds =
            $dataset['preSessions']->keys();

        $postUserIds =
            $dataset['postSessions']->keys();

        $eligibility = $dataset['eligibility'];

        return [
            'pre_candidates' => $eligibility['pre_candidates'],

            'post_candidates' => $eligibility['post_candidates'],

            'pre_completed' => $preUserIds->count(),

            'post_finalized' => $postUserIds->count(),

            /*
         * This now counts only valid, correctly ordered pairs.
         */
            'paired_finalized' => $dataset['pairs']->count(),

            'paired_complete' => $dataset['pairs']
                ->where('is_complete', true)
                ->count(),

            'pre_only' => $preUserIds
                ->diff($postUserIds)
                ->count(),

            'post_only' => $postUserIds
                ->diff($preUserIds)
                ->count(),

            'excluded_missing_pair' => $preUserIds
                ->diff($postUserIds)
                ->count()
                + $postUserIds
                    ->diff($preUserIds)
                    ->count(),

            'excluded_pre_missing_version' => $eligibility['pre_missing_version'],

            'excluded_pre_wrong_version' => $eligibility['pre_wrong_version'],

            'excluded_post_missing_version' => $eligibility['post_missing_version'],

            'excluded_post_wrong_version' => $eligibility['post_wrong_version'],

            'excluded_pre_integrity' => $eligibility['pre_integrity_failures'],

            'excluded_post_integrity' => $eligibility['post_integrity_failures'],

            'excluded_duplicate_pre' => $eligibility['duplicate_pre_attempts'],

            'excluded_duplicate_post' => $eligibility['duplicate_post_attempts'],

            'excluded_invalid_chronology' => $eligibility['invalid_chronology'],

            'excluded_pre_participant_scope' => array_sum(
                $eligibility['pre_participant_scope_exclusions'],
            ),

            'excluded_post_participant_scope' => array_sum(
                $eligibility['post_participant_scope_exclusions'],
            ),
        ];
    }

    private function submissionBreakdownFromDataset(
        array $dataset,
    ): array {
        $result = [
            'total' => $dataset['postSessions']->count(),

            'manual_complete' => 0,
            'manual_incomplete' => 0,
            'expired_complete' => 0,
            'expired_incomplete' => 0,
            'unknown' => 0,
        ];

        foreach (
            $dataset['postSessions'] as $session
        ) {
            /*
         * Session integrity has already guaranteed that 60
         * response rows exist. Completeness here means that all
         * 60 rows have selected answers.
         */
            $complete =
                (int) $session->answered_items
                === ResearchFormService::TOTAL_ITEMS;

            if (
                ! in_array(
                    $session->submission_reason,
                    ['manual', 'expired'],
                    true,
                )
            ) {
                $result['unknown']++;

                continue;
            }

            $key = $session->submission_reason
                .'_'
                .($complete
                    ? 'complete'
                    : 'incomplete');

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

    private function domainScoreKey(string $sessionId, int|string $domainId): string
    {
        return $sessionId.'|'.$domainId;
    }

    /**
     * Resolve the immutable research-form versions selected for the
     * analytics report.
     *
     * The configured version codes pin the report to a reproducible
     * Form A/Form B pair. Session and question eligibility are then
     * checked using the corresponding form_version_id values.
     *
     * @return array{
     *     pre_test: ResearchFormVersion,
     *     post_test: ResearchFormVersion
     * }
     */
    private function researchVersions(): array
    {
        if ($this->resolvedResearchVersions !== null) {
            return $this->resolvedResearchVersions;
        }

        $definitions = [
            'pre_test' => [
                'research_form' => Question::FORM_PRE_TEST_A,

                'version_code' => trim(
                    (string) config(
                        'citirx.research.pre_test_form_version',
                        'pre_test_a_v1',
                    ),
                ),
            ],

            'post_test' => [
                'research_form' => Question::FORM_POST_TEST_B,

                'version_code' => trim(
                    (string) config(
                        'citirx.research.post_test_form_version',
                        'post_test_b_v1',
                    ),
                ),
            ],
        ];

        foreach ($definitions as $phase => $definition) {
            if ($definition['version_code'] === '') {
                throw new RuntimeException(
                    "The configured {$phase} research-form "
                        .'version cannot be empty.',
                );
            }
        }

        $versions = ResearchFormVersion::query()
            ->whereIn(
                'version_code',
                collect($definitions)
                    ->pluck('version_code')
                    ->all(),
            )
            ->get()
            ->keyBy('version_code');

        $resolved = [];

        foreach ($definitions as $phase => $definition) {
            $version = $versions->get(
                $definition['version_code'],
            );

            if (! $version instanceof ResearchFormVersion) {
                throw new RuntimeException(
                    'Research-form version '
                        ."{$definition['version_code']} "
                        .'is not registered.',
                );
            }

            if (
                $version->research_form
                !== $definition['research_form']
            ) {
                throw new RuntimeException(
                    'Research-form version '
                        ."{$version->version_code} belongs to "
                        ."{$version->research_form}, not "
                        ."{$definition['research_form']}.",
                );
            }

            $resolved[$phase] = $version;
        }

        /** @var array{
         *     pre_test: ResearchFormVersion,
         *     post_test: ResearchFormVersion
         * } $resolved
         */
        $this->resolvedResearchVersions = $resolved;

        return $this->resolvedResearchVersions;
    }
}
