<?php

use App\Models\AdminAuditLog;
use App\Models\AssessmentSession;
use App\Models\Cohort;
use App\Models\User;
use App\Services\AdminUserService;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->withoutVite();
});

function deanManagedCohort(string $name = 'Dean Managed Cohort'): Cohort
{
    return Cohort::query()->create(['cohort_name' => $name, 'academic_year' => '2026-2027']);
}

function deanAssignmentPayload(array $students, Cohort $cohort): array
{
    return [
        'student_ids' => array_map(fn (User $student) => $student->user_id, $students),
        'cohort_id' => $cohort->cohort_id,
        'original_cohorts' => collect($students)->mapWithKeys(fn (User $student) => [$student->user_id => $student->cohort_id])->all(),
    ];
}

it('requires authentication and verification for Dean cohort management', function (): void {
    foreach (['dean.cohorts.create', 'dean.cohorts.assignments'] as $route) {
        $this->get(route($route))->assertRedirect(route('login'));
    }
    $this->post(route('dean.cohorts.store'))->assertRedirect(route('login'));
    $this->post(route('dean.cohorts.assign'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create(['role' => 'dean', 'email_verified_at' => null]));
    $this->get(route('dean.cohorts.create'))->assertRedirect(route('verification.notice'));
    $this->get(route('dean.cohorts.assignments'))->assertRedirect(route('verification.notice'));
    $this->post(route('dean.cohorts.store'))->assertRedirect(route('verification.notice'));
    $this->post(route('dean.cohorts.assign'))->assertRedirect(route('verification.notice'));
});

it('keeps Dean creation and bulk assignment unavailable to every other role', function (string $role): void {
    $this->actingAs(User::factory()->create(['role' => $role]));
    $this->get(route('dean.cohorts.create'))->assertForbidden();
    $this->get(route('dean.cohorts.assignments'))->assertForbidden();
    $this->post(route('dean.cohorts.store'))->assertForbidden();
    $this->post(route('dean.cohorts.assign'))->assertForbidden();
})->with(['student', 'faculty', 'admin']);

it('lets the Dean create a cohort without changing Faculty assignments or release windows', function (): void {
    $existing = deanManagedCohort('Existing Faculty Cohort');
    $faculty = User::factory()->create(['role' => 'faculty', 'cohort_id' => $existing->cohort_id]);
    $dean = User::factory()->create(['role' => 'dean']);
    $this->actingAs($dean)->get(route('dean.cohorts.index'))->assertOk()
        ->assertSee(route('dean.cohorts.create'), false)->assertSee(route('dean.cohorts.assignments'), false);
    $this->get(route('dean.cohorts.create'))->assertOk()->assertSeeText('Create cohort');
    $this->post(route('dean.cohorts.store'), [
        'cohort_name' => '  New Pharmacy Cohort  ', 'academic_year' => '2026-2027', 'target_phle_date' => '2027-04-01',
        'post_test_opens_at' => '2027-01-01', 'post_test_closes_at' => '2027-12-31', 'faculty_id' => $faculty->user_id,
    ])->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('status', 'Cohort created.');

    $cohort = Cohort::query()->where('cohort_name', 'New Pharmacy Cohort')->sole();
    expect($cohort->post_test_opens_at)->toBeNull()->and($cohort->post_test_closes_at)->toBeNull()
        ->and($faculty->fresh()->cohort_id)->toBe($existing->cohort_id);
    $audit = AdminAuditLog::query()->where('action', 'cohort.created')->sole();
    expect($audit->admin_id)->toBe($dean->user_id)->and($audit->target_id)->toBe($cohort->cohort_id);
});

it('uses the existing cohort name and academic year validation for the Dean', function (): void {
    $existing = deanManagedCohort();
    $this->actingAs(User::factory()->create(['role' => 'dean']));
    $this->from(route('dean.cohorts.create'))->post(route('dean.cohorts.store'), [
        'cohort_name' => '', 'academic_year' => 'not-a-year', 'target_phle_date' => 'not-a-date',
    ])->assertRedirect(route('dean.cohorts.create'))->assertSessionHasErrors(['cohort_name', 'academic_year', 'target_phle_date']);
    $this->post(route('dean.cohorts.store'), [
        'cohort_name' => $existing->cohort_name, 'academic_year' => $existing->academic_year,
    ])->assertSessionHasErrors('cohort_name');
    expect(Cohort::query()->count())->toBe(1)->and(AdminAuditLog::query()->count())->toBe(0);
});

it('shows only student accounts with selection controls and hides archived assignment targets', function (): void {
    $active = deanManagedCohort();
    $archived = deanManagedCohort('Archived Assignment Target');
    $archived->forceFill(['archived_at' => now()])->save();
    $student = User::factory()->create(['role' => 'student', 'first_name' => 'Roster Student', 'last_name' => 'Listed']);
    $deleted = User::factory()->create(['role' => 'student', 'first_name' => 'Deleted Student', 'last_name' => 'Hidden']);
    $deleted->delete();
    $faculty = User::factory()->create(['role' => 'faculty', 'first_name' => 'Faculty Person', 'last_name' => 'Hidden']);
    $this->actingAs(User::factory()->create(['role' => 'dean']))
        ->get(route('dean.cohorts.assignments', ['cohort_id' => $active->cohort_id]))->assertOk()
        ->assertSeeText($student->fullName())->assertDontSeeText($deleted->fullName())->assertDontSeeText($faculty->fullName())
        ->assertSeeText('Select all eligible on this page')->assertSeeText('0 selected')
        ->assertDontSee('value="'.$archived->cohort_id.'"', false)
        ->assertSee('data-confirm=', false);
});

it('lets the Dean assign only selected students and records every move without granting account administration', function (): void {
    $source = deanManagedCohort('Source Cohort');
    $target = deanManagedCohort('Target Cohort');
    $first = User::factory()->create(['role' => 'student', 'cohort_id' => null]);
    $second = User::factory()->create(['role' => 'student', 'cohort_id' => $source->cohort_id]);
    $untouched = User::factory()->create(['role' => 'student', 'cohort_id' => $source->cohort_id]);
    $dean = User::factory()->create(['role' => 'dean']);
    $this->actingAs($dean)->from(route('dean.cohorts.assignments'))->post(route('dean.cohorts.assign'), [
        ...deanAssignmentPayload([$first, $second], $target), 'role' => 'admin', 'first_name' => 'Forged',
    ])->assertRedirect(route('dean.cohorts.assignments'))->assertSessionHasNoErrors()
        ->assertSessionHas('status', '2 students moved to the selected cohort.');
    expect($first->fresh()->cohort_id)->toBe($target->cohort_id)->and($second->fresh()->cohort_id)->toBe($target->cohort_id)
        ->and($untouched->fresh()->cohort_id)->toBe($source->cohort_id)->and($first->fresh()->role)->toBe('student')
        ->and($first->fresh()->first_name)->toBe($first->first_name);
    $audits = AdminAuditLog::query()->where('action', 'user.bulk_cohort_assigned')->get();
    expect($audits)->toHaveCount(2)->and($audits->every(fn ($audit) => $audit->admin_id === $dean->user_id))->toBeTrue();
    $this->get(route('admin.users.index'))->assertForbidden();
    $this->patch(route('admin.cohorts.post-test-window', $target))->assertForbidden();
});

it('does not allow archived cohort assignment targets', function (): void {
    $cohort = deanManagedCohort();
    $cohort->forceFill(['archived_at' => now()])->save();
    $student = User::factory()->create(['role' => 'student', 'cohort_id' => null]);
    $this->actingAs(User::factory()->create(['role' => 'dean']))
        ->get(route('dean.cohorts.assignments', ['cohort_id' => $cohort->cohort_id]))->assertOk()
        ->assertSeeText('No active cohorts are available.');
    $this
        ->post(route('dean.cohorts.assign'), deanAssignmentPayload([$student], $cohort))->assertSessionHasErrors('cohort_id');
    expect($student->fresh()->cohort_id)->toBeNull()->and(AdminAuditLog::query()->count())->toBe(0);
});

it('rejects stale or missing original assignments without changing any student', function (bool $missing): void {
    $cohort = deanManagedCohort();
    $student = User::factory()->create(['role' => 'student', 'cohort_id' => null]);
    $payload = deanAssignmentPayload([$student], $cohort);
    $payload['original_cohorts'] = $missing
        ? [fake()->uuid() => null]
        : [$student->user_id => $cohort->cohort_id];
    $this->actingAs(User::factory()->create(['role' => 'dean']))
        ->post(route('dean.cohorts.assign'), $payload)->assertSessionHasErrors('student_ids');
    expect($student->fresh()->cohort_id)->toBeNull()->and(AdminAuditLog::query()->count())->toBe(0);
})->with([true, false]);

it('rolls back a Dean batch if any selected student has official research history', function (string $type, string $phase): void {
    $cohort = deanManagedCohort();
    $students = User::factory()->count(2)->create(['role' => 'student', 'cohort_id' => null])->sortBy('user_id')->values();
    AssessmentSession::query()->create([
        'user_id' => $students[1]->user_id, 'session_type' => $type, 'research_phase' => $phase, 'started_at' => now(),
    ]);
    $this->actingAs(User::factory()->create(['role' => 'dean']))
        ->post(route('dean.cohorts.assign'), deanAssignmentPayload($students->all(), $cohort))->assertSessionHasErrors('student_ids');
    expect($students[0]->fresh()->cohort_id)->toBeNull()->and($students[1]->fresh()->cohort_id)->toBeNull()
        ->and(AdminAuditLog::query()->count())->toBe(0);
})->with([['diagnostic', 'pre_test'], ['mock_board', 'post_test']]);

it('rejects nonstudent accounts instead of changing their Faculty assignment', function (): void {
    $cohort = deanManagedCohort();
    $faculty = User::factory()->create(['role' => 'faculty', 'cohort_id' => null]);
    $this->actingAs(User::factory()->create(['role' => 'dean']))
        ->post(route('dean.cohorts.assign'), deanAssignmentPayload([$faculty], $cohort))->assertSessionHasErrors('student_ids');
    expect($faculty->fresh()->cohort_id)->toBeNull()->and(AdminAuditLog::query()->count())->toBe(0);
});

it('keeps Faculty pages read only and blocks direct service bulk assignment', function (): void {
    $cohort = deanManagedCohort();
    $faculty = User::factory()->create(['role' => 'faculty', 'cohort_id' => $cohort->cohort_id]);
    $student = User::factory()->create(['role' => 'student', 'cohort_id' => null]);
    $this->actingAs($faculty)->get(route('faculty.cohorts.index'))->assertOk()
        ->assertDontSee(route('dean.cohorts.create'), false)->assertDontSee(route('dean.cohorts.assignments'), false);
    $this->get(route('faculty.cohorts.show', $cohort))->assertOk()
        ->assertDontSee(route('dean.cohorts.assignments'), false);
    expect(fn () => app(AdminUserService::class)->assignCohort($faculty, [$student->user_id], $cohort->cohort_id, [$student->user_id => null]))
        ->toThrow(HttpException::class);
    expect($student->fresh()->cohort_id)->toBeNull();
});

it('requires a selection and refuses duplicate student identifiers', function (): void {
    $cohort = deanManagedCohort();
    $student = User::factory()->create(['role' => 'student', 'cohort_id' => null]);
    $this->actingAs(User::factory()->create(['role' => 'dean']));
    $this->post(route('dean.cohorts.assign'), ['cohort_id' => $cohort->cohort_id])->assertSessionHasErrors('student_ids');
    $payload = deanAssignmentPayload([$student], $cohort);
    $payload['student_ids'][] = $student->user_id;
    $this->post(route('dean.cohorts.assign'), $payload)->assertSessionHasErrors('student_ids.0');
    expect($student->fresh()->cohort_id)->toBeNull()->and(AdminAuditLog::query()->count())->toBe(0);
});
