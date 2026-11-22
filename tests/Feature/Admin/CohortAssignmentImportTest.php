<?php

use App\Models\AdminAuditLog;
use App\Models\AssessmentSession;
use App\Models\Cohort;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->withoutVite();
});

function bulkCohort(array $overrides = []): Cohort
{
    return Cohort::query()->create(array_merge([
        'cohort_name' => fake()->unique()->words(3, true),
        'academic_year' => '2026-2027',
        'target_phle_date' => now()->addYear()->toDateString(),
    ], $overrides));
}

function bulkCohortPayload(array $students, Cohort $cohort): array
{
    return [
        'student_ids' => array_map(fn (User $student) => $student->user_id, $students),
        'cohort_id' => $cohort->cohort_id,
        'original_cohorts' => collect($students)->mapWithKeys(fn (User $student) => [
            $student->user_id => $student->cohort_id,
        ])->all(),
    ];
}

function bulkCohortAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

test('guests are redirected from bulk cohort assignment', function (): void {
    $this->post(route('admin.users.assign-cohort'))->assertRedirect(route('login'));
});

test('non administrators cannot assign cohorts', function (string $role): void {
    $this->actingAs(User::factory()->create(['role' => $role]))
        ->post(route('admin.users.assign-cohort'))->assertForbidden();
})->with(['student', 'faculty', 'dean']);

test('the user list exposes selection without changing accounts', function (): void {
    $student = User::factory()->create(['role' => 'student', 'cohort_id' => null]);
    $this->actingAs(bulkCohortAdmin())->get(route('admin.users.index'))
        ->assertOk()->assertSee('student_ids[]', false)->assertSeeText('Select all eligible on this page');
    expect($student->fresh()->cohort_id)->toBeNull();
});

test('an administrator assigns only the selected students without a password or reason', function (): void {
    $cohort = bulkCohort();
    $students = User::factory()->count(2)->create(['role' => 'student', 'cohort_id' => null]);
    $other = User::factory()->create(['role' => 'student', 'cohort_id' => null]);
    $this->actingAs(bulkCohortAdmin())->post(route('admin.users.assign-cohort'), bulkCohortPayload($students->all(), $cohort))
        ->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('status', '2 students moved to the selected cohort.');
    foreach ($students as $student) {
        expect($student->fresh()->cohort_id)->toBe($cohort->cohort_id);
    }
    expect($other->fresh()->cohort_id)->toBeNull();
});

test('bulk assignment rejects unknown student identifiers', function (): void {
    $id = (string) Str::uuid();
    $this->actingAs(bulkCohortAdmin())->post(route('admin.users.assign-cohort'), [
        'student_ids' => [$id], 'cohort_id' => bulkCohort()->cohort_id, 'original_cohorts' => [$id => null],
    ])->assertSessionHasErrors('student_ids');
});

test('bulk assignment rejects deleted students', function (): void {
    $student = User::factory()->create(['role' => 'student', 'cohort_id' => null]);
    $payload = bulkCohortPayload([$student], bulkCohort());
    $student->delete();
    $this->actingAs(bulkCohortAdmin())->post(route('admin.users.assign-cohort'), $payload)
        ->assertSessionHasErrors('student_ids');
    expect($student->fresh()->cohort_id)->toBeNull();
});

test('bulk assignment rejects non student targets', function (string $role): void {
    $user = User::factory()->create(['role' => $role, 'cohort_id' => null]);
    $this->actingAs(bulkCohortAdmin())->post(route('admin.users.assign-cohort'), bulkCohortPayload([$user], bulkCohort()))
        ->assertSessionHasErrors('student_ids');
    expect($user->fresh()->cohort_id)->toBeNull();
})->with(['faculty', 'admin', 'dean']);

