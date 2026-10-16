<?php

use App\Models\AdminAuditLog;
use App\Models\AssessmentSession;
use App\Models\Cohort;
use App\Models\User;

beforeEach(function (): void {
    $this->withoutVite();
});

it('shows the administrator overview using active account counts', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    $cohort = Cohort::query()->create([
        'cohort_name' => 'Dashboard Test Cohort',
        'academic_year' => '2026-2027',
    ]);

    $studentWithCohort = User::factory()->create([
        'role' => 'student',
        'cohort_id' => $cohort->cohort_id,
    ]);

    User::factory()->create([
        'role' => 'student',
        'cohort_id' => null,
    ]);

    User::factory()->create([
        'role' => 'faculty',
        'cohort_id' => null,
    ]);

    AssessmentSession::query()->create([
        'user_id' => $studentWithCohort->user_id,
        'session_type' => 'diagnostic',
        'research_phase' => 'pre_test',
        'started_at' => now()->subHour(),
        'completed_at' => now(),
    ]);

    AssessmentSession::query()->create([
        'user_id' => $studentWithCohort->user_id,
        'session_type' => 'mock_board',
        'research_phase' => 'post_test',
        'started_at' => now()->subMinutes(30),
        'completed_at' => now(),
    ]);

    AdminAuditLog::query()->create([
        'admin_id' => $admin->user_id,
        'action' => 'cohort_created',
        'target_type' => Cohort::class,
        'target_id' => $cohort->cohort_id,
        'changes' => null,
        'reason' => 'Dashboard test activity.',
    ]);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Admin Dashboard')
        ->assertSeeText('Cohort Created')
        ->assertViewHas(
            'accountStats',
            fn (array $stats): bool => $stats['students'] === 2
                && $stats['faculty'] === 1
                && $stats['students_without_cohort'] === 1
                && $stats['faculty_without_cohort'] === 1,
        )
        ->assertViewHas(
            'assessmentStats',
            fn (array $stats): bool => $stats['diagnostic_completed'] === 1
                && $stats['mock_board_completed'] === 1,
        );
});
