<?php

use App\Models\AdminAuditLog;
use App\Models\AssessmentSession;
use App\Models\Question;
use App\Models\ResearchFormVersion;
use App\Models\User;
use App\Services\LegacyResearchCommandGuard;
use App\Services\ResearchFormService;
use App\Services\ResearchFormVersionManagementService;
use Database\Seeders\ResearchQuestionSeeder;
use Database\Seeders\TosSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/*
|--------------------------------------------------------------------------
| Test helpers
|--------------------------------------------------------------------------
*/

function rvwAdministrator(): User
{
    return User::factory()->create([
        'role' => 'admin',
        'password' => 'password',
    ]);
}

function rvwActiveVersion(
    string $form = Question::FORM_PRE_TEST_A,
): ResearchFormVersion {
    return ResearchFormVersion::query()
        ->where('research_form', $form)
        ->where('is_active', true)
        ->where('active_slot', $form)
        ->sole();
}

function rvwCopyDraft(
    User $administrator,
    ResearchFormVersion $source,
    string $versionCode = 'pre_test_a_v2',
): ResearchFormVersion {
    return app(
        ResearchFormVersionManagementService::class,
    )->copyToDraft(
        administrator: $administrator,
        source: $source,
        versionCode: $versionCode,
        expectedLockVersion: $source->fresh()->lock_version,
        reason: 'Create a protected draft for faculty review.',
    );
}

function rvwMakeDraftReviewable(
    ResearchFormVersion $version,
): void {
    Question::query()
        ->where(
            'form_version_id',
            $version->form_version_id,
        )
        ->orderBy('form_position')
        ->get()
        ->each(
            function (Question $question): void {
                $question->update([
                    'question_text' => str_replace(
                        '[DEVELOPMENT MOCK - NOT VALIDATED] ',
                        '',
                        $question->question_text,
                    ),
                ]);
            },
        );
}

function rvwValidateQuestions(
    ResearchFormVersion $version,
    User $validator,
): void {
    /*
     * A bulk fixture update is intentional in this test.
     * Production validation must use the controlled workflow.
     */
    DB::table('questions')
        ->where(
            'form_version_id',
            $version->form_version_id,
        )
        ->update([
            'research_validated_at' => now(),
            'research_validated_by' => $validator->user_id,
        ]);
}

/*
|--------------------------------------------------------------------------
| Setup
|--------------------------------------------------------------------------
*/

beforeEach(function (): void {
    $this->withoutVite();

    /*
     * Official publication must be tested with this disabled.
     */
    config()->set(
        'citirx.research.allow_unvalidated_forms',
        false,
    );

    $this->seed([
        TosSeeder::class,
        ResearchQuestionSeeder::class,
    ]);
});

/*
|--------------------------------------------------------------------------
| Authorization
|--------------------------------------------------------------------------
*/

it('redirects guests from research form version management', function (): void {
    $source = rvwActiveVersion();

    $this->post(
        route(
            'admin.research-forms.versions.copy',
            $source,
        ),
        [
            'version_code' => 'pre_test_a_v2',
            'expected_lock_version' => $source->lock_version,
            'reason' => 'Create a protected version for review.',
        ],
    )->assertRedirect(
        route('login'),
    );
});

it('blocks non administrators from research form version management', function (
    string $role,
): void {
    $user = User::factory()->create([
        'role' => $role,
    ]);

    $source = rvwActiveVersion();

    $this->actingAs($user)
        ->post(
            route(
                'admin.research-forms.versions.copy',
                $source,
            ),
            [
                'version_code' => 'pre_test_a_v2',

                'expected_lock_version' => $source->lock_version,

                'reason' => 'Create a protected version for review.',
            ],
        )
        ->assertForbidden();
})->with([
    'student',
    'faculty',
]);