test('duplicate selections are rejected', function (): void {
    $student = User::factory()->create(['role' => 'student']);
    $payload = bulkCohortPayload([$student, $student], bulkCohort());
    $this->actingAs(bulkCohortAdmin())->post(route('admin.users.assign-cohort'), $payload)
        ->assertSessionHasErrors('student_ids.0');
});

test('an empty selection is rejected', function (): void {
    $this->actingAs(bulkCohortAdmin())->post(route('admin.users.assign-cohort'), [
        'student_ids' => [], 'cohort_id' => bulkCohort()->cohort_id, 'original_cohorts' => [],
    ])->assertSessionHasErrors('student_ids');
});

test('more than one hundred selected students are rejected', function (): void {
    $ids = array_map(fn () => (string) Str::uuid(), range(1, 101));
    $this->actingAs(bulkCohortAdmin())->post(route('admin.users.assign-cohort'), [
        'student_ids' => $ids, 'cohort_id' => bulkCohort()->cohort_id,
        'original_cohorts' => array_fill_keys($ids, null),
    ])->assertSessionHasErrors('student_ids');
});

test('an unknown cohort is rejected', function (): void {
    $student = User::factory()->create(['role' => 'student']);
    $payload = bulkCohortPayload([$student], bulkCohort());
    $payload['cohort_id'] = (string) Str::uuid();
    $this->actingAs(bulkCohortAdmin())->post(route('admin.users.assign-cohort'), $payload)
        ->assertSessionHasErrors('cohort_id');
});

test('a missing cohort is rejected', function (): void {
    $student = User::factory()->create(['role' => 'student']);
    $payload = bulkCohortPayload([$student], bulkCohort());
    unset($payload['cohort_id']);
    $this->actingAs(bulkCohortAdmin())->post(route('admin.users.assign-cohort'), $payload)
        ->assertSessionHasErrors('cohort_id');
});

test('a cohort archived after selection is rejected', function (): void {
    $cohort = bulkCohort();
    $student = User::factory()->create(['role' => 'student', 'cohort_id' => null]);
    $payload = bulkCohortPayload([$student], $cohort);
    $cohort->forceFill(['archived_at' => now()])->save();
    $this->actingAs(bulkCohortAdmin())->post(route('admin.users.assign-cohort'), $payload)
        ->assertSessionHasErrors('cohort_id');
    expect($student->fresh()->cohort_id)->toBeNull();
});

test('a changed cohort assignment makes the browser selection stale', function (): void {
    $original = bulkCohort();
    $student = User::factory()->create(['role' => 'student', 'cohort_id' => $original->cohort_id]);
    $payload = bulkCohortPayload([$student], bulkCohort());
    $newCohort = bulkCohort();
    $student->update(['cohort_id' => $newCohort->cohort_id]);
    $this->actingAs(bulkCohortAdmin())->post(route('admin.users.assign-cohort'), $payload)
        ->assertSessionHasErrors('student_ids');
    expect($student->fresh()->cohort_id)->toBe($newCohort->cohort_id);
});

test('a missing original cohort snapshot is rejected', function (): void {
    $student = User::factory()->create(['role' => 'student']);
    $payload = bulkCohortPayload([$student], bulkCohort());
    $payload['original_cohorts'] = [(string) Str::uuid() => null];
    $this->actingAs(bulkCohortAdmin())->post(route('admin.users.assign-cohort'), $payload)
        ->assertSessionHasErrors('student_ids');
});

test('official assessment attempts lock cohort assignment', function (string $type, string $phase): void {
    $student = User::factory()->create(['role' => 'student', 'cohort_id' => null]);
    AssessmentSession::query()->create([
        'user_id' => $student->user_id, 'session_type' => $type, 'research_phase' => $phase, 'started_at' => now(),
    ]);
    $this->actingAs(bulkCohortAdmin())->post(route('admin.users.assign-cohort'), bulkCohortPayload([$student], bulkCohort()))
        ->assertSessionHasErrors('student_ids');
    expect($student->fresh()->cohort_id)->toBeNull();
})->with([['diagnostic', 'pre_test'], ['mock_board', 'post_test']]);

