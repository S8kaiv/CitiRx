<?php

use App\Services\ReadinessService;

it(
    'classifies readiness percentages into the correct display band',
    function (
        float $readiness,
        string $expectedBand,
    ): void {
        $service = new ReadinessService;

        expect($service->band($readiness))
            ->toBe($expectedBand);
    },
)->with([
    'negative value' => [-1.0, 'at_risk'],
    'zero' => [0.0, 'at_risk'],
    'below developing' => [24.99, 'at_risk'],
    'developing boundary' => [25.0, 'developing'],
    'below approaching' => [49.99, 'developing'],
    'approaching boundary' => [50.0, 'approaching'],
    'below board ready' => [74.99, 'approaching'],
    'board ready boundary' => [75.0, 'board_ready'],
    'above maximum' => [110.0, 'board_ready'],
]);
