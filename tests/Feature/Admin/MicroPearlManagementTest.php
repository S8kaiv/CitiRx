<?php

use App\Models\MicroPearl;
use App\Models\MicroPearlEdit;
use App\Models\TosCompetency;
use App\Models\User;
use App\Services\MicroPearlWorkflowService;
use Carbon\Carbon;
use Database\Seeders\TosSeeder;

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed(TosSeeder::class);
});

afterEach(function (): void {
    Carbon::setTestNow();
});

function pearlManagementCompetency(): TosCompetency
{
    return TosCompetency::query()
        ->where('competency_code', 'PC-01')
        ->firstOrFail();
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function pearlManagementPayload(array $overrides = []): array
{
    $competencyId = $overrides['competency_id']
        ?? pearlManagementCompetency()->competency_id;

    $nextOrder = ((int) MicroPearl::query()
        ->where('competency_id', $competencyId)
        ->max('order_index')) + 1;

    return array_replace([
        'competency_id' => $competencyId,
        'title' => 'Managed Micro Pearl '.$nextOrder,
        'mnemonic_summary' => 'A concise summary for controlled review.',
        'lesson_content' => 'Detailed lesson content for the management workflow.',
        'learning_objectives_text' => "Explain the concept.\nApply the concept safely.",
        'key_points_text' => "Remember the core rule.\nCheck the relevant exception.",
        'common_pitfalls_text' => 'Do not confuse related concepts.',
        'recall_prompts_text' => 'What is the core rule? | Apply the reviewed rule.',
        'source_references_text' => 'Faculty-authored test reference.',
        'estimated_read_seconds' => 120,
        'order_index' => $nextOrder,
        'rights_status' => MicroPearl::RIGHTS_ORIGINAL,
        'is_ai_generated' => false,
        'ai_enrichment_allowed' => false,
    ], $overrides);
}

/**
 * @param  array<string, mixed>  $overrides
 */
function pearlManagementCreateDraft(
    User $creator,
    array $overrides = [],
): MicroPearl {
    return app(MicroPearlWorkflowService::class)->create(
        $creator,
        pearlManagementPayload($overrides),
    );
}

/**
 * @param  array<string, mixed>  $overrides
 */
function pearlManagementCreateInReview(
    User $creator,
    array $overrides = [],
): MicroPearl {
    $pearl = pearlManagementCreateDraft(
        $creator,
        $overrides,
    );

    return app(MicroPearlWorkflowService::class)
        ->submitForReview(
            $creator,
            $pearl,
            (int) $pearl->edit_version,
            'Ready for independent content review.',
        );
}

/**
 * @param  array<string, mixed>  $overrides
 */
function pearlManagementCreatePublished(
    User $creator,
    User $reviewer,
    array $overrides = [],
    ?string $publishedAt = null,
): MicroPearl {
    $pearl = pearlManagementCreateInReview(
        $creator,
        $overrides,
    );

    return app(MicroPearlWorkflowService::class)->publish(
        $reviewer,
        $pearl,
        (int) $pearl->edit_version,
        'Independent review completed successfully.',
        $publishedAt,
    );
}

it('redirects guests from Micro Pearl management', function (): void {
    $this->get(route('content.pearls.index'))
        ->assertRedirect(route('login'));
});

it('blocks students from Micro Pearl management', function (): void {
    $student = User::factory()->create([
        'role' => 'student',
    ]);

    $this->actingAs($student)
        ->get(route('content.pearls.index'))
        ->assertForbidden();
});

it('allows authorized roles to access Micro Pearl management', function (
    string $role,
): void {
    $editor = User::factory()->create([
        'role' => $role,
    ]);

    $this->actingAs($editor)
        ->get(route('content.pearls.index'))
        ->assertOk()
        ->assertSeeText('Micro Pearl Administration')
        ->assertSeeText('Create Draft');
})->with([
    'administrator' => 'admin',
    'faculty' => 'faculty',
]);

it('creates a draft without exposing it to students', function (): void {
    $faculty = User::factory()->create([
        'role' => 'faculty',
    ]);

    $payload = pearlManagementPayload([
        'title' => 'Hidden Management Draft',
    ]);

    $this->actingAs($faculty)
        ->post(route('content.pearls.store'), $payload)
        ->assertRedirect()
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', 'Micro Pearl draft created.');

    $pearl = MicroPearl::query()
        ->where('title', 'Hidden Management Draft')
        ->firstOrFail();

    expect($pearl->content_status)
        ->toBe(MicroPearl::STATUS_DRAFT)
        ->and($pearl->created_by)
        ->toBe($faculty->user_id)
        ->and($pearl->edits)
        ->toHaveCount(1);

    $student = User::factory()->create([
        'role' => 'student',
    ]);

    $this->actingAs($student)
        ->get(route('pearls.index'))
        ->assertOk()
        ->assertDontSeeText($pearl->title);

    $this->get(route('pearls.show', $pearl))
        ->assertNotFound();
});

it('does not submit incomplete content for review', function (): void {
    $faculty = User::factory()->create([
        'role' => 'faculty',
    ]);

    $pearl = pearlManagementCreateDraft($faculty, [
        'lesson_content' => null,
    ]);

    $editUrl = route('content.pearls.edit', [
        'pearl' => $pearl->pearl_id,
    ]);

    $this->actingAs($faculty)
        ->from($editUrl)
        ->patch(
            route('content.pearls.submit-review', [
                'pearl' => $pearl->pearl_id,
            ]),
            [
                'edit_version' => $pearl->edit_version,
                'reason' => 'Request an independent review.',
            ],
        )
        ->assertRedirect($editUrl)
        ->assertSessionHas(
            'error',
            'Complete the lesson, objectives, key points, recall prompts, and references before review.',
        );

    expect($pearl->fresh()->content_status)
        ->toBe(MicroPearl::STATUS_DRAFT);
});

it('blocks review while copyright rights are pending', function (): void {
    $faculty = User::factory()->create([
        'role' => 'faculty',
    ]);

    $pearl = pearlManagementCreateDraft($faculty, [
        'rights_status' => MicroPearl::RIGHTS_PENDING,
    ]);

    $editUrl = route('content.pearls.edit', [
        'pearl' => $pearl->pearl_id,
    ]);

    $this->actingAs($faculty)
        ->from($editUrl)
        ->patch(
            route('content.pearls.submit-review', [
                'pearl' => $pearl->pearl_id,
            ]),
            [
                'edit_version' => $pearl->edit_version,
                'reason' => 'Request an independent review.',
            ],
        )
        ->assertRedirect($editUrl)
        ->assertSessionHas(
            'error',
            'Copyright or source rights must be resolved before review.',
        );

    expect($pearl->fresh()->content_status)
        ->toBe(MicroPearl::STATUS_DRAFT);
});

it('rejects AI content without generation metadata', function (): void {
    $faculty = User::factory()->create([
        'role' => 'faculty',
    ]);

    $payload = pearlManagementPayload([
        'title' => 'Untracked AI Pearl',
        'is_ai_generated' => true,
    ]);

    $this->actingAs($faculty)
        ->from(route('content.pearls.create'))
        ->post(route('content.pearls.store'), $payload)
        ->assertRedirect(route('content.pearls.create'))
        ->assertSessionHasErrors([
            'generation_provider',
            'generation_model',
            'prompt_version',
        ]);

    $this->assertDatabaseMissing('micro_pearls', [
        'title' => 'Untracked AI Pearl',
    ]);
});

it('does not allow an author to approve their own content', function (): void {
    $author = User::factory()->create([
        'role' => 'faculty',
    ]);

    $pearl = pearlManagementCreateInReview($author);

    $editUrl = route('content.pearls.edit', [
        'pearl' => $pearl->pearl_id,
    ]);

    $this->actingAs($author)
        ->from($editUrl)
        ->patch(
            route('content.pearls.publish', [
                'pearl' => $pearl->pearl_id,
            ]),
            [
                'edit_version' => $pearl->edit_version,
                'reason' => 'Approve this reviewed lesson for publication.',
                'current_password' => 'password',
            ],
        )
        ->assertRedirect($editUrl)
        ->assertSessionHas(
            'error',
            'The author cannot approve and publish their own Micro Pearl.',
        );

    $pearl->refresh();

    expect($pearl->content_status)
        ->toBe(MicroPearl::STATUS_IN_REVIEW)
        ->and($pearl->reviewed_by)
        ->toBeNull();
});

it('allows a different authorized reviewer to publish content', function (
    string $reviewerRole,
): void {
    $author = User::factory()->create([
        'role' => 'faculty',
    ]);

    $reviewer = User::factory()->create([
        'role' => $reviewerRole,
    ]);

    $pearl = pearlManagementCreateInReview($author, [
        'title' => 'Independently Reviewed Pearl',
    ]);

    $this->actingAs($reviewer)
        ->from(route('content.pearls.edit', [
            'pearl' => $pearl->pearl_id,
        ]))
        ->patch(
            route('content.pearls.publish', [
                'pearl' => $pearl->pearl_id,
            ]),
            [
                'edit_version' => $pearl->edit_version,
                'reason' => 'Independent review completed successfully.',
                'current_password' => 'password',
            ],
        )
        ->assertRedirect()
        ->assertSessionHasNoErrors()
        ->assertSessionHas(
            'status',
            'Micro Pearl publication approved.',
        );

    $pearl->refresh();

    expect($pearl->content_status)
        ->toBe(MicroPearl::STATUS_PUBLISHED)
        ->and($pearl->reviewed_by)
        ->toBe($reviewer->user_id)
        ->and($pearl->reviewed_at)
        ->not->toBeNull()
        ->and($pearl->published_at)
        ->not->toBeNull();
})->with([
    'faculty reviewer' => 'faculty',
    'administrator reviewer' => 'admin',
]);

it('keeps a future publication hidden from students', function (): void {
    $author = User::factory()->create([
        'role' => 'faculty',
    ]);

    $reviewer = User::factory()->create([
        'role' => 'admin',
    ]);

    $student = User::factory()->create([
        'role' => 'student',
    ]);

    $pearl = pearlManagementCreateInReview($author, [
        'title' => 'Scheduled Hidden Pearl',
    ]);

    $publishAt = now()->addHour();

    $this->actingAs($reviewer)
        ->patch(
            route('content.pearls.publish', [
                'pearl' => $pearl->pearl_id,
            ]),
            [
                'edit_version' => $pearl->edit_version,
                'published_at' => $publishAt->format('Y-m-d H:i:s'),
                'reason' => 'Schedule this reviewed lesson for publication.',
                'current_password' => 'password',
            ],
        )
        ->assertSessionHasNoErrors();

    $pearl->refresh();

    $this->actingAs($student)
        ->get(route('pearls.index'))
        ->assertOk()
        ->assertDontSeeText($pearl->title);

    $this->get(route('pearls.show', $pearl))
        ->assertNotFound();
});

it('exposes a scheduled publication when its publication time is due', function (): void {
    $author = User::factory()->create([
        'role' => 'faculty',
    ]);

    $reviewer = User::factory()->create([
        'role' => 'admin',
    ]);

    $student = User::factory()->create([
        'role' => 'student',
    ]);

    $publishAt = now()->addHour();

    $pearl = pearlManagementCreatePublished(
        $author,
        $reviewer,
        ['title' => 'Scheduled Visible Pearl'],
        $publishAt->toIso8601String(),
    );

    Carbon::setTestNow($publishAt->copy()->addSecond());

    $this->actingAs($student)
        ->get(route('pearls.index'))
        ->assertOk()
        ->assertSeeText($pearl->title);

    $this->get(route('pearls.show', $pearl))
        ->assertOk()
        ->assertSeeText($pearl->lesson_content);
});

it('removes retired content from the student library', function (): void {
    $author = User::factory()->create([
        'role' => 'faculty',
    ]);

    $reviewer = User::factory()->create([
        'role' => 'admin',
    ]);

    $student = User::factory()->create([
        'role' => 'student',
    ]);

    $pearl = pearlManagementCreatePublished(
        $author,
        $reviewer,
        ['title' => 'Pearl To Retire'],
    );

    $this->actingAs($student)
        ->get(route('pearls.index'))
        ->assertSeeText($pearl->title);

    $this->actingAs($reviewer)
        ->patch(
            route('content.pearls.retire', [
                'pearl' => $pearl->pearl_id,
            ]),
            [
                'edit_version' => $pearl->edit_version,
                'reason' => 'Retire outdated reviewed material.',
                'current_password' => 'password',
            ],
        )
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', 'Micro Pearl retired.');

    $pearl->refresh();

    expect($pearl->content_status)
        ->toBe(MicroPearl::STATUS_RETIRED)
        ->and($pearl->retired_at)
        ->not->toBeNull();

    $this->actingAs($student)
        ->get(route('pearls.index'))
        ->assertOk()
        ->assertDontSeeText($pearl->title);

    $this->get(route('pearls.show', $pearl))
        ->assertNotFound();
});

it('rejects a stale edit version', function (): void {
    $faculty = User::factory()->create([
        'role' => 'faculty',
    ]);

    $pearl = pearlManagementCreateDraft($faculty);
    $staleVersion = (int) $pearl->edit_version;

    app(MicroPearlWorkflowService::class)->update(
        $faculty,
        $pearl,
        pearlManagementPayload([
            'competency_id' => $pearl->competency_id,
            'order_index' => $pearl->order_index,
            'title' => 'Updated Concurrent Pearl',
        ]),
        $staleVersion,
        'Update the lesson before review.',
    );

    $pearl->refresh();

    $editUrl = route('content.pearls.edit', [
        'pearl' => $pearl->pearl_id,
    ]);

    $this->actingAs($faculty)
        ->from($editUrl)
        ->patch(
            route('content.pearls.submit-review', [
                'pearl' => $pearl->pearl_id,
            ]),
            [
                'edit_version' => $staleVersion,
                'reason' => 'Submit this stale browser copy.',
            ],
        )
        ->assertRedirect($editUrl)
        ->assertSessionHas(
            'error',
            'This Micro Pearl was changed by another user. Reload the page.',
        );

    expect($pearl->fresh()->content_status)
        ->toBe(MicroPearl::STATUS_DRAFT);
});

it('requires the current password to publish content', function (): void {
    $author = User::factory()->create([
        'role' => 'faculty',
    ]);

    $reviewer = User::factory()->create([
        'role' => 'admin',
    ]);

    $pearl = pearlManagementCreateInReview($author);
    $url = route('content.pearls.publish', [
        'pearl' => $pearl->pearl_id,
    ]);

    $basePayload = [
        'edit_version' => $pearl->edit_version,
        'reason' => 'Independent review completed successfully.',
    ];

    $this->actingAs($reviewer)
        ->patch($url, $basePayload)
        ->assertSessionHasErrors('current_password');

    $this->patch($url, array_merge($basePayload, [
        'current_password' => 'incorrect-password',
    ]))->assertSessionHasErrors('current_password');

    expect($pearl->fresh()->content_status)
        ->toBe(MicroPearl::STATUS_IN_REVIEW);
});

it('requires the current password to retire content', function (): void {
    $author = User::factory()->create([
        'role' => 'faculty',
    ]);

    $reviewer = User::factory()->create([
        'role' => 'admin',
    ]);

    $pearl = pearlManagementCreatePublished(
        $author,
        $reviewer,
    );

    $url = route('content.pearls.retire', [
        'pearl' => $pearl->pearl_id,
    ]);

    $basePayload = [
        'edit_version' => $pearl->edit_version,
        'reason' => 'Retire outdated reviewed material.',
    ];

    $this->actingAs($reviewer)
        ->patch($url, $basePayload)
        ->assertSessionHasErrors('current_password');

    $this->patch($url, array_merge($basePayload, [
        'current_password' => 'incorrect-password',
    ]))->assertSessionHasErrors('current_password');

    expect($pearl->fresh()->content_status)
        ->toBe(MicroPearl::STATUS_PUBLISHED);
});

it('keeps edit history immutable', function (): void {
    $faculty = User::factory()->create([
        'role' => 'faculty',
    ]);

    $pearl = pearlManagementCreateDraft($faculty);
    $edit = $pearl->edits()->firstOrFail();

    expect(
        fn () => $edit->update([
            'reason' => 'Tampered audit reason.',
        ]),
    )->toThrow(
        LogicException::class,
        'Micro Pearl audit records are immutable.',
    );

    expect(
        fn () => $edit->fresh()->delete(),
    )->toThrow(
        LogicException::class,
        'Micro Pearl audit records cannot be deleted.',
    );

    expect(MicroPearlEdit::query()->whereKey($edit->edit_id)->exists())
        ->toBeTrue()
        ->and($edit->fresh()->reason)
        ->toBeNull();
});

it('escapes stored content when showing a published pearl', function (): void {
    $author = User::factory()->create([
        'role' => 'faculty',
    ]);

    $reviewer = User::factory()->create([
        'role' => 'admin',
    ]);

    $student = User::factory()->create([
        'role' => 'student',
    ]);

    $pearl = pearlManagementCreatePublished(
        $author,
        $reviewer,
        [
            'title' => '<script>alert("title")</script>',
            'mnemonic_summary' => '<img src=x onerror=alert("summary")>',
            'lesson_content' => '<script>alert("lesson")</script>',
        ],
    );

    $this->actingAs($student)
        ->get(route('pearls.show', $pearl))
        ->assertOk()
        ->assertDontSee('<script>', false)
        ->assertDontSee('<img src=x', false)
        ->assertSee('&lt;script&gt;', false)
        ->assertSee('&lt;img src=x', false);
});
