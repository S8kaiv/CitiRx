<?php

use App\Models\AdminAuditLog;
use App\Models\AssessmentSession;
use App\Models\Cohort;
use App\Models\PilotControl;
use App\Models\User;
use App\Services\MockBoardService;

beforeEach(function (): void {
    $this->withoutVite();
});

function managementAdmin(): User
{
    return User::factory()->create([
        'role' => 'admin',
    ]);
}

function managementUser(
    string $role = 'student',
    ?Cohort $cohort = null,
): User {
    return User::factory()->create([
        'role' => $role,
        'cohort_id' => $cohort?->cohort_id,
    ]);
}

function managementCohort(
    string $name = 'BS Pharmacy Batch 2026',
): Cohort {
    return Cohort::query()->create([
        'cohort_name' => $name,
        'academic_year' => '2025-2026',
        'target_phle_date' => '2026-11-15',
    ]);
}

it('redirects guests from admin user management', function (): void {
    $this->get(route('admin.users.index'))
        ->assertRedirect(route('login'));
});

it('blocks students and faculty from admin management', function (
    string $role,
): void {
    $user = managementUser($role);

    $this->actingAs($user)
        ->get(route('admin.users.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('admin.cohorts.index'))
        ->assertForbidden();
})->with([
    'student',
    'faculty',
]);

it('allows an administrator to assign faculty to an active cohort', function (): void {
    $admin = managementAdmin();
    $faculty = managementUser('faculty');
    $cohort = managementCohort();

    $this->actingAs($admin)
        ->put(
            route(
                'admin.users.update',
                ['userId' => $faculty->user_id],
            ),
            [
                'role' => 'faculty',
                'cohort_id' => $cohort->cohort_id,
                'reason' => 'Assign faculty to the current batch.',
            ],
        )
        ->assertSessionHasNoErrors();

    expect($faculty->fresh()->cohort_id)
        ->toBe($cohort->cohort_id);

    $this->assertDatabaseHas(
        'admin_audit_logs',
        [
            'admin_id' => $admin->user_id,
            'action' => 'user.assignment_updated',
            'target_id' => $faculty->user_id,
        ],
    );
});

it('rejects assignment to an archived cohort', function (): void {
    $admin = managementAdmin();
    $faculty = managementUser('faculty');
    $cohort = managementCohort();

    $cohort->forceFill([
        'archived_at' => now(),
    ])->save();

    $this->actingAs($admin)
        ->put(
            route(
                'admin.users.update',
                ['userId' => $faculty->user_id],
            ),
            [
                'role' => 'faculty',
                'cohort_id' => $cohort->cohort_id,
                'reason' => 'Attempt archived assignment.',
            ],
        )
        ->assertSessionHasErrors('cohort_id');

    expect($faculty->fresh()->cohort_id)->toBeNull();
});

it('automatically clears the cohort when assigning the admin role', function (): void {
    $admin = managementAdmin();
    $cohort = managementCohort();
    $faculty = managementUser('faculty', $cohort);

    $this->actingAs($admin)
        ->put(
            route(
                'admin.users.update',
                ['userId' => $faculty->user_id],
            ),
            [
                'role' => 'admin',
                'cohort_id' => $cohort->cohort_id,
                'reason' => 'Promote faculty to administrator.',
            ],
        )
        ->assertSessionHasNoErrors();

    $faculty->refresh();

    expect($faculty->role)->toBe('admin')
        ->and($faculty->cohort_id)->toBeNull();
});

it('prevents an administrator from demoting themself', function (): void {
    $admin = managementAdmin();

    $this->actingAs($admin)
        ->put(
            route(
                'admin.users.update',
                ['userId' => $admin->user_id],
            ),
            [
                'role' => 'student',
                'cohort_id' => null,
                'reason' => 'Unsafe self-demotion attempt.',
            ],
        )
        ->assertSessionHas('error');

    expect($admin->fresh()->role)->toBe('admin');
});

it('protects cohort history after an official assessment starts', function (): void {
    $admin = managementAdmin();
    $oldCohort = managementCohort('Original Cohort');
    $newCohort = managementCohort('Replacement Cohort');
    $student = managementUser('student', $oldCohort);

    AssessmentSession::query()->create([
        'user_id' => $student->user_id,
        'session_type' => 'diagnostic',
        'research_phase' => 'pre_test',
        'started_at' => now(),
    ]);

    $this->actingAs($admin)
        ->put(
            route(
                'admin.users.update',
                ['userId' => $student->user_id],
            ),
            [
                'role' => 'student',
                'cohort_id' => $newCohort->cohort_id,
                'reason' => 'Attempt cohort transfer.',
            ],
        )
        ->assertSessionHas('error');

    expect($student->fresh()->cohort_id)
        ->toBe($oldCohort->cohort_id);
});

it('soft deletes and restores an account with audit records', function (): void {
    $admin = managementAdmin();
    $student = managementUser();

    $this->actingAs($admin)
        ->delete(
            route(
                'admin.users.destroy',
                ['userId' => $student->user_id],
            ),
            [
                'reason' => 'Requested account deactivation.',
            ],
        )
        ->assertRedirect(route('admin.users.index'));

    expect($student->fresh()->trashed())->toBeTrue();

    $this->actingAs($admin)
        ->patch(
            route(
                'admin.users.restore',
                ['userId' => $student->user_id],
            ),
            [
                'reason' => 'Account restoration approved.',
            ],
        )
        ->assertSessionHasNoErrors();

    expect($student->fresh()->trashed())->toBeFalse();

    expect(
        AdminAuditLog::query()
            ->where(
                'target_id',
                $student->user_id,
            )
            ->count(),
    )->toBe(2);
});

it('archives a cohort without removing its users', function (): void {
    $admin = managementAdmin();
    $cohort = managementCohort();
    $student = managementUser('student', $cohort);

    $this->actingAs($admin)
        ->patch(
            route(
                'admin.cohorts.archive',
                ['cohort' => $cohort],
            ),
            [
                'reason' => 'The research batch has concluded.',
            ],
        )
        ->assertSessionHasNoErrors();

    expect($cohort->fresh()->archived_at)->not->toBeNull()
        ->and($student->fresh()->cohort_id)
        ->toBe($cohort->cohort_id);
});

it('blocks mock board availability for archived cohorts', function (): void {
    PilotControl::query()->update([
        'phase' => PilotControl::PHASE_POST_TEST_OPEN,
    ]);

    $cohort = managementCohort();

    $cohort->forceFill([
        'post_test_opens_at' => now()->subHour(),
        'post_test_closes_at' => now()->addHour(),
        'archived_at' => now(),
    ])->save();

    $student = User::factory()->create([
        'role' => 'student',
        'cohort_id' => $cohort->cohort_id,
        'is_diagnostic_completed' => true,
    ]);

    $status = app(
        MockBoardService::class,
    )->status($student);

    expect($status['can_start'])->toBeFalse()
        ->and($status['reason'])->toContain(
            'not currently available',
        );
});

it('requires a complete form b before scheduling a window', function (): void {
    $admin = managementAdmin();
    $cohort = managementCohort();

    $this->actingAs($admin)
        ->patch(
            route(
                'admin.cohorts.post-test-window',
                ['cohort' => $cohort],
            ),
            [
                'post_test_opens_at' => now()
                    ->addDay()
                    ->format('Y-m-d H:i:s'),
                'post_test_closes_at' => now()
                    ->addDays(2)
                    ->format('Y-m-d H:i:s'),
                'reason' => 'Schedule the official post-test.',
            ],
        )
        ->assertSessionHas('error');

    expect($cohort->fresh()->post_test_opens_at)
        ->toBeNull();
});

it('rejects an invalid post-test date range', function (): void {
    $admin = managementAdmin();
    $cohort = managementCohort();

    $this->actingAs($admin)
        ->patch(
            route(
                'admin.cohorts.post-test-window',
                ['cohort' => $cohort],
            ),
            [
                'post_test_opens_at' => '2026-11-10 10:00:00',
                'post_test_closes_at' => '2026-11-10 09:00:00',
                'reason' => 'Invalid date range test.',
            ],
        )
        ->assertSessionHasErrors(
            'post_test_closes_at',
        );
});

it('filters active deleted and all users correctly', function (): void {
    $admin = managementAdmin();

    $activeUser = User::factory()->create([
        'role' => 'student',
        'email' => 'active-filter@citirx.test',
    ]);

    $deletedUser = User::factory()->create([
        'role' => 'student',
        'email' => 'deleted-filter@citirx.test',
    ]);

    $deletedUser->delete();

    $this->actingAs($admin)
        ->get(
            route(
                'admin.users.index',
                ['status' => 'active'],
            ),
        )
        ->assertOk()
        ->assertSeeText($activeUser->email)
        ->assertDontSeeText($deletedUser->email);

    $this->actingAs($admin)
        ->get(
            route(
                'admin.users.index',
                ['status' => 'deleted'],
            ),
        )
        ->assertOk()
        ->assertSeeText($deletedUser->email)
        ->assertDontSeeText($activeUser->email);

    $this->actingAs($admin)
        ->get(
            route(
                'admin.users.index',
                ['status' => 'all'],
            ),
        )
        ->assertOk()
        ->assertSeeText($activeUser->email)
        ->assertSeeText($deletedUser->email);
});

it('allows administrators to open a deleted account management page', function (): void {
    $admin = managementAdmin();

    $deletedUser = User::factory()->create([
        'role' => 'student',
        'email' => 'deleted-edit@citirx.test',
    ]);

    $deletedUser->delete();

    $this->actingAs($admin)
        ->get(
            route(
                'admin.users.edit',
                ['userId' => $deletedUser->user_id],
            ),
        )
        ->assertOk()
        ->assertSeeText($deletedUser->email)
        ->assertSeeText('Deleted')
        ->assertSeeText('Restore Account');
});

it('prevents the final administrator account from being deleted', function (): void {
    $admin = managementAdmin();

    $this->actingAs($admin)
        ->delete(
            route(
                'admin.users.destroy',
                ['userId' => $admin->user_id],
            ),
            [
                'reason' => 'Attempt to remove final administrator.',
            ],
        )
        ->assertSessionHas('error');

    $storedAdmin = User::withTrashed()->findOrFail(
        $admin->user_id,
    );

    expect($storedAdmin->deleted_at)->toBeNull()
        ->and($storedAdmin->role)->toBe('admin');
});

it('rejects a duplicate cohort name within the same academic year', function (): void {
    $admin = managementAdmin();

    Cohort::query()->create([
        'cohort_name' => 'BS Pharmacy Batch 2027',
        'academic_year' => '2026-2027',
        'target_phle_date' => '2027-11-15',
    ]);

    $this->actingAs($admin)
        ->post(
            route('admin.cohorts.store'),
            [
                'cohort_name' => 'BS Pharmacy Batch 2027',
                'academic_year' => '2026-2027',
                'target_phle_date' => '2027-11-15',
            ],
        )
        ->assertSessionHasErrors('cohort_name');

    expect(
        Cohort::query()
            ->where(
                'cohort_name',
                'BS Pharmacy Batch 2027',
            )
            ->where(
                'academic_year',
                '2026-2027',
            )
            ->count(),
    )->toBe(1);
});

it('allows the same cohort name in a different academic year', function (): void {
    $admin = managementAdmin();

    Cohort::query()->create([
        'cohort_name' => 'BS Pharmacy',
        'academic_year' => '2025-2026',
    ]);

    $this->actingAs($admin)
        ->post(
            route('admin.cohorts.store'),
            [
                'cohort_name' => 'BS Pharmacy',
                'academic_year' => '2026-2027',
                'target_phle_date' => null,
            ],
        )
        ->assertSessionHasNoErrors();

    expect(
        Cohort::query()
            ->where(
                'cohort_name',
                'BS Pharmacy',
            )
            ->count(),
    )->toBe(2);
});

it('allows an administrator to clear a post test window', function (): void {
    $admin = managementAdmin();

    $cohort = managementCohort();

    $cohort->forceFill([
        'post_test_opens_at' => now()->subHour(),
        'post_test_closes_at' => now()->addHour(),
    ])->save();

    $this->actingAs($admin)
        ->patch(
            route(
                'admin.cohorts.post-test-window',
                ['cohort' => $cohort],
            ),
            [
                'clear_window' => true,
                'reason' => 'Cancel the scheduled research post-test.',
            ],
        )
        ->assertSessionHasNoErrors()
        ->assertSessionHas(
            'status',
            'Post-test schedule cleared.',
        );

    $cohort->refresh();

    expect($cohort->post_test_opens_at)->toBeNull()
        ->and($cohort->post_test_closes_at)->toBeNull();

    $this->assertDatabaseHas(
        'admin_audit_logs',
        [
            'admin_id' => $admin->user_id,
            'action' => 'cohort.post_test_window_cleared',
            'target_id' => $cohort->cohort_id,
        ],
    );
});

it('does not reopen a post test window when a cohort is unarchived', function (): void {
    $admin = managementAdmin();

    $cohort = managementCohort();

    /*
     * A future schedule should be cancelled when the
     * cohort is archived.
     */
    $cohort->forceFill([
        'post_test_opens_at' => now()->addDay(),
        'post_test_closes_at' => now()->addDays(2),
    ])->save();

    $this->actingAs($admin)
        ->patch(
            route(
                'admin.cohorts.archive',
                ['cohort' => $cohort],
            ),
            [
                'reason' => 'Archive the completed research cohort.',
            ],
        )
        ->assertSessionHasNoErrors();

    $cohort->refresh();

    expect($cohort->archived_at)->not->toBeNull()
        ->and($cohort->post_test_opens_at)->toBeNull()
        ->and($cohort->post_test_closes_at)->toBeNull();

    $this->actingAs($admin)
        ->patch(
            route(
                'admin.cohorts.unarchive',
                ['cohort' => $cohort],
            ),
            [
                'reason' => 'Restore cohort for historical viewing.',
            ],
        )
        ->assertSessionHasNoErrors();

    $cohort->refresh();

    expect($cohort->archived_at)->toBeNull()
        ->and($cohort->post_test_opens_at)->toBeNull()
        ->and($cohort->post_test_closes_at)->toBeNull()
        ->and($cohort->postTestWindowStatus())->toBe('closed');
});

it('restricts the audit page to administrators and paginates results', function (): void {
    $admin = managementAdmin();
    $student = managementUser('student');
    $cohort = managementCohort();

    foreach (range(1, 45) as $number) {
        AdminAuditLog::query()->create([
            'admin_id' => $admin->user_id,
            'action' => "test.audit_action_{$number}",
            'target_type' => Cohort::class,
            'target_id' => $cohort->cohort_id,
            'changes' => [
                'test_number' => [
                    'old' => null,
                    'new' => $number,
                ],
            ],
            'reason' => "Audit pagination record {$number}.",
        ]);
    }

    $this->actingAs($student)
        ->get(route('admin.audit-logs.index'))
        ->assertForbidden();

    $this->actingAs($admin)
        ->get(route('admin.audit-logs.index'))
        ->assertOk()
        ->assertViewHas(
            'logs',
            function ($logs): bool {
                return $logs->count() === 40
                    && $logs->total() === 45
                    && $logs->lastPage() === 2;
            },
        );

    $this->actingAs($admin)
        ->get(
            route(
                'admin.audit-logs.index',
                ['page' => 2],
            ),
        )
        ->assertOk()
        ->assertViewHas(
            'logs',
            fn ($logs): bool => $logs->count() === 5,
        );
});

it('redirects guests from the administrative audit page', function (): void {
    $this->get(
        route('admin.audit-logs.index'),
    )->assertRedirect(
        route('login'),
    );
});

it('never stores credentials in administrative audit changes', function (): void {
    $admin = managementAdmin();
    $cohort = managementCohort();
    $faculty = managementUser('faculty');

    $this->actingAs($admin)
        ->put(
            route(
                'admin.users.update',
                ['userId' => $faculty->user_id],
            ),
            [
                'role' => 'faculty',
                'cohort_id' => $cohort->cohort_id,
                'reason' => 'Assign faculty to the research cohort.',
            ],
        )
        ->assertSessionHasNoErrors();

    $log = AdminAuditLog::query()
        ->where(
            'target_id',
            $faculty->user_id,
        )
        ->where(
            'action',
            'user.assignment_updated',
        )
        ->firstOrFail();

    $serializedChanges = strtolower(
        json_encode(
            $log->changes,
            JSON_THROW_ON_ERROR,
        ),
    );

    expect(array_keys($log->changes))
        ->toContain('cohort_id')
        ->not->toContain(
            'password',
            'remember_token',
            'email_verified_at',
        );

    expect($serializedChanges)
        ->not->toContain('password')
        ->not->toContain('remember_token')
        ->not->toContain('mail_password')
        ->not->toContain('api_key')
        ->not->toContain('secret');
});
