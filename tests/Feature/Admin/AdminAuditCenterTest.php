<?php

use App\Models\AdminAuditLog;
use App\Models\Cohort;
use App\Models\User;
use App\Services\AdminAuditService;
use Illuminate\Support\Carbon;

beforeEach(function (): void {
    $this->withoutVite();
});

function auditCenterAdmin(array $overrides = []): User
{
    return User::factory()->create(array_merge([
        'role' => 'admin',
    ], $overrides));
}

function auditCenterCohort(string $name = 'Audit Test Cohort'): Cohort
{
    return Cohort::query()->create([
        'cohort_name' => $name,
        'academic_year' => '2026-2027',
        'target_phle_date' => '2027-11-15',
    ]);
}

function auditCenterLog(
    User $administrator,
    Cohort $cohort,
    string $action = 'cohort.updated',
    array $changes = [],
): AdminAuditLog {
    return AdminAuditLog::query()->create([
        'admin_id' => $administrator->user_id,
        'actor_name' => $administrator->fullName(),
        'action' => $action,
        'target_type' => Cohort::class,
        'target_id' => $cohort->cohort_id,
        'changes' => $changes !== [] ? $changes : null,
        'reason' => 'Audit center test record.',
    ]);
}

test('guests are redirected from the audit center', function (): void {
    $this->get(route('admin.audit-logs.index'))
        ->assertRedirect(route('login'));
});

test('non administrators cannot access the audit center', function (
    string $role,
): void {
    $user = User::factory()->create([
        'role' => $role,
    ]);

    $this->actingAs($user)
        ->get(route('admin.audit-logs.index'))
        ->assertForbidden();
})->with([
    'student',
    'faculty',
]);

test('administrators can filter audit records', function (): void {
    Carbon::setTestNow('2026-10-27 12:00:00');

    $administrator = auditCenterAdmin();
    $otherAdministrator = auditCenterAdmin();
    $cohort = auditCenterCohort();
    $wanted = auditCenterLog(
        $administrator,
        $cohort,
        'cohort.updated',
    );

    auditCenterLog(
        $otherAdministrator,
        $cohort,
        'cohort.archived',
    );

    $this->actingAs($administrator)
        ->get(route('admin.audit-logs.index', [
            'date_from' => '2026-10-27',
            'date_to' => '2026-10-27',
            'admin_id' => $administrator->user_id,
            'action' => 'cohort.updated',
            'target_type' => Cohort::class,
            'target_id' => $cohort->cohort_id,
        ]))
        ->assertOk()
        ->assertSee($wanted->action)
        ->assertViewHas(
            'logs',
            fn ($logs): bool => $logs->total() === 1
                && $logs->first()?->is($wanted) === true,
        );
});

test('the audit center paginates records', function (): void {
    $administrator = auditCenterAdmin();
    $cohort = auditCenterCohort();

    foreach (range(1, 45) as $number) {
        auditCenterLog(
            $administrator,
            $cohort,
            "test.action.{$number}",
        );
    }

    $this->actingAs($administrator)
        ->get(route('admin.audit-logs.index'))
        ->assertOk()
        ->assertViewHas(
            'logs',
            fn ($logs): bool => $logs->total() === 45
                && $logs->count() === 40,
        );
});

test('target records have links when they still exist', function (): void {
    $administrator = auditCenterAdmin();
    $cohort = auditCenterCohort();

    auditCenterLog($administrator, $cohort);

    $this->actingAs($administrator)
        ->get(route('admin.audit-logs.index'))
        ->assertOk()
        ->assertSee(
            route(
                'admin.cohorts.show',
                ['cohort' => $cohort],
            ),
            false,
        );
});

test('actor names remain historical after account changes', function (): void {
    $viewer = auditCenterAdmin();
    $actor = auditCenterAdmin([
        'first_name' => 'Original',
        'middle_name' => null,
        'last_name' => 'Administrator',
    ]);
    $cohort = auditCenterCohort();

    app(AdminAuditService::class)->record(
        $actor,
        'cohort.updated',
        $cohort,
    );

    $actor->forceFill([
        'first_name' => 'Changed',
        'role' => 'faculty',
    ])->save();
    $actor->delete();

    $this->actingAs($viewer)
        ->get(route('admin.audit-logs.index'))
        ->assertOk()
        ->assertSee('Original Administrator')
        ->assertViewHas(
            'logs',
            fn ($logs): bool => $logs->count() === 1
                && $logs->first()?->actorDisplayName()
                === 'Original Administrator',
        );
});

test('csv export prevents formulas and excludes change values', function (): void {
    $viewer = auditCenterAdmin();
    $actor = auditCenterAdmin([
        'first_name' => '=2+2',
        'middle_name' => null,
        'last_name' => 'Administrator',
    ]);
    $cohort = auditCenterCohort();

    auditCenterLog(
        $actor,
        $cohort,
        'cohort.updated',
        [
            'password' => [
                'old' => 'old-secret',
                'new' => 'new-secret',
            ],
        ],
    );

    $response = $this->actingAs($viewer)
        ->get(route('admin.audit-logs.export'));

    $response->assertOk();

    $csv = $response->streamedContent();

    expect($csv)
        ->toContain("'=2+2 Administrator")
        ->toContain('password')
        ->not->toContain('old-secret')
        ->not->toContain('new-secret');
});

test('audit records cannot be updated or deleted', function (): void {
    $administrator = auditCenterAdmin();
    $cohort = auditCenterCohort();
    $log = auditCenterLog($administrator, $cohort);

    expect(
        fn () => $log->update([
            'action' => 'tampered',
        ]),
    )->toThrow(
        'LogicException',
        'Administrative audit records are immutable.',
    );

    expect(
        fn () => $log->delete(),
    )->toThrow(
        'LogicException',
        'Administrative audit records are immutable.',
    );
});

test('the audit center has an empty state', function (): void {
    $administrator = auditCenterAdmin();

    $this->actingAs($administrator)
        ->get(route('admin.audit-logs.index'))
        ->assertOk()
        ->assertSee('No audit records match these filters.');
});
