<?php

use App\Models\AdminAuditLog;
use App\Models\Question;
use App\Models\ResearchFormVersion;
use App\Models\User;
use App\Services\AdminUserService;
use App\Services\DeanResearchFormReviewService;
use App\Services\ResearchFormBlueprint;
use App\Services\ResearchFormVersionManagementService;
use Database\Seeders\ResearchQuestionSeeder;
use Database\Seeders\TosSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->withoutVite();
    config()->set('citirx.research.allow_unvalidated_forms', false);
    $this->seed([TosSeeder::class, ResearchQuestionSeeder::class]);
});

function deanReviewFixture(string $form = Question::FORM_POST_TEST_B): array
{
    $admin = User::factory()->create(['role' => 'admin', 'password' => 'password']);
    $dean = User::factory()->create(['role' => 'dean', 'password' => 'password']);
    $source = ResearchFormVersion::query()->where('research_form', $form)->where('is_active', true)->sole();
    $versions = app(ResearchFormVersionManagementService::class);
    $draft = $versions->copyToDraft(
        $admin, $source, $form.'_v2', $source->lock_version, 'Prepare a complete version for Dean review.',
    );
    makeResearchFormFixtureReviewable($draft);
    $draft = $versions->submitForReview(
        $admin, $draft, $draft->lock_version, 'Submit complete content for Dean review.',
    );

    return [$admin, $dean, $draft];
}

function deanReviewPayload(ResearchFormVersion $version): array
{
    $reviews = app(DeanResearchFormReviewService::class);

    return [
        'expected_lock_version' => $version->lock_version,
        'expected_digest' => $reviews->digest($reviews->questions($version)),
        'review_confirmed' => '1',
    ];
}

function deanEditorPayload(ResearchFormVersion $version): array
{
    $question = $version->questions()->with('choices')->orderBy('form_position')->firstOrFail();

    return [
        'expected_lock_version' => $version->lock_version, 'form_position' => $question->form_position,
        'competency_id' => $question->competency_id, 'question_text' => 'A revised unique test question requiring fresh approval?',
        'choices' => $question->choices->pluck('choice_text', 'choice_letter')->all(),
        'correct_choice' => $question->choices->firstWhere('is_correct', true)->choice_letter,
        'hypercorrection_rationale' => $question->hypercorrection_rationale,
        'cognitive_level' => $question->cognitive_level,
        'source_reference' => $question->source_reference, 'copyright_permission' => $question->copyright_permission,
    ];
}

it('shows the complete research review only to the dean', function (string $form): void {
    [$admin, $dean, $version] = deanReviewFixture($form);
    foreach ([$admin, User::factory()->create(['role' => 'faculty']), User::factory()->create(['role' => 'student'])] as $other) {
        $this->actingAs($other)->get(route('dean.research-forms.show', $version))->assertForbidden();
    }
    $total = ResearchFormBlueprint::total($form);
    $this->actingAs($dean)->get(route('dean.research-forms.show', $version))->assertOk()
        ->assertSeeText('Blueprint allocation: '.$total.' questions')->assertSeeText('Approve all '.$total.' questions');
})->with([Question::FORM_PRE_TEST_A, Question::FORM_POST_TEST_B]);

it('validates the whole form in one Dean approval and leaves publication to an administrator', function (string $form): void {
    [$admin, $dean, $version] = deanReviewFixture($form);
    $payload = deanReviewPayload($version);
    $this->actingAs($dean)->patch(route('dean.research-forms.approve', $version), $payload)
        ->assertRedirect()->assertSessionHasNoErrors();
    $version->refresh();
    expect($version->status)->toBe(ResearchFormVersion::STATUS_VALIDATED)
        ->and($version->is_active)->toBeFalse()
        ->and($version->dean_approved_by)->toBe($dean->user_id)
        ->and($version->dean_approved_at)->not->toBeNull()
        ->and($version->approved_content_digest)->toBe($payload['expected_digest'])
        ->and($version->questions()->where('research_validated_by', $dean->user_id)->count())->toBe(ResearchFormBlueprint::total($form));

    $published = app(ResearchFormVersionManagementService::class)->publish(
        $admin, $version, $version->lock_version, 'Publish the exact content approved by the Dean.',
    );
    expect($published->status)->toBe(ResearchFormVersion::STATUS_PUBLISHED)
        ->and($published->published_by)->toBe($admin->user_id)->and($published->is_active)->toBeTrue();
    $audit = AdminAuditLog::query()->where('action', 'research_form.dean_approved')->sole();
    expect($audit->admin_id)->toBe($dean->user_id)->and($audit->target_id)->toBe($version->form_version_id);
})->with([Question::FORM_PRE_TEST_A, Question::FORM_POST_TEST_B]);

it('does not give a dean administrator publication permissions', function (): void {
    [, $dean, $version] = deanReviewFixture();
    $version = approveResearchFormFixture($version, $dean);
    $this->actingAs($dean)->patch(route('admin.research-forms.versions.publish', $version), [
        'expected_lock_version' => $version->lock_version, 'reason' => 'Attempt administrator-only publication.',
        'password' => 'password',
    ])->assertForbidden();
    expect(fn () => app(ResearchFormVersionManagementService::class)->publish(
        $dean, $version, $version->lock_version, 'Attempt direct administrator-only publication.',
    ))->toThrow(HttpException::class);
    expect($version->fresh()->status)->toBe(ResearchFormVersion::STATUS_VALIDATED)
        ->and($version->fresh()->is_active)->toBeFalse();
});