test('a mixed valid and locked batch rolls back every change and audit', function (): void {
    $students = User::factory()->count(2)->create(['role' => 'student', 'cohort_id' => null])->sortBy('user_id')->values();
    AssessmentSession::query()->create([
        'user_id' => $students[1]->user_id, 'session_type' => 'diagnostic', 'research_phase' => 'pre_test', 'started_at' => now(),
    ]);
    $this->actingAs(bulkCohortAdmin())->post(route('admin.users.assign-cohort'), bulkCohortPayload($students->all(), bulkCohort()))
        ->assertSessionHasErrors('student_ids');
    expect($students[0]->fresh()->cohort_id)->toBeNull()
        ->and($students[1]->fresh()->cohort_id)->toBeNull()
        ->and(AdminAuditLog::query()->where('action', 'user.bulk_cohort_assigned')->count())->toBe(0);
});

test('bulk assignment changes only the cohort and ignores privilege fields', function (): void {
    $student = User::factory()->create(['role' => 'student', 'cohort_id' => null]);
    $before = Arr::except($student->fresh()->getAttributes(), ['cohort_id', 'updated_at']);
    $cohort = bulkCohort();
    $payload = array_merge(bulkCohortPayload([$student], $cohort), [
        'role' => 'admin', 'first_name' => 'Forged', 'password' => 'not-an-authorized-password-change',
    ]);
    $this->actingAs(bulkCohortAdmin())->post(route('admin.users.assign-cohort'), $payload)
        ->assertSessionHasNoErrors();
    expect($student->fresh()->cohort_id)->toBe($cohort->cohort_id)
        ->and(Arr::except($student->fresh()->getAttributes(), ['cohort_id', 'updated_at']))->toEqual($before);
});

test('a fresh no op selection creates no duplicate audit records', function (): void {
    $student = User::factory()->create(['role' => 'student', 'cohort_id' => null]);
    $cohort = bulkCohort();
    $this->actingAs(bulkCohortAdmin());
    $payload = bulkCohortPayload([$student], $cohort);
    $this->post(route('admin.users.assign-cohort'), $payload)->assertSessionHasNoErrors();
    $this->post(route('admin.users.assign-cohort'), $payload)->assertSessionHasErrors('student_ids');
    session()->forget('errors');
    $this->post(route('admin.users.assign-cohort'), bulkCohortPayload([$student->fresh()], $cohort))
        ->assertSessionHasNoErrors()->assertSessionHas('status', '0 students moved to the selected cohort.');
    expect(AdminAuditLog::query()->where('action', 'user.bulk_cohort_assigned')->count())->toBe(1);
});

test('bulk audit records contain only cohort identifiers and no personal data', function (): void {
    $admin = bulkCohortAdmin();
    $student = User::factory()->create(['role' => 'student', 'cohort_id' => null]);
    $cohort = bulkCohort();
    $this->actingAs($admin)->post(route('admin.users.assign-cohort'), bulkCohortPayload([$student], $cohort))
        ->assertSessionHasNoErrors();
    $audit = AdminAuditLog::query()->where('action', 'user.bulk_cohort_assigned')->sole();
    expect($audit->admin_id)->toBe($admin->user_id)->and($audit->target_id)->toBe($student->user_id)
        ->and($audit->changes)->toEqual(['cohort_id' => ['old' => null, 'new' => $cohort->cohort_id]]);
    $json = json_encode($audit->changes, JSON_THROW_ON_ERROR);
    expect($json)->not->toContain($student->email)->not->toContain($student->first_name)->not->toContain('password');
});

test('the retired cohort csv routes are not exposed', function (): void {
    foreach (['create', 'preview', 'show', 'commit', 'destroy'] as $action) {
        expect(Route::has('admin.cohort-imports.'.$action))->toBeFalse();
    }
});