it('prevents direct version-management service use by non administrators', function (): void {
    $student = User::factory()->create([
        'role' => 'student',
    ]);

    $source = rvwActiveVersion();

    expect(
        fn () => app(
            ResearchFormVersionManagementService::class,
        )->copyToDraft(
            administrator: $student,
            source: $source,
            versionCode: 'pre_test_a_v2',
            expectedLockVersion: $source->lock_version,
            reason: 'Attempt an unauthorized version copy.',
        ),
    )->toThrow(
        LogicException::class,
        'Only administrators',
    );
});

it('allows a verified administrator to view version history', function (): void {
    $administrator = rvwAdministrator();
    $source = rvwActiveVersion();

    $this->actingAs($administrator)
        ->get(
            route(
                'admin.research-forms.index',
            ),
        )
        ->assertOk()
        ->assertSeeText(
            $source->version_code,
        )
        ->assertSeeText(
            Question::FORM_PRE_TEST_A,
        )
        ->assertSeeText(
            Question::FORM_POST_TEST_B,
        );
});

/*
|--------------------------------------------------------------------------
| Copying
|--------------------------------------------------------------------------
*/

it('copies a complete version using new question and choice identifiers', function (): void {
    $administrator = rvwAdministrator();
    $source = rvwActiveVersion();

    $sourceQuestions = Question::query()
        ->where(
            'form_version_id',
            $source->form_version_id,
        )
        ->orderBy('form_position')
        ->get();

    $sourceQuestionIds = $sourceQuestions
        ->pluck('question_id');

    $sourceChoiceIds = DB::table(
        'question_choices',
    )
        ->whereIn(
            'question_id',
            $sourceQuestionIds->all(),
        )
        ->pluck('choice_id');

    $sourceSnapshot = $sourceQuestions
        ->mapWithKeys(
            fn (Question $question): array => [
                $question->question_id => $question->question_text,
            ],
        );

    $draft = rvwCopyDraft(
        $administrator,
        $source,
    );

    $draftQuestions = Question::query()
        ->where(
            'form_version_id',
            $draft->form_version_id,
        )
        ->orderBy('form_position')
        ->get();

    $draftQuestionIds = $draftQuestions
        ->pluck('question_id');

    $draftChoiceIds = DB::table(
        'question_choices',
    )
        ->whereIn(
            'question_id',
            $draftQuestionIds->all(),
        )
        ->pluck('choice_id');

    expect($draft->research_form)
        ->toBe(
            Question::FORM_PRE_TEST_A,
        )
        ->and($draft->version_number)
        ->toBe(2)
        ->and($draft->version_code)
        ->toBe('pre_test_a_v2')
        ->and($draft->status)
        ->toBe(
            ResearchFormVersion::STATUS_DRAFT,
        )
        ->and($draft->is_active)
        ->toBeFalse()
        ->and($draft->active_slot)
        ->toBeNull()
        ->and($draft->created_by)
        ->toBe($administrator->user_id)
        ->and($draftQuestions)
        ->toHaveCount(60)
        ->and($draftChoiceIds)
        ->toHaveCount(240)
        ->and(
            $draftQuestionIds->intersect(
                $sourceQuestionIds,
            ),
        )
        ->toBeEmpty()
        ->and(
            $draftChoiceIds->intersect(
                $sourceChoiceIds,
            ),
        )
        ->toBeEmpty();

    expect(
        $draftQuestions
            ->pluck('form_position')
            ->all(),
    )->toBe(range(1, 60));

    expect(
        $draftQuestions
            ->whereNotNull(
                'research_validated_at',
            ),
    )->toBeEmpty();

    expect(
        $draftQuestions
            ->whereNotNull(
                'research_validated_by',
            ),
    )->toBeEmpty();

    expect(
        $draftQuestions
            ->whereNotNull('first_served_at'),
    )->toBeEmpty();

    $sourceAfterCopy = Question::query()
        ->where(
            'form_version_id',
            $source->form_version_id,
        )
        ->orderBy('form_position')
        ->get()
        ->mapWithKeys(
            fn (Question $question): array => [
                $question->question_id => $question->question_text,
            ],
        );

    expect($sourceAfterCopy->all())
        ->toBe($sourceSnapshot->all())
        ->and(
            $source->fresh()->lock_version,
        )
        ->toBe(2);

    $this->assertDatabaseHas(
        'admin_audit_logs',
        [
            'admin_id' => $administrator->user_id,

            'action' => 'research_form_version.created',

            'target_type' => ResearchFormVersion::class,

            'target_id' => $draft->form_version_id,
        ],
    );
});

