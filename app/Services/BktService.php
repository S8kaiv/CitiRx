<?php

namespace App\Services;

use InvalidArgumentException;

class BktService
{
    public const DEFAULT_INITIAL_MASTERY = 0.10;

    public const DEFAULT_P_GUESS = 0.25;

    public const DEFAULT_P_SLIP = 0.10;

    public const DEFAULT_P_TRANSIT = 0.10;

    public const MASTERY_THRESHOLD = 0.85;

    public function initialPrior(int $correct, int $total): float
    {
        if ($total < 0 || $correct < 0 || $correct > $total) {
            throw new InvalidArgumentException(
                'Correct answers must be between 0 and the total number of items.'
            );
        }

        if ($total === 0) {
            return 0.10;
        }

        $prior = ($correct / $total) * 0.90;

        return max(0.10, min(0.90, $prior));
    }

    public function updateMastery(
        float $priorMastery,
        bool $isCorrect,
        float $pTransit = self::DEFAULT_P_TRANSIT,
        float $pGuess = self::DEFAULT_P_GUESS,
        float $pSlip = self::DEFAULT_P_SLIP
    ): float {
        $priorMastery = max(0.0001, min(0.9999, $priorMastery));

        if ($isCorrect) {
            $numerator = $priorMastery * (1.0 - $pSlip);

            $denominator =
                $numerator +
                (1.0 - $priorMastery) * $pGuess;
        } else {
            $numerator = $priorMastery * $pSlip;

            $denominator =
                $numerator +
                (1.0 - $priorMastery) * (1.0 - $pGuess);
        }

        $posterior = $denominator > 0.0
            ? $numerator / $denominator
            : $priorMastery;

        $projected =
            $posterior +
            (1.0 - $posterior) * $pTransit;

        return max(0.0, min(1.0, $projected));
    }

    public function classifyMastery(float $mastery): string
    {
        if ($mastery >= self::MASTERY_THRESHOLD) {
            return 'mastered';
        }

        if ($mastery >= 0.50) {
            return 'developing';
        }

        if ($mastery >= 0.25) {
            return 'emerging';
        }

        return 'beginning';
    }
}
