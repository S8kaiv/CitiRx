<?php

use App\Models\Question;
use App\Services\QuestionLockService;
use Carbon\CarbonImmutable;
use Tests\TestCase;

uses(TestCase::class);

it('allows an unserved and unvalidated question to be edited', function () {
    $question = new Question;

    $service = new QuestionLockService;

    expect($service->isLocked($question))
        ->toBeFalse()
        ->and($service->canReactivate($question))
        ->toBeTrue()
        ->and($service->lockReason($question))
        ->toBeNull();
});

it('locks a served question', function () {
    $question = new Question;

    $question->forceFill([
        'first_served_at' => CarbonImmutable::parse(
            '2026-10-09 10:00:00',
        ),
    ]);

    $service = new QuestionLockService;

    expect($service->isLocked($question))
        ->toBeTrue()
        ->and($service->canReactivate($question))
        ->toBeFalse()
        ->and($service->lockReason($question))
        ->toContain('first served');
});

it(
    'locks a question when complete validation metadata exists',
    function () {
        $question = new Question;

        $question->forceFill([
            'research_validated_at' => CarbonImmutable::parse(
                '2026-10-09 10:00:00',
            ),
            'research_validated_by' => '01a00000-0000-7000-8000-000000000001',
        ]);

        $service = new QuestionLockService;

        expect($service->isLocked($question))
            ->toBeTrue()
            ->and($service->canReactivate($question))
            ->toBeFalse()
            ->and($service->lockReason($question))
            ->toContain('validation metadata');
    },
);

it(
    'safely locks a question with partial validation metadata',
    function (
        array $metadata,
    ): void {
        $question = new Question;

        $question->forceFill($metadata);

        $service = new QuestionLockService;

        expect($service->isLocked($question))
            ->toBeTrue()
            ->and($service->canReactivate($question))
            ->toBeFalse();
    },
)->with([
    'validation date only' => [[
        'research_validated_at' => CarbonImmutable::parse(
            '2026-10-09 10:00:00',
        ),
    ]],
    'validator only' => [[
        'research_validated_by' => '01a00000-0000-7000-8000-000000000001',
    ]],
]);
