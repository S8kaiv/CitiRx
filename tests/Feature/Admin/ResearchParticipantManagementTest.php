<?php

use App\Models\AdminAuditLog;
use App\Models\Cohort;
use App\Models\ResearchParticipant;
use App\Models\User;
use App\Services\ResearchParticipantService;

function participantAdmin(): User
{
    return User::factory()->create([
        'role' => 'admin',
        'password' => 'password',
    ]);
}

function participantCohort(): Cohort
{
    return Cohort::query()->create([
        'cohort_name' => fake()->unique()->words(3, true),
        'academic_year' => '2026-2027',
        'target_phle_date' => now()->addMonths(3)->toDateString(),
    ]);
}

function participantStudent(array $attributes = []): User
{
    $cohort = participantCohort();

    return User::factory()->create(array_merge([
        'role' => 'student',
        'cohort_id' => $cohort->cohort_id,
        'email_verified_at' => now(),
    ], $attributes));
}

beforeEach(function (): void {
    $this->withoutVite();
});

test('guests are redirected from participant scope', function (): void {
    $this->get(route('admin.research-participants.index'))
        ->assertRedirect(route('login'));
});

test('students and faculty cannot access participant scope', function (
    string $role,
): void {
    $user = User::factory()->create([
        'role' => $role,
    ]);

    $this->actingAs($user)
        ->get(route('admin.research-participants.index'))
        ->assertForbidden();
})->with([
    'student',
    'faculty',
]);

test('an administrator can view participant scope', function (): void {
    $administrator = participantAdmin();
    $student = participantStudent();

    $this->actingAs($administrator)
        ->get(route('admin.research-participants.index'))
        ->assertOk()
        ->assertSeeText('Research Participants')
        ->assertSeeText($student->fullName())
        ->assertSeeText('Unregistered');
});

test('an administrator can explicitly include a student', function (): void {
    $administrator = participantAdmin();
    $student = participantStudent();

    $this->actingAs($administrator)
        ->patch(
            route(
                'admin.research-participants.update',
                ['userId' => $student->user_id],
            ),
            [
                'status' => ResearchParticipant::STATUS_INCLUDED,
                'expected_lock_version' => 0,
                'reason' => 'Approved protocol documentation was verified.',
                'current_password' => 'password',
            ],
        )
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status');

    $participant = ResearchParticipant::query()
        ->where('user_id', $student->user_id)
        ->sole();

    expect($participant->status)
        ->toBe(ResearchParticipant::STATUS_INCLUDED)
        ->and($participant->changed_by)
        ->toBe($administrator->user_id)
        ->and($participant->lock_version)
        ->toBe(1);

    $this->assertDatabaseHas('admin_audit_logs', [
        'admin_id' => $administrator->user_id,
        'action' => 'research_participant.status_changed',
        'target_id' => $participant->research_participant_id,
    ]);
});

test('the current administrator password is required', function (): void {
    $administrator = participantAdmin();
    $student = participantStudent();

    $this->actingAs($administrator)
        ->patch(
            route(
                'admin.research-participants.update',
                ['userId' => $student->user_id],
            ),
            [
                'status' => ResearchParticipant::STATUS_ELIGIBLE,
                'expected_lock_version' => 0,
                'reason' => 'Eligibility documentation was reviewed.',
                'current_password' => 'wrong-password',
            ],
        )
        ->assertSessionHasErrors('current_password');

    expect(
        ResearchParticipant::query()
            ->where('user_id', $student->user_id)
            ->exists(),
    )->toBeFalse();
});

test('a stale participant lock version is rejected', function (): void {
    $administrator = participantAdmin();
    $student = participantStudent();

    $participant = app(ResearchParticipantService::class)
        ->changeStatus(
            administrator: $administrator,
            userId: $student->user_id,
            status: ResearchParticipant::STATUS_ELIGIBLE,
            expectedLockVersion: 0,
            reason: 'Initial eligibility documentation was reviewed.',
        );

    $this->actingAs($administrator)
        ->patch(
            route(
                'admin.research-participants.update',
                ['userId' => $student->user_id],
            ),
            [
                'status' => ResearchParticipant::STATUS_INCLUDED,
                'expected_lock_version' => $participant->lock_version - 1,
                'reason' => 'Attempting an update from a stale browser.',
                'current_password' => 'password',
            ],
        )
        ->assertSessionHas('error');

    expect($participant->fresh()->status)
        ->toBe(ResearchParticipant::STATUS_ELIGIBLE);
});