it('rejects stale content approval without validating any questions', function (): void {
    [, $dean, $version] = deanReviewFixture();
    $payload = deanReviewPayload($version);
    $payload['expected_digest'] = str_repeat('0', 64);
    $url = route('dean.research-forms.show', $version);
    $this->actingAs($dean)->from($url)->patch(route('dean.research-forms.approve', $version), $payload)
        ->assertRedirect($url)->assertSessionHasErrors('review');
    expect($version->questions()->whereNotNull('research_validated_at')->count())->toBe(0)
        ->and($version->fresh()->dean_approved_at)->toBeNull();
});

it('automatically checks source rights during approval', function (): void {
    [, $dean, $version] = deanReviewFixture();
    $version->questions()->where('form_position', 1)->update(['copyright_permission' => 'pending']);
    $this->actingAs($dean)->patch(route('dean.research-forms.approve', $version), deanReviewPayload($version))
        ->assertSessionHasErrors('review');
    expect($version->fresh()->status)->toBe(ResearchFormVersion::STATUS_IN_REVIEW)
        ->and($version->questions()->whereNotNull('research_validated_at')->count())->toBe(0);
});

it('records revision notes and returns an unapproved form to draft', function (): void {
    [$admin, $dean, $version] = deanReviewFixture();
    $note = 'Revise the answer keys at positions 4 and 27.';
    $this->actingAs($dean)->patch(route('dean.research-forms.return', $version), [
        'expected_lock_version' => $version->lock_version, 'reason' => $note,
    ])->assertRedirect(route('dean.research-forms.index'));
    expect($version->fresh()->status)->toBe(ResearchFormVersion::STATUS_DRAFT)
        ->and($version->fresh()->dean_approved_at)->toBeNull()
        ->and($version->questions()->whereNotNull('research_validated_at')->count())->toBe(0)
        ->and(AdminAuditLog::query()->where('action', 'research_form.dean_returned')->sole()->reason)->toBe($note);
    $this->actingAs($admin)->get(route('research-editor.edit', $version))->assertOk()->assertSeeText($note);
});

it('requires revision notes before returning a form', function (): void {
    [, $dean, $version] = deanReviewFixture();
    $this->actingAs($dean)->patch(route('dean.research-forms.return', $version), [
        'expected_lock_version' => $version->lock_version, 'reason' => '',
    ])->assertSessionHasErrors('reason');
    expect($version->fresh()->status)->toBe(ResearchFormVersion::STATUS_IN_REVIEW);
});

it('clears whole version approval when the editor changes reviewed content', function (): void {
    [$admin, $dean, $version] = deanReviewFixture();
    $version = approveResearchFormFixture($version, $dean);
    $payload = deanEditorPayload($version);
    $this->actingAs($admin)->put(route('research-editor.save', $version), $payload)->assertSessionHasNoErrors();
    $version->refresh();
    expect($version->status)->toBe(ResearchFormVersion::STATUS_DRAFT)
        ->and($version->dean_approved_at)->toBeNull()->and($version->dean_approved_by)->toBeNull()
        ->and($version->approved_content_digest)->toBeNull()
        ->and($version->questions()->whereNotNull('research_validated_at')->count())->toBe(0)
        ->and($version->questions()->where('form_position', 1)->value('question_text'))->toBe($payload['question_text']);
    $this->patch(route('admin.research-forms.versions.publish', $version), [
        'expected_lock_version' => $version->lock_version, 'reason' => 'Attempt publication without fresh review.', 'password' => 'password',
    ])->assertSessionHasErrors('version');
    $version = approveResearchFormFixture($version, $dean);
    expect($version->status)->toBe(ResearchFormVersion::STATUS_VALIDATED)
        ->and($version->questions()->where('research_validated_by', $dean->user_id)->count())->toBe(100);
});

it('refuses publication when approved content is changed outside the editor', function (): void {
    [$admin, $dean, $version] = deanReviewFixture();
    $version = approveResearchFormFixture($version, $dean);
    DB::table('questions')->where('form_version_id', $version->form_version_id)->where('form_position', 1)
        ->update(['question_text' => 'Changed after approval without updating the version lock?']);
    expect(fn () => app(ResearchFormVersionManagementService::class)->publish(
        $admin, $version, $version->lock_version, 'Attempt to publish content different from the approved digest.',
    ))->toThrow(ValidationException::class, 'Fresh Dean approval');
    expect($version->fresh()->is_active)->toBeFalse();
});

it('preserves published content when an editor attempts to save changes', function (): void {
    [$admin, $dean, $version] = deanReviewFixture();
    $version = approveResearchFormFixture($version, $dean);
    $version = app(ResearchFormVersionManagementService::class)->publish($admin, $version, $version->lock_version, 'Publish the protected test version.');
    $before = $version->questions()->where('form_position', 1)->value('question_text');
    $this->actingAs($admin)->put(route('research-editor.save', $version), deanEditorPayload($version))
        ->assertSessionHasErrors('version');
    expect($version->questions()->where('form_position', 1)->value('question_text'))->toBe($before)
        ->and($version->fresh()->status)->toBe(ResearchFormVersion::STATUS_PUBLISHED);
});

it('preserves the Dean role after research approval', function (): void {
    [$admin, $dean, $version] = deanReviewFixture();
    approveResearchFormFixture($version, $dean);
    expect(fn () => app(AdminUserService::class)->updateAssignment(
        $admin, $dean->user_id, ['role' => 'student', 'cohort_id' => null], 'Change role after research review.',
    ))->toThrow(LogicException::class, 'role-specific history');
});