it('clears validation and serving metadata when copying a protected source', function (): void {
    $administrator = rvwAdministrator();
    $source = rvwActiveVersion();
    $servedAt = now()->subDay();

    DB::table('questions')
        ->where(
            'form_version_id',
            $source->form_version_id,
        )
        ->update([
            'research_validated_at' => $servedAt,

            'research_validated_by' => $administrator->user_id,

            'first_served_at' => $servedAt,
        ]);

    $source->forceFill([
        'status' => ResearchFormVersion::STATUS_PUBLISHED,

        'first_served_at' => $servedAt,

        'published_at' => $servedAt,

        'published_by' => $administrator->user_id,
    ])->save();

    $draft = rvwCopyDraft(
        $administrator,
        $source,
    );

    $draftQuestions = Question::query()
        ->where(
            'form_version_id',
            $draft->form_version_id,
        )
        ->get();

    expect($draftQuestions)
        ->toHaveCount(60)
        ->and(
            $draftQuestions->every(
                fn (Question $question): bool => $question
                    ->research_validated_at
                        === null
                    && $question
                        ->research_validated_by
                        === null
                    && $question
                        ->first_served_at
                        === null,
            ),
        )
        ->toBeTrue();

    expect(
        Question::query()
            ->where(
                'form_version_id',
                $source->form_version_id,
            )
            ->whereNotNull(
                'research_validated_at',
            )
            ->count(),
    )->toBe(60);

    expect(
        Question::query()
            ->where(
                'form_version_id',
                $source->form_version_id,
            )
            ->whereNotNull('first_served_at')
            ->count(),
    )->toBe(60);
});

it('rejects a version code belonging to another research form', function (): void {
    $administrator = rvwAdministrator();
    $source = rvwActiveVersion();

    $this->actingAs($administrator)
        ->from(
            route(
                'admin.research-forms.index',
            ),
        )
        ->post(
            route(
                'admin.research-forms.versions.copy',
                $source,
            ),
            [
                'version_code' => 'post_test_b_v2',

                'expected_lock_version' => $source->lock_version,

                'reason' => 'Test a mismatched version code safely.',
            ],
        )
        ->assertRedirect(
            route(
                'admin.research-forms.index',
            ),
        )
        ->assertSessionHasErrors(
            'version_code',
        );

    expect(
        ResearchFormVersion::query()
            ->where(
                'version_code',
                'post_test_b_v2',
            )
            ->exists(),
    )->toBeFalse();
});

it('rejects a stale source lock version', function (): void {
    $administrator = rvwAdministrator();
    $source = rvwActiveVersion();

    $this->actingAs($administrator)
        ->from(
            route(
                'admin.research-forms.index',
            ),
        )
        ->post(
            route(
                'admin.research-forms.versions.copy',
                $source,
            ),
            [
                'version_code' => 'pre_test_a_v2',

                'expected_lock_version' => $source->lock_version + 1,

                'reason' => 'Test stale concurrent form changes.',
            ],
        )
        ->assertRedirect(
            route(
                'admin.research-forms.index',
            ),
        )
        ->assertSessionHasErrors(
            'expected_lock_version',
        );

    expect(
        ResearchFormVersion::query()
            ->where(
                'version_code',
                'pre_test_a_v2',
            )
            ->exists(),
    )->toBeFalse();
});

