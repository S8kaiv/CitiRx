<?php

use App\Models\AdminAuditLog;
use App\Models\StudentNameRequest;
use App\Models\User;
use App\Services\StudentNameService;

beforeEach(function (): void {
    $this->withoutVite();
});

function nameRequestStudent(): User
{
    return User::factory()->create([
        'role' => 'student', 'first_name' => 'Alicia', 'middle_name' => 'Maria', 'last_name' => 'Santos',
        'is_diagnostic_completed' => false,
    ]);
}

function pendingNameRequest(User $student): StudentNameRequest
{
    app(StudentNameService::class)->request($student, [
        'first_name' => 'Alicia Updated', 'middle_name' => 'M.', 'last_name' => 'Reyes',
    ]);

    return StudentNameRequest::query()->where('pending_user_id', $student->user_id)->sole();
}

it('keeps name decisions and the Dean workspace unavailable to other roles', function (string $role): void {
    $student = nameRequestStudent();
    $request = pendingNameRequest($student);
    $actor = User::factory()->create(['role' => $role]);
    $this->actingAs($actor)->get(route('dean.dashboard'))->assertForbidden();
    $this->get(route('dean.names.index'))->assertForbidden();
    $this->get(route('dean.students.show', $student))->assertForbidden();
    $this->patch(route('dean.names.decide', $request), ['decision' => 'approve'])->assertForbidden();
    expect($request->fresh()->status)->toBe('pending')->and($student->fresh()->first_name)->toBe('Alicia');
})->with(['student', 'faculty', 'admin']);

it('shows pending requests and an honest missing diagnostic state in the Dean workspace', function (): void {$student = nameRequestStudent();
    pendingNameRequest($student);

    $this->actingAs(User::factory()->create(['role' => 'dean']))->get(route('dean.dashboard'))->assertOk()->assertViewHas('pendingNames', 1)
        ->assertSeeText('Name requests')->assertSeeText('Diagnostic pending');

    $this->get(route('dean.students.show', $student))->assertOk()->assertSeeText('Diagnostic not completed');
});

it('approves the requested fields even when stored JSON keys have a different order', function (): void {
    $student = nameRequestStudent();
    $request = pendingNameRequest($student);
    $request->update([
        'original_names' => ['last_name' => 'Santos', 'middle_name' => 'Maria', 'first_name' => 'Alicia'],
        'requested_names' => ['last_name' => 'Reyes', 'first_name' => 'Alicia Updated', 'middle_name' => 'M.'],
    ]);
    $dean = User::factory()->create(['role' => 'dean']);
    $this->actingAs($dean)->get(route('dean.names.index'))->assertOk()->assertSeeText('Alicia Updated M. Reyes');
    $this->patch(route('dean.names.decide', $request), ['decision' => 'approve', 'decision_note' => 'Official spelling confirmed.'])
        ->assertRedirect()->assertSessionHasNoErrors();
    $student->refresh();
    $request->refresh();
    expect($student->fullName())->toBe('Alicia Updated M. Reyes')
        ->and($request->status)->toBe('approved')->and($request->pending_user_id)->toBeNull()
        ->and($request->decided_by)->toBe($dean->user_id)->and($request->decided_at)->not->toBeNull();
    $audit = AdminAuditLog::query()->where('action', 'student_name.approved')->sole();
    expect($audit->admin_id)->toBe($dean->user_id)->and($audit->target_id)->toBe($student->user_id)
        ->and($audit->reason)->toBe('Official spelling confirmed.');
    $this->actingAs($student)->get(route('profile.edit'))->assertOk()
        ->assertSeeText('Approved')->assertSeeText('Official spelling confirmed.');
});

it('declines a request without changing the official name and shows the decision to the student', function (): void {
    $student = nameRequestStudent();
    $request = pendingNameRequest($student);
    $dean = User::factory()->create(['role' => 'dean']);
    $note = 'Please provide the spelling on your official student record.';
    $this->actingAs($dean)->patch(route('dean.names.decide', $request), ['decision' => 'decline', 'decision_note' => $note])
        ->assertSessionHasNoErrors();
    expect($student->fresh()->fullName())->toBe('Alicia Maria Santos')
        ->and($request->fresh()->status)->toBe('declined')->and($request->fresh()->decided_by)->toBe($dean->user_id)
        ->and($request->fresh()->decision_note)->toBe($note);
    expect(AdminAuditLog::query()->where('action', 'student_name.declined')->count())->toBe(1);
    $this->actingAs($student)->get(route('profile.edit'))->assertOk()->assertSeeText('Declined')->assertSeeText($note);
});

