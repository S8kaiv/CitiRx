<?php

namespace App\Services;

use App\Models\TosDomain;
use App\Models\User;
use App\Models\UserKnowledgeState;
use RuntimeException;

class ReadinessService
{
    public const BAND_THRESHOLDS = [
        'board_ready' => 75.0,
        'approaching' => 50.0,
        'developing' => 25.0,
    ];

    /**
     * Compute the user's current Board Readiness Estimate.
     *
     * Current formula:
     *
     *     Σ(domain mastery × PRC weight)
     *
     * normalized by the total available PRC weight.
     *
     * The review-consistency term from the research model
     * will be added later when streak tracking is implemented.
     */
    public function compute(User $user): float
    {
        return $this->breakdown($user)['total'];
    }

    /**
     * Compute the full readiness breakdown.
     *
     * Returns:
     *
     * [
     *     'total' => float,
     *     'band' => string,
     *     'total_weight' => float,
     *     'domains' => [...]
     * ]
     */
    public function breakdown(User $user): array
    {
        $domains = TosDomain::query()
            ->with([
                'competencies' => fn ($query) => $query->orderBy('order_index'),
            ])
            ->orderBy('domain_number')
            ->get();

        $states = UserKnowledgeState::query()
            ->where('user_id', $user->user_id)
            ->get()
            ->keyBy('competency_id');

        /*
     * The student has not completed a diagnostic yet.
     */
        if ($states->isEmpty()) {
            return [
                'total' => 0.0,
                'band' => 'at_risk',
                'total_weight' => 0.0,
                'domains' => [],
            ];
        }

        $rows = [];
        $weightedSum = 0.0;
        $totalWeight = 0.0;

        foreach ($domains as $domain) {
            $competencies = $domain->competencies;

            if ($competencies->isEmpty()) {
                continue;
            }

            /*
         * First weighting level:
         *
         * component mastery × component TOS weight
         */
            $componentWeightedSum = 0.0;
            $componentWeightTotal = 0.0;
            $competencyRows = [];

            foreach ($competencies as $competency) {
                $state = $states->get(
                    $competency->competency_id
                );

                if (! $state) {
                    throw new RuntimeException(
                        'Missing knowledge state for competency '
                            .$competency->competency_id
                            .'.'
                    );
                }

                $mastery = (float)
                $state->current_mastery_p_l;

                $componentWeight = (float)
                $competency->tos_weight_percentage;

                if ($componentWeight <= 0) {
                    throw new RuntimeException(
                        $competency->title
                            .' has an invalid TOS weight.'
                    );
                }

                $componentWeightedSum +=
                    $mastery * $componentWeight;

                $componentWeightTotal +=
                    $componentWeight;

                $competencyRows[] = [
                    'competency_id' => $competency->competency_id,

                    'code' => $competency->competency_code,

                    'title' => $competency->title,

                    'order_index' => $competency->order_index,

                    'mastery' => $mastery,

                    'weight' => $componentWeight,
                ];
            }

            if ($componentWeightTotal <= 0) {
                throw new RuntimeException(
                    $domain->domain_name
                        .' has no valid component weights.'
                );
            }

            /*
         * Weighted mastery for this subject.
         *
         * The result remains between 0 and 1.
         */
            $domainMastery =
                $componentWeightedSum
                / $componentWeightTotal;

            $domainWeight = (float)
            $domain->prc_weight_percentage;

            /*
         * Second weighting level:
         *
         * subject mastery × official PRC subject weight
         */
            $weighted =
                $domainMastery * $domainWeight;

            $weightedSum += $weighted;
            $totalWeight += $domainWeight;

            $rows[] = [
                'domain_id' => $domain->domain_id,

                'domain_name' => $domain->domain_name,

                'domain_number' => $domain->domain_number,

                'mastery' => $domainMastery,

                'weight' => $domainWeight,

                'weighted' => $weighted,

                'competencies' => $competencyRows,
            ];
        }

        if ($totalWeight <= 0) {
            return [
                'total' => 0.0,
                'band' => 'at_risk',
                'total_weight' => 0.0,
                'domains' => [],
            ];
        }

        /*
     * Convert each subject contribution into percentage points.
     */
        foreach ($rows as &$row) {
            $row['contribution'] = round(
                ($row['weighted'] / $totalWeight) * 100,
                2
            );
        }

        unset($row);

        $total = round(
            max(
                0,
                min(
                    100,
                    ($weightedSum / $totalWeight) * 100
                )
            ),
            2
        );

        return [
            'total' => $total,
            'band' => $this->band($total),
            'total_weight' => $totalWeight,
            'domains' => $rows,
        ];
    }

    /**
     * Classify readiness into application UI bands.
     *
     * These thresholds are display categories only.
     * They should not be interpreted as validated
     * PhLE passing-score thresholds.
     */
    public function band(float $readiness): string
    {
        if (
            $readiness
            >= self::BAND_THRESHOLDS['board_ready']
        ) {
            return 'board_ready';
        }

        if (
            $readiness
            >= self::BAND_THRESHOLDS['approaching']
        ) {
            return 'approaching';
        }

        if (
            $readiness
            >= self::BAND_THRESHOLDS['developing']
        ) {
            return 'developing';
        }

        return 'at_risk';
    }
}