it('refuses to copy an incomplete source version', function (): void {
    $administrator = rvwAdministrator();
    $source = rvwActiveVersion();

    Question::query()
        ->where(
            'form_version_id',
            $source->form_version_id,
        )
        ->orderBy('form_position')
        ->firstOrFail()
        ->update([
            'is_active' => false,
        ]);

    expect(
        fn () => rvwCopyDraft(
            $administrator,
            $source,
        ),
    )->toThrow(
        RuntimeException::class,
        'complete 60-item version',
    );
});

/*
|--------------------------------------------------------------------------
| Review and validation transitions
|--------------------------------------------------------------------------
*/

it('rejects a structurally invalid draft from review', function (): void {
    $administrator = rvwAdministrator();
    $source = rvwActiveVersion();

    /*
     * The copy still contains development placeholders, so the
     * structural audit must reject it.
     */
    $draft = rvwCopyDraft(
        $administrator,
        $source,
    );

    $this->actingAs($administrator)
        ->from(
            route(
                'admin.research-forms.index',
            ),
        )
        ->patch(
            route(
                'admin.research-forms.'
                    .'versions.submit-review',
                $draft,
            ),
            [
                'expected_lock_version' => $draft->lock_version,

                'reason' => 'Attempt review before resolving placeholders.',
            ],
        )
        ->assertRedirect(
            route(
                'admin.research-forms.index',
            ),
        )
        ->assertSessionHasErrors(
            'version',
        );

    expect($draft->fresh()->status)
        ->toBe(
            ResearchFormVersion::STATUS_DRAFT,
        );
});

it('rejects publication when question validation is incomplete', function (): void {
    $administrator = rvwAdministrator();
    $source = rvwActiveVersion();
    $draft = rvwCopyDraft(
        $administrator,
        $source,
    );

    rvwMakeDraftReviewable($draft);

    /*
     * Force the state only to prove that publish() still performs
     * the complete question-level validation audit.
     */
    $draft->forceFill([
        'status' => ResearchFormVersion::STATUS_VALIDATED,
    ])->save();

    $this->actingAs($administrator)
        ->from(
            route(
                'admin.research-forms.index',
            ),
        )
        ->patch(
            route(
                'admin.research-forms.'
                    .'versions.publish',
                $draft,
            ),
            [
                'expected_lock_version' => $draft->lock_version,

                'reason' => 'Test incomplete validation protection.',

                'password' => 'password',
            ],
        )
        ->assertRedirect(
            route(
                'admin.research-forms.index',
            ),
        )
        ->assertSessionHasErrors(
            'version',
        );

    expect($draft->fresh()->is_active)
        ->toBeFalse();
});

it('requires the current administrator password before publication', function (): void {
    $administrator = rvwAdministrator();
    $source = rvwActiveVersion();
    $draft = rvwCopyDraft(
        $administrator,
        $source,
    );

    $draft->forceFill([
        'status' => ResearchFormVersion::STATUS_VALIDATED,
    ])->save();

    $this->actingAs($administrator)
        ->from(
            route(
                'admin.research-forms.index',
            ),
        )
        ->patch(
            route(
                'admin.research-forms.'
                    .'versions.publish',
                $draft,
            ),
            [
                'expected_lock_version' => $draft->lock_version,

                'reason' => 'Test password confirmation before publishing.',

                'password' => 'incorrect-password',
            ],
        )
        ->assertRedirect(
            route(
                'admin.research-forms.index',
            ),
        )
        ->assertSessionHasErrors(
            'password',
        );

    expect($draft->fresh()->is_active)
        ->toBeFalse();
});

