<?php

use App\Models\AdminAuditLog;
use App\Models\Cohort;
use App\Models\User;

beforeEach(function (): void {
    $this->withoutVite();
});

function workspaceCohort(string $name = 'Workspace Pharmacy Cohort'): Cohort
{
    return Cohort::query()->create([
        'cohort_name' => $name,
        'academic_year' => '2026-2027',
        'target_phle_date' => '2027-04-01',
    ]);
}

it('requires authentication for cohort workspace pages', function (string $role): void {
    $cohort = workspaceCohort();
    $this->get(route($role.'.cohorts.index'))->assertRedirect(route('login'));
    $this->get(route($role.'.cohorts.show', $cohort))->assertRedirect(route('login'));
})->with(['dean', 'faculty']);

it('requires verified email for cohort workspace pages', function (string $role): void {
    $cohort = workspaceCohort();
    $user = User::factory()->create(['role' => $role, 'cohort_id' => $cohort->cohort_id, 'email_verified_at' => null]);
    $this->actingAs($user)->get(route($role.'.cohorts.index'))->assertRedirect(route('verification.notice'));
    $this->get(route($role.'.cohorts.show', $cohort))->assertRedirect(route('verification.notice'));
})->with(['dean', 'faculty']);

it('does not expose cohort workspace routes to other roles', function (string $actorRole, string $routeRole): void {
    $cohort = workspaceCohort();
    $actor = User::factory()->create(['role' => $actorRole, 'cohort_id' => $cohort->cohort_id]);
    $this->actingAs($actor)->get(route($routeRole.'.cohorts.index'))->assertForbidden();
    $this->get(route($routeRole.'.cohorts.show', $cohort))->assertForbidden();
})->with([
    ['student', 'dean'], ['student', 'faculty'], ['admin', 'dean'], ['admin', 'faculty'],
    ['faculty', 'dean'], ['dean', 'faculty'],
]);

it('lets the Dean browse all cohorts including archived cohorts', function (): void {
    $active = workspaceCohort('Active Pharmacy Batch');
    $archived = workspaceCohort('Archived Pharmacy Batch');
    $archived->forceFill(['archived_at' => now()])->save();
    $dean = User::factory()->create(['role' => 'dean']);

    $this->actingAs($dean)->get(route('dean.cohorts.index'))->assertOk()
        ->assertSeeText($active->cohort_name)->assertSeeText($archived->cohort_name)
        ->assertSee(route('dean.cohorts.show', $active), false)
        ->assertSee(route('dean.cohorts.index'), false);
    $this->get(route('dean.cohorts.show', $archived))->assertOk()->assertSeeText('Archived');
});

it('shows only the Faculty members assigned cohort and hides other rosters', function (): void {
    $own = workspaceCohort('Assigned Pharmacy Batch');
    $other = workspaceCohort('Unrelated Pharmacy Batch');
    $faculty = User::factory()->create(['role' => 'faculty', 'cohort_id' => $own->cohort_id]);
    $student = User::factory()->create(['role' => 'student', 'cohort_id' => $own->cohort_id, 'first_name' => 'Assigned Student', 'last_name' => 'Visible']);
    $hidden = User::factory()->create(['role' => 'student', 'cohort_id' => $other->cohort_id, 'first_name' => 'Other Student', 'last_name' => 'Hidden']);

    $this->actingAs($faculty)->get(route('faculty.cohorts.index'))->assertOk()
        ->assertSeeText($own->cohort_name)->assertDontSeeText($other->cohort_name)
        ->assertSee(route('faculty.cohorts.index'), false);
    $this->get(route('faculty.cohorts.show', ['cohort' => $own, 'cohort_id' => $other->cohort_id]))->assertOk()
        ->assertSeeText($student->fullName())->assertDontSeeText($hidden->fullName())
        ->assertDontSee(route('dean.students.show', $student), false);
    $this->get(route('faculty.cohorts.show', $other))->assertNotFound();
});

it('gives unassigned Faculty a useful empty state without granting cohort access', function (): void {
    $cohort = workspaceCohort();
    $faculty = User::factory()->create(['role' => 'faculty', 'cohort_id' => null]);
    $this->actingAs($faculty)->get(route('faculty.cohorts.index'))->assertOk()
        ->assertSeeText('No cohort assigned')->assertDontSeeText($cohort->cohort_name);
    $this->get(route('faculty.cohorts.show', $cohort))->assertNotFound();
});

it('rechecks Faculty cohort assignment on each request', function (): void {
    $old = workspaceCohort('Previous Cohort');
    $current = workspaceCohort('Current Cohort');
    $faculty = User::factory()->create(['role' => 'faculty', 'cohort_id' => $old->cohort_id]);
    $this->actingAs($faculty)->get(route('faculty.cohorts.show', $old))->assertOk();
    $faculty->update(['cohort_id' => $current->cohort_id]);
    $this->get(route('faculty.cohorts.show', $old))->assertNotFound();
    $this->get(route('faculty.cohorts.show', $current))->assertOk();
});

it('shows a cohort roster without deleted students or students from another cohort', function (): void {
    $cohort = workspaceCohort();
    $other = workspaceCohort('Another Cohort');
    $faculty = User::factory()->create(['role' => 'faculty', 'cohort_id' => $cohort->cohort_id]);
    $student = User::factory()->create(['role' => 'student', 'cohort_id' => $cohort->cohort_id, 'is_diagnostic_completed' => false]);
    $completed = User::factory()->create(['role' => 'student', 'cohort_id' => $cohort->cohort_id, 'is_diagnostic_completed' => true]);
    $deleted = User::factory()->create(['role' => 'student', 'cohort_id' => $cohort->cohort_id]);
    $deleted->delete();
    $outsider = User::factory()->create(['role' => 'student', 'cohort_id' => $other->cohort_id]);

    $this->actingAs(User::factory()->create(['role' => 'dean']))
        ->get(route('dean.cohorts.show', $cohort))->assertOk()
        ->assertSeeText($student->fullName())->assertSeeText($completed->fullName())->assertSeeText($faculty->fullName())
        ->assertSeeText('Diagnostic not completed')->assertSeeText('1 of 2')
        ->assertDontSeeText($deleted->fullName())->assertDontSeeText($outsider->fullName())
        ->assertSee(route('dean.students.show', $student), false);
});

it('keeps cohort mutation and assessment release controls administrator only', function (string $role): void {
    $cohort = workspaceCohort();
    $actor = User::factory()->create(['role' => $role, 'cohort_id' => $cohort->cohort_id]);
    $this->actingAs($actor)->get(route($role.'.cohorts.show', $cohort))->assertOk()
        ->assertDontSee(route('admin.cohorts.edit', $cohort), false);
    $this->get(route('admin.cohorts.edit', $cohort))->assertForbidden();
    $this->post(route('admin.cohorts.store'), ['cohort_name' => 'Unauthorized'])->assertForbidden();
    $this->put(route('admin.cohorts.update', $cohort), ['cohort_name' => 'Unauthorized'])->assertForbidden();
    $this->patch(route('admin.cohorts.archive', $cohort), ['reason' => 'Unauthorized change.'])->assertForbidden();
    $this->patch(route('admin.cohorts.post-test-window', $cohort))->assertForbidden();
    expect($cohort->fresh()->cohort_name)->toBe('Workspace Pharmacy Cohort')
        ->and($cohort->fresh()->archived_at)->toBeNull()
        ->and(AdminAuditLog::query()->count())->toBe(0);
})->with(['dean', 'faculty']);