it('requires a note when declining a request', function (): void {
    $student = nameRequestStudent();
    $request = pendingNameRequest($student);
    $this->actingAs(User::factory()->create(['role' => 'dean']))
        ->patch(route('dean.names.decide', $request), ['decision' => 'decline', 'decision_note' => ''])
        ->assertSessionHasErrors('decision_note');
    expect($request->fresh()->status)->toBe('pending')->and($student->fresh()->last_name)->toBe('Santos')
        ->and(AdminAuditLog::query()->where('action', 'student_name.declined')->count())->toBe(0);
});

it('does not approve a request after the official name has actually changed', function (): void {
    $student = nameRequestStudent();
    $request = pendingNameRequest($student);
    $student->update(['first_name' => 'Corrected Official Name']);
    $this->actingAs(User::factory()->create(['role' => 'dean']))
        ->patch(route('dean.names.decide', $request), ['decision' => 'approve'])->assertSessionHasErrors('decision');
    expect($request->fresh()->status)->toBe('pending')
        ->and($student->fresh()->first_name)->toBe('Corrected Official Name')
        ->and(AdminAuditLog::query()->where('action', 'student_name.approved')->count())->toBe(0);
});

it('does not duplicate an identical pending request because of JSON key ordering', function (): void {
    $student = nameRequestStudent();
    $request = pendingNameRequest($student);
    $request->update(['requested_names' => ['last_name' => 'Reyes', 'middle_name' => 'M.', 'first_name' => 'Alicia Updated']]);
    app(StudentNameService::class)->request($student, [
        'first_name' => 'Alicia Updated', 'middle_name' => 'M.', 'last_name' => 'Reyes',
    ]);
    expect(StudentNameRequest::query()->where('user_id', $student->user_id)->count())->toBe(1)
        ->and($request->fresh()->status)->toBe('pending')->and($request->fresh()->requestedFullName())->toBe('Alicia Updated M. Reyes');
});

it('supersedes the prior pending request when a different spelling is submitted', function (): void {
    $student = nameRequestStudent();
    $request = pendingNameRequest($student);
    app(StudentNameService::class)->request($student, ['first_name' => 'Another Request', 'middle_name' => null, 'last_name' => 'Reyes']);
    expect($request->fresh()->status)->toBe('superseded')->and($request->fresh()->pending_user_id)->toBeNull()
        ->and(StudentNameRequest::query()->where('pending_user_id', $student->user_id)->count())->toBe(1)
        ->and($student->fresh()->first_name)->toBe('Alicia');
});

it('refuses a second decision without duplicating its audit', function (): void {
    $student = nameRequestStudent();
    $request = pendingNameRequest($student);
    $this->actingAs(User::factory()->create(['role' => 'dean']))
        ->patch(route('dean.names.decide', $request), ['decision' => 'approve'])->assertSessionHasNoErrors();
    $this->patch(route('dean.names.decide', $request), ['decision' => 'decline', 'decision_note' => 'Attempt to overwrite the decision.'])
        ->assertSessionHasErrors('decision');
    expect($request->fresh()->status)->toBe('approved')->and($student->fresh()->fullName())->toBe('Alicia Updated M. Reyes')
        ->and(AdminAuditLog::query()->whereIn('action', ['student_name.approved', 'student_name.declined'])->count())->toBe(1);
});

it('lets the Dean edit an official name and records why the pending request ended', function (): void {
    $student = nameRequestStudent();
    $request = pendingNameRequest($student);
    $dean = User::factory()->create(['role' => 'dean']);
    $this->actingAs($dean)->patch(route('dean.students.name', $student), [
        'first_name' => 'Official', 'middle_name' => null, 'last_name' => 'Correction',
    ])->assertSessionHasNoErrors();
    expect($student->fresh()->fullName())->toBe('Official Correction')
        ->and($request->fresh()->status)->toBe('superseded')->and($request->fresh()->decided_by)->toBe($dean->user_id)
        ->and(AdminAuditLog::query()->where('action', 'student_name.edited')->count())->toBe(1);
});