it('blocks publication while the development override is enabled', function (): void {
    $administrator = rvwAdministrator();
    $source = rvwActiveVersion();
    $draft = rvwCopyDraft(
        $administrator,
        $source,
    );

    rvwMakeDraftReviewable($draft);

    $service = app(
        ResearchFormVersionManagementService::class,
    );

    $draft = $service->submitForReview(
        administrator: $administrator,
        version: $draft,
        expectedLockVersion: $draft->lock_version,
        reason: 'Submit the complete draft for review.',
    );

    rvwValidateQuestions(
        $draft,
        $administrator,
    );

    $draft = $service->markValidated(
        administrator: $administrator,
        version: $draft,
        expectedLockVersion: $draft->lock_version,
        reason: 'Record completion of faculty validation.',
    );

    config()->set(
        'citirx.research.allow_unvalidated_forms',
        true,
    );

    expect(
        fn () => $service->publish(
            administrator: $administrator,
            version: $draft,
            expectedLockVersion: $draft->lock_version,
            reason: 'Attempt publication with override enabled.',
        ),
    )->toThrow(
        ValidationException::class,
        'Publishing is disabled',
    );

    expect($draft->fresh()->is_active)
        ->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Complete publication workflow
|--------------------------------------------------------------------------
*/

it('publishes one validated version and preserves historical sessions', function (): void {
    $administrator = rvwAdministrator();
    $student = User::factory()->create([
        'role' => 'student',
    ]);

    $source = rvwActiveVersion();

    $sourceQuestionIds = Question::query()
        ->where(
            'form_version_id',
            $source->form_version_id,
        )
        ->orderBy('form_position')
        ->pluck('question_id')
        ->values()
        ->all();

    $startedAt = now()->subHours(2);

    $historicalSession =
        AssessmentSession::query()->create([
            'user_id' => $student->user_id,

            'session_type' => 'diagnostic',
            'research_phase' => 'pre_test',

            'form_version_id' => $source->form_version_id,

            'exam_form_version' => $source->version_code,

            'served_question_ids' => $sourceQuestionIds,

            'target_length' => 60,
            'total_items' => 60,
            'correct_items' => 0,
            'xp_awarded' => 0,

            'started_at' => $startedAt,
            'completed_at' => $startedAt->copy()->addHour(),
        ]);

    $draft = rvwCopyDraft(
        $administrator,
        $source,
    );

    rvwMakeDraftReviewable($draft);

    $this->actingAs($administrator)
        ->from(
            route(
                'admin.research-forms.index',
            ),
        )
        ->patch(
            route(
                'admin.research-forms.'
                    .'versions.submit-review',
                $draft,
            ),
            [
                'expected_lock_version' => $draft->lock_version,

                'reason' => 'Submit completed content for formal review.',
            ],
        )
        ->assertRedirect(
            route(
                'admin.research-forms.index',
            ),
        )
        ->assertSessionHasNoErrors();

    $draft->refresh();

    expect($draft->status)
        ->toBe(
            ResearchFormVersion::STATUS_IN_REVIEW,
        )
        ->and($draft->lock_version)
        ->toBe(2);

    rvwValidateQuestions(
        $draft,
        $administrator,
    );

    $this->actingAs($administrator)
        ->from(
            route(
                'admin.research-forms.index',
            ),
        )
        ->patch(
            route(
                'admin.research-forms.'
                    .'versions.validate',
                $draft,
            ),
            [
                'expected_lock_version' => $draft->lock_version,

                'reason' => 'Faculty validation metadata is complete.',
            ],
        )
        ->assertRedirect(
            route(
                'admin.research-forms.index',
            ),
        )
        ->assertSessionHasNoErrors();

    $draft->refresh();

    expect($draft->status)
        ->toBe(
            ResearchFormVersion::STATUS_VALIDATED,
        )
        ->and($draft->lock_version)
        ->toBe(3);

    $this->actingAs($administrator)
        ->from(
            route(
                'admin.research-forms.index',
            ),
        )
        ->patch(
            route(
                'admin.research-forms.'
                    .'versions.publish',
                $draft,
            ),
            [
                'expected_lock_version' => $draft->lock_version,

                'reason' => 'Publish the validated official research instrument.',

                'password' => 'password',
            ],
        )
        ->assertRedirect(
            route(
                'admin.research-forms.index',
            ),
        )
        ->assertSessionHasNoErrors();

    $source->refresh();
    $draft->refresh();

    expect($source->status)
        ->toBe(
            ResearchFormVersion::STATUS_RETIRED,
        )
        ->and($source->is_active)
        ->toBeFalse()
        ->and($source->active_slot)
        ->toBeNull()
        ->and($draft->status)
        ->toBe(
            ResearchFormVersion::STATUS_PUBLISHED,
        )
        ->and($draft->is_active)
        ->toBeTrue()
        ->and($draft->active_slot)
        ->toBe(
            Question::FORM_PRE_TEST_A,
        )
        ->and($draft->published_by)
        ->toBe($administrator->user_id)
        ->and($draft->published_at)
        ->not->toBeNull()
        ->and($draft->lock_version)
        ->toBe(4);

    expect(
        ResearchFormVersion::query()
            ->where(
                'research_form',
                Question::FORM_PRE_TEST_A,
            )
            ->where('is_active', true)
            ->count(),
    )->toBe(1);

    $loadedQuestions = app(
        ResearchFormService::class,
    )->load(
        Question::FORM_PRE_TEST_A,
    );

    expect($loadedQuestions)
        ->toHaveCount(60)
        ->and(
            $loadedQuestions
                ->pluck('form_version_id')
                ->unique()
                ->values()
                ->all(),
        )
        ->toBe([
            $draft->form_version_id,
        ]);

    /*
     * Publication must not rewrite historical assessment records.
     */
    expect(
        $historicalSession
            ->fresh()
            ->form_version_id,
    )->toBe(
        $source->form_version_id,
    );

    expect(
        $historicalSession
            ->fresh()
            ->exam_form_version,
    )->toBe(
        $source->version_code,
    );

    $this->assertDatabaseHas(
        'admin_audit_logs',
        [
            'action' => 'research_form_version.submitted',

            'target_id' => $draft->form_version_id,
        ],
    );

    $this->assertDatabaseHas(
        'admin_audit_logs',
        [
            'action' => 'research_form_version.validated',

            'target_id' => $draft->form_version_id,
        ],
    );

    $this->assertDatabaseHas(
        'admin_audit_logs',
        [
            'action' => 'research_form_version.published',

            'target_id' => $draft->form_version_id,
        ],
    );

    $auditPayload = AdminAuditLog::query()
        ->where(
            'target_type',
            ResearchFormVersion::class,
        )
        ->where(
            'target_id',
            $draft->form_version_id,
        )
        ->get()
        ->map(
            fn (AdminAuditLog $log): string => (string) json_encode([
                'changes' => $log->changes,

                'reason' => $log->reason,
            ]),
        )
        ->implode(' ');

    $normalizedAuditPayload =
        mb_strtolower($auditPayload);

    expect($normalizedAuditPayload)
        ->not->toContain('question_text')
        ->not->toContain('choice_text')
        ->not->toContain('password')
        ->not->toContain('credential');
});

/*
|--------------------------------------------------------------------------
| Legacy command protection
|--------------------------------------------------------------------------
*/

it('allows the legacy guard while exactly one active version exists', function (): void {
    app(
        LegacyResearchCommandGuard::class,
    )->assertSingleVersion(
        Question::FORM_PRE_TEST_A,
        'Test operation',
    );

    expect(true)->toBeTrue();
});

it('blocks legacy commands after a second version exists', function (): void {
    $administrator = rvwAdministrator();
    $source = rvwActiveVersion();

    rvwCopyDraft(
        $administrator,
        $source,
    );

    expect(
        fn () => app(
            LegacyResearchCommandGuard::class,
        )->assertSingleVersion(
            Question::FORM_PRE_TEST_A,
            'Test operation',
        ),
    )->toThrow(
        RuntimeException::class,
        'multiple registered versions',
    );
});
