<?php

use App\Models\Question;
use App\Models\ResearchFormVersion;
use App\Models\User;
use App\Services\AdminUserService;
use App\Services\DeanResearchFormReviewService;
use App\Services\ResearchFormVersionManagementService;
use Database\Seeders\ResearchQuestionSeeder;
use Database\Seeders\TosSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->withoutVite();
    config()->set('citirx.research.allow_unvalidated_forms', false);
    $this->seed([TosSeeder::class, ResearchQuestionSeeder::class]);
});

function deanReviewFixture(): array
{
    $admin = User::factory()->create(['role' => 'admin', 'password' => 'password']);
    $dean = User::factory()->create(['role' => 'dean', 'password' => 'password']);

    $source = ResearchFormVersion::query()
        ->where('research_form', Question::FORM_POST_TEST_B)
        ->where('is_active', true)
        ->sole();

    $versions = app(ResearchFormVersionManagementService::class);
    $draft = $versions->copyToDraft(
        $admin,
        $source,
        'post_test_b_v2',
        $source->lock_version,
        'Prepare a dean-reviewed 100-item Form B.',
    );

    DB::table('questions')
        ->where('form_version_id', $draft->form_version_id)
        ->update([
            'question_text' => DB::raw("REPLACE(question_text, '[DEVELOPMENT MOCK - NOT VALIDATED] ', '')"),
            'hypercorrection_rationale' => 'Test-authored explanation for the correct answer.',
            'source_reference' => 'Original question authored for this test.',
            'copyright_permission' => 'permission confirmed',
        ]);

    $questionIds = $draft->questions()->pluck('question_id');
    DB::table('question_choices')
        ->whereIn('question_id', $questionIds)
        ->update([
            'choice_text' => DB::raw("CONCAT('Answer ', choice_letter)"),
            'distractor_fallacy_note' => null,
        ]);

    $draft = $versions->submitForReview(
        $admin,
        $draft,
        $draft->fresh()->lock_version,
        'The full Form B content is ready for dean review.',
    );

    return [$admin, $dean, $draft];
}

it('shows the 100-item Form B review only to the dean', function (): void {
    [$admin, $dean, $version] = deanReviewFixture();

    $this->actingAs($admin)
        ->get(route('dean.research-forms.show', $version))
        ->assertForbidden();

    $this->actingAs($dean)
        ->get(route('dean.research-forms.show', $version))
        ->assertOk()
        ->assertSeeText('Proposed 100-question allocation')
        ->assertSeeText('Approve all 100 questions');
});

it('requires administrator validation and publication after dean bulk approval', function (): void {
    [$admin, $dean, $version] = deanReviewFixture();
    $reviews = app(DeanResearchFormReviewService::class);
    $digest = $reviews->digest($reviews->questions($version));

    $this->actingAs($dean)
        ->patch(route('dean.research-forms.approve', $version), [
            'expected_lock_version' => $version->lock_version,
            'expected_digest' => $digest,
            'review_confirmed' => '1',
            'current_password' => 'password',
        ])
        ->assertRedirect();

    expect($version->fresh()->status)->toBe(ResearchFormVersion::STATUS_IN_REVIEW)
        ->and($version->fresh()->questions()->where('research_validated_by', $dean->user_id)->count())
        ->toBe(100);

    $versions = app(ResearchFormVersionManagementService::class);
    $validated = $versions->markValidated(
        $admin,
        $version->fresh(),
        $version->fresh()->lock_version,
        'Dean approved all one hundred items.',
    );

    expect($validated->status)->toBe(ResearchFormVersion::STATUS_VALIDATED);

    $published = $versions->publish(
        $admin,
        $validated,
        $validated->lock_version,
        'Publish the dean-approved replacement version.',
    );

    expect($published->status)->toBe(ResearchFormVersion::STATUS_PUBLISHED)
        ->and($published->is_active)->toBeTrue();
});

it('lets the dean validate and publish the Form B they approved', function (): void {
    [, $dean, $version] = deanReviewFixture();
    $reviews = app(DeanResearchFormReviewService::class);

    $reviews->approveAll(
        $dean,
        $version,
        $version->lock_version,
        $reviews->digest($reviews->questions($version)),
    );

    $versions = app(ResearchFormVersionManagementService::class);
    $validated = $versions->markValidated(
        $dean,
        $version->fresh(),
        $version->fresh()->lock_version,
        'Dean validated the approved Form B.',
    );
    $published = $versions->publish(
        $dean,
        $validated,
        $validated->lock_version,
        'Dean published the approved Form B.',
    );

    expect($published->status)->toBe(ResearchFormVersion::STATUS_PUBLISHED)
        ->and($published->published_by)->toBe($dean->user_id)
        ->and($published->is_active)->toBeTrue();
});

it('rejects stale bulk approval without validating any questions', function (): void {
    [, $dean, $version] = deanReviewFixture();

    $this->actingAs($dean)
        ->from(route('dean.research-forms.show', $version))
        ->patch(route('dean.research-forms.approve', $version), [
            'expected_lock_version' => $version->lock_version,
            'expected_digest' => str_repeat('0', 64),
            'review_confirmed' => '1',
            'current_password' => 'password',
        ])
        ->assertRedirect(route('dean.research-forms.show', $version))
        ->assertSessionHasErrors('review');

    expect($version->questions()->whereNotNull('research_validated_at')->count())->toBe(0);
});

it('lets the dean return an unapproved Form B for revision', function (): void {
    [, $dean, $version] = deanReviewFixture();

    $this->actingAs($dean)
        ->patch(route('dean.research-forms.return', $version), [
            'expected_lock_version' => $version->lock_version,
            'reason' => 'Revise the answer keys at positions 4 and 27.',
            'current_password' => 'password',
        ])
        ->assertRedirect(route('dean.research-forms.index'));

    expect($version->fresh()->status)->toBe(ResearchFormVersion::STATUS_DRAFT)
        ->and($version->questions()->whereNotNull('research_validated_at')->count())->toBe(0);
});

it('preserves the Dean role after it has been used to approve Form B', function (): void {
    [$admin, $dean, $version] = deanReviewFixture();
    $reviews = app(DeanResearchFormReviewService::class);

    $reviews->approveAll(
        $dean,
        $version,
        $version->lock_version,
        $reviews->digest($reviews->questions($version)),
    );

    expect(fn () => app(AdminUserService::class)->updateAssignment(
        $admin,
        $dean->user_id,
        ['role' => 'student', 'cohort_id' => null],
        'Change role after research review.',
    ))->toThrow(LogicException::class, 'role-specific history');
});