test('only student accounts can receive participant status', function (): void {
    $administrator = participantAdmin();
    $faculty = User::factory()->create([
        'role' => 'faculty',
    ]);

    expect(
        fn () => app(ResearchParticipantService::class)
            ->changeStatus(
                administrator: $administrator,
                userId: $faculty->user_id,
                status: ResearchParticipant::STATUS_EXCLUDED,
                expectedLockVersion: 0,
                reason: 'This account is not a student participant.',
            ),
    )->toThrow(
        LogicException::class,
        'Only student accounts',
    );
});

test('direct service use by a non administrator is rejected', function (): void {
    $student = participantStudent();

    expect(
        fn () => app(ResearchParticipantService::class)
            ->changeStatus(
                administrator: $student,
                userId: $student->user_id,
                status: ResearchParticipant::STATUS_INCLUDED,
                expectedLockVersion: 0,
                reason: 'Unauthorized direct service invocation attempt.',
            ),
    )->toThrow(
        LogicException::class,
        'Only administrators',
    );
});

test('included status requires a cohort and verified email', function (
    array $studentAttributes,
    string $expectedMessage,
): void {
    $administrator = participantAdmin();
    $student = participantStudent($studentAttributes);

    expect(
        fn () => app(ResearchParticipantService::class)
            ->changeStatus(
                administrator: $administrator,
                userId: $student->user_id,
                status: ResearchParticipant::STATUS_INCLUDED,
                expectedLockVersion: 0,
                reason: 'Attempting inclusion without required account readiness.',
            ),
    )->toThrow(
        LogicException::class,
        $expectedMessage,
    );
})->with([
    'missing cohort' => [
        ['cohort_id' => null],
        'Assign the student to a cohort',
    ],
    'unverified email' => [
        ['email_verified_at' => null],
        'Verify the student email',
    ],
]);

test('soft deletion does not automatically withdraw a participant', function (): void {
    $administrator = participantAdmin();
    $student = participantStudent();

    $participant = app(ResearchParticipantService::class)
        ->changeStatus(
            administrator: $administrator,
            userId: $student->user_id,
            status: ResearchParticipant::STATUS_INCLUDED,
            expectedLockVersion: 0,
            reason: 'Approved protocol documentation was verified.',
        );

    $student->delete();

    expect($participant->fresh()->status)
        ->toBe(ResearchParticipant::STATUS_INCLUDED)
        ->and($participant->fresh()->user->trashed())
        ->toBeTrue();
});

test('withdrawal preserves assessment history and the participant record', function (): void {
    $administrator = participantAdmin();
    $student = participantStudent();

    $participant = app(ResearchParticipantService::class)
        ->changeStatus(
            administrator: $administrator,
            userId: $student->user_id,
            status: ResearchParticipant::STATUS_INCLUDED,
            expectedLockVersion: 0,
            reason: 'Approved protocol documentation was verified.',
        );

    $updated = app(ResearchParticipantService::class)
        ->changeStatus(
            administrator: $administrator,
            userId: $student->user_id,
            status: ResearchParticipant::STATUS_WITHDRAWN,
            expectedLockVersion: $participant->lock_version,
            reason: 'Withdrawal was recorded under the approved protocol.',
        );

    expect($updated->research_participant_id)
        ->toBe($participant->research_participant_id)
        ->and($updated->status)
        ->toBe(ResearchParticipant::STATUS_WITHDRAWN)
        ->and($updated->lock_version)
        ->toBe(2);
});

test('audit data never contains the administrator password', function (): void {
    $administrator = participantAdmin();
    $student = participantStudent();

    $this->actingAs($administrator)
        ->patch(
            route(
                'admin.research-participants.update',
                ['userId' => $student->user_id],
            ),
            [
                'status' => ResearchParticipant::STATUS_ELIGIBLE,
                'expected_lock_version' => 0,
                'reason' => 'Eligibility documentation was reviewed.',
                'current_password' => 'password',
            ],
        )
        ->assertSessionHasNoErrors();

    $audit = AdminAuditLog::query()
        ->where(
            'action',
            'research_participant.status_changed',
        )
        ->latest('created_at')
        ->firstOrFail();

    $encoded = json_encode([
        'changes' => $audit->changes,
        'reason' => $audit->reason,
    ], JSON_THROW_ON_ERROR);

    expect($encoded)
        ->not->toContain('current_password')
        ->not->toContain('password');
});
