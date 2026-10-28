<?php

use App\Models\Question;
use App\Models\ResearchFormVersion;
use App\Services\ResearchFormAuditService;
use App\Services\ResearchFormService;
use Database\Seeders\ResearchQuestionSeeder;
use Database\Seeders\TosSeeder;
use Illuminate\Database\QueryException;

beforeEach(function (): void {
    config()->set(
        'citirx.research.allow_unvalidated_forms',
        true,
    );

    $this->seed([
        TosSeeder::class,
        ResearchQuestionSeeder::class,
    ]);
});

it('registers one active version for each research form', function (): void {
    foreach (
        [
            Question::FORM_PRE_TEST_A,
            Question::FORM_POST_TEST_B,
        ] as $form
    ) {
        $version = ResearchFormVersion::query()
            ->where('research_form', $form)
            ->where('is_active', true)
            ->where('active_slot', $form)
            ->sole();

        expect($version->questions()->count())
            ->toBe(60);

        expect(
            $version
                ->questions()
                ->whereNull('form_version_id')
                ->exists(),
        )->toBeFalse();
    }
});

it('loads questions only from the active version', function (): void {
    $service = app(
        ResearchFormService::class,
    );

    $version = $service->activeVersion(
        Question::FORM_PRE_TEST_A,
    );

    $questions = $service->load(
        Question::FORM_PRE_TEST_A,
    );

    expect($questions)->toHaveCount(60);

    expect(
        $questions
            ->pluck('form_version_id')
            ->unique()
            ->values()
            ->all(),
    )->toBe([
        $version->form_version_id,
    ]);
});

it('allows the same position in a different version', function (): void {
    $source = ResearchFormVersion::query()
        ->where(
            'research_form',
            Question::FORM_PRE_TEST_A,
        )
        ->firstOrFail();

    $second = ResearchFormVersion::query()->create([
        'research_form' => Question::FORM_PRE_TEST_A,
        'version_number' => 2,
        'version_code' => 'pre_test_a_v2',
        'status' => ResearchFormVersion::STATUS_DRAFT,
        'is_active' => false,
        'active_slot' => null,
        'lock_version' => 1,
    ]);

    $sourceQuestion = $source
        ->questions()
        ->where('form_position', 1)
        ->firstOrFail();

    $copy = $sourceQuestion->replicate([
        'research_validated_at',
        'research_validated_by',
        'first_served_at',
    ]);

    $copy->form_version_id =
        $second->form_version_id;

    $copy->form_position = 1;
    $copy->save();

    expect(
        Question::query()
            ->where('research_form', Question::FORM_PRE_TEST_A)
            ->where('form_position', 1)
            ->count(),
    )->toBe(2);
});

it('prevents two active versions of the same form', function (): void {
    expect(
        fn () => ResearchFormVersion::query()->create([
            'research_form' => Question::FORM_PRE_TEST_A,
            'version_number' => 2,
            'version_code' => 'pre_test_a_v2',
            'status' => ResearchFormVersion::STATUS_PUBLISHED,
            'is_active' => true,
            'active_slot' => Question::FORM_PRE_TEST_A,
            'lock_version' => 1,
        ]),
    )->toThrow(QueryException::class);
});

it('reports real version binding in the readiness audit', function (): void {
    $report = app(
        ResearchFormAuditService::class,
    )->audit(
        Question::FORM_PRE_TEST_A,
        requireValidation: false,
    );

    expect($report['version_is_bound'])
        ->toBeTrue();

    expect($report['version_binding'])
        ->toBe(
            config(
                'citirx.research.'
                    .'pre_test_form_version',
            ),
        );
});
