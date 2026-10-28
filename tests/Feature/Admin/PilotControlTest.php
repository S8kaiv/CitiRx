<?php

use App\Models\AdminAuditLog;
use App\Models\AssessmentSession;
use App\Models\Cohort;
use App\Models\PilotControl;
use App\Models\Question;
use App\Models\ResearchFormVersion;
use App\Models\User;
use App\Services\AdminCohortService;
use App\Services\DiagnosticService;
use App\Services\MockBoardService;
use App\Services\PilotControlService;
use App\Services\ResearchFormVersionManagementService;
use Database\Seeders\ResearchQuestionSeeder;
use Database\Seeders\TosSeeder;

function pilotAdministrator(): User
{
    return User::factory()->create([
        'role' => 'admin',
        'password' => 'password',
    ]);
}

function pilotMakeResearchFormsReady(User $validator): void
{
    /*
     * Test-fixture setup only. The production model event must
     * continue protecting served or validated question content.
     */
    Question::withoutEvents(function () use ($validator): void {
        Question::query()
            ->whereIn('research_form', [
                Question::FORM_PRE_TEST_A,
                Question::FORM_POST_TEST_B,
            ])
            ->each(function (Question $question) use ($validator): void {
                $question->forceFill([
                    'question_text' => str_replace(
                        '[DEVELOPMENT MOCK - NOT VALIDATED] ',
                        '',
                        $question->question_text,
                    ),
                    'research_validated_at' => now(),
                    'research_validated_by' => $validator->user_id,
                ])->save();
            });
    });

    ResearchFormVersion::query()
        ->whereIn('research_form', [
            Question::FORM_PRE_TEST_A,
            Question::FORM_POST_TEST_B,
        ])
        ->where('is_active', true)
        ->update([
            'status' => ResearchFormVersion::STATUS_VALIDATED,
        ]);
}

function pilotCohort(
    bool $expiredWindow = false,
    bool $archived = false,
): Cohort {
    $cohort = Cohort::query()->create([
        'cohort_name' => fake()->unique()->words(3, true),
        'academic_year' => '2026-2027',
        'target_phle_date' => now()->addMonths(3)->toDateString(),
        'post_test_opens_at' => $expiredWindow
            ? now()->subDays(2)
            : now()->subHour(),
        'post_test_closes_at' => $expiredWindow
            ? now()->subDay()
            : now()->addDay(),
    ]);

    if ($archived) {
        $cohort->forceFill([
            'archived_at' => now(),
        ])->save();
    }

    return $cohort->fresh();
}

/**
 * @param  array<string, mixed>  $extra
 * @return array<string, mixed>
 */
function pilotTransitionData(
    PilotControl $control,
    string $phase,
    array $extra = [],
): array {
    return array_merge([
        'phase' => $phase,
        'lock_version' => $control->lock_version,
        'reason' => 'Authorized pilot phase transition for testing.',
        'current_password' => 'password',
    ], $extra);
}

function pilotSetState(
    string $phase,
    ?User $starter = null,
): PilotControl {
    $control = PilotControl::query()->sole();

    $control->forceFill([
        'phase' => $phase,
        'official_collection_started_at' => $starter
            ? now()->subMinute()
            : null,
        'official_collection_started_by' => $starter?->user_id,
        'lock_version' => $control->lock_version + 1,
    ])->save();

    return $control->fresh();
}

function pilotIncompleteSession(
    User $user,
    string $sessionType,
    string $researchPhase,
): AssessmentSession {
    return AssessmentSession::query()->create([
        'user_id' => $user->user_id,
        'session_type' => $sessionType,
        'research_phase' => $researchPhase,
        'served_question_ids' => [],
        'draft_answers' => [],
        'target_length' => 60,
        'total_items' => 60,
        'correct_items' => 0,
        'xp_awarded' => 0,
        'started_at' => now()->subMinute(),
    ]);
}

beforeEach(function (): void {
    $this->withoutVite();

    config()->set(
        'citirx.research.allow_unvalidated_forms',
        false,
    );

    $this->seed([
        TosSeeder::class,
        ResearchQuestionSeeder::class,
    ]);
});

test('guests are redirected from pilot controls', function (): void {
    $this->get(route('admin.pilot.index'))
        ->assertRedirect(route('login'));
});

test('non administrators cannot access pilot controls', function (
    string $role,
): void {
    $user = User::factory()->create([
        'role' => $role,
    ]);

    $this->actingAs($user)
        ->get(route('admin.pilot.index'))
        ->assertForbidden();
})->with([
    'student',
    'faculty',
]);

test('an administrator can view pilot controls', function (): void {
    $administrator = pilotAdministrator();

    $this->actingAs($administrator)
        ->get(route('admin.pilot.index'))
        ->assertOk()
        ->assertSeeText('Pilot Controls')
        ->assertSeeText('Current pilot phase')
        ->assertSeeText('Cohort readiness')
        ->assertSeeText('Submission reasons');
});

test('the current administrator password is required', function (): void {
    $administrator = pilotAdministrator();
    $control = PilotControl::query()->sole();

    $this->actingAs($administrator)
        ->patch(
            route('admin.pilot.update'),
            pilotTransitionData(
                $control,
                PilotControl::PHASE_DIAGNOSTIC_OPEN,
                ['current_password' => 'wrong-password'],
            ),
        )
        ->assertSessionHasErrors('current_password');

    expect($control->fresh()->phase)
        ->toBe(PilotControl::PHASE_SETUP);
});

test('research form readiness blocks opening the diagnostic', function (): void {
    $administrator = pilotAdministrator();
    pilotCohort();
    $control = PilotControl::query()->sole();

    $this->actingAs($administrator)
        ->patch(
            route('admin.pilot.update'),
            pilotTransitionData(
                $control,
                PilotControl::PHASE_DIAGNOSTIC_OPEN,
            ),
        )
        ->assertSessionHas('error');

    expect($control->fresh()->phase)
        ->toBe(PilotControl::PHASE_SETUP);
});

test('the unsafe development override blocks official collection', function (): void {
    $administrator = pilotAdministrator();
    pilotMakeResearchFormsReady($administrator);
    pilotCohort();

    config()->set(
        'citirx.research.allow_unvalidated_forms',
        true,
    );

    $control = PilotControl::query()->sole();

    $this->actingAs($administrator)
        ->patch(
            route('admin.pilot.update'),
            pilotTransitionData(
                $control,
                PilotControl::PHASE_DIAGNOSTIC_OPEN,
            ),
        )
        ->assertSessionHas('error');

    expect($control->fresh()->phase)
        ->toBe(PilotControl::PHASE_SETUP);
});

test('the normal pilot phase sequence succeeds', function (): void {
    $administrator = pilotAdministrator();
    pilotMakeResearchFormsReady($administrator);
    pilotCohort();

    foreach ([
        PilotControl::PHASE_DIAGNOSTIC_OPEN,
        PilotControl::PHASE_BETWEEN_ASSESSMENTS,
        PilotControl::PHASE_POST_TEST_OPEN,
        PilotControl::PHASE_CLOSED,
    ] as $phase) {
        $control = PilotControl::query()->sole();

        $this->actingAs($administrator)
            ->patch(
                route('admin.pilot.update'),
                pilotTransitionData($control, $phase),
            )
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        expect(PilotControl::query()->sole()->phase)
            ->toBe($phase);
    }

    $control = PilotControl::query()->sole();

    expect($control->official_collection_started_at)
        ->not->toBeNull()
        ->and($control->official_collection_started_by)
        ->toBe($administrator->user_id);
});

test('skipping the normal phase sequence is rejected', function (): void {
    $administrator = pilotAdministrator();
    $control = PilotControl::query()->sole();

    $this->actingAs($administrator)
        ->patch(
            route('admin.pilot.update'),
            pilotTransitionData(
                $control,
                PilotControl::PHASE_POST_TEST_OPEN,
            ),
        )
        ->assertSessionHas('error');

    expect($control->fresh()->phase)
        ->toBe(PilotControl::PHASE_SETUP);
});

test('a stale lock version is rejected', function (): void {
    $administrator = pilotAdministrator();
    $control = PilotControl::query()->sole();

    $this->actingAs($administrator)
        ->patch(route('admin.pilot.update'), [
            'phase' => PilotControl::PHASE_DIAGNOSTIC_OPEN,
            'lock_version' => $control->lock_version + 50,
            'reason' => 'Attempting a stale administrative update.',
            'current_password' => 'password',
        ])
        ->assertSessionHas('error');

    expect($control->fresh()->phase)
        ->toBe(PilotControl::PHASE_SETUP);
});

test('an archived cohort does not satisfy readiness', function (): void {
    $administrator = pilotAdministrator();
    pilotMakeResearchFormsReady($administrator);
    pilotCohort(archived: true);
    $control = PilotControl::query()->sole();

    $this->actingAs($administrator)
        ->patch(
            route('admin.pilot.update'),
            pilotTransitionData(
                $control,
                PilotControl::PHASE_DIAGNOSTIC_OPEN,
            ),
        )
        ->assertSessionHas('error');

    expect($control->fresh()->phase)
        ->toBe(PilotControl::PHASE_SETUP);
});

test('an expired post test window blocks diagnostic opening', function (): void {
    $administrator = pilotAdministrator();
    pilotMakeResearchFormsReady($administrator);
    pilotCohort(expiredWindow: true);
    $control = PilotControl::query()->sole();

    $this->actingAs($administrator)
        ->patch(
            route('admin.pilot.update'),
            pilotTransitionData(
                $control,
                PilotControl::PHASE_DIAGNOSTIC_OPEN,
            ),
        )
        ->assertSessionHas('error');

    expect($control->fresh()->phase)
        ->toBe(PilotControl::PHASE_SETUP);
});

test('an emergency transition after collection begins is separately audited', function (): void {
    $administrator = pilotAdministrator();
    $control = pilotSetState(
        PilotControl::PHASE_DIAGNOSTIC_OPEN,
        $administrator,
    );

    $this->actingAs($administrator)
        ->patch(
            route('admin.pilot.update'),
            pilotTransitionData(
                $control,
                PilotControl::PHASE_CLOSED,
                ['emergency_override' => true],
            ),
        )
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status');

    expect(PilotControl::query()->sole()->phase)
        ->toBe(PilotControl::PHASE_CLOSED);

    $this->assertDatabaseHas('admin_audit_logs', [
        'admin_id' => $administrator->user_id,
        'action' => 'pilot.phase_emergency_override',
    ]);
});

test('an emergency override cannot start official collection', function (): void {
    $administrator = pilotAdministrator();
    $control = PilotControl::query()->sole();

    $this->actingAs($administrator)
        ->patch(
            route('admin.pilot.update'),
            pilotTransitionData(
                $control,
                PilotControl::PHASE_CLOSED,
                ['emergency_override' => true],
            ),
        )
        ->assertSessionHas('error');

    expect($control->fresh()->phase)
        ->toBe(PilotControl::PHASE_SETUP);
});

test('a closed diagnostic blocks a new session', function (): void {
    $administrator = pilotAdministrator();
    pilotSetState(PilotControl::PHASE_CLOSED, $administrator);
    $student = User::factory()->create();

    expect(
        fn () => app(DiagnosticService::class)
            ->startSession($student),
    )->toThrow(
        LogicException::class,
        'The official diagnostic is not currently open.',
    );
});

test('a student can resume an existing diagnostic after it closes', function (): void {
    $administrator = pilotAdministrator();
    pilotSetState(PilotControl::PHASE_CLOSED, $administrator);
    $student = User::factory()->create();

    $existing = pilotIncompleteSession(
        $student,
        'diagnostic',
        'pre_test',
    );

    $resumed = app(DiagnosticService::class)
        ->startSession($student);

    expect($resumed->session_id)
        ->toBe($existing->session_id);
});

test('a closed post test blocks a new attempt', function (): void {
    $administrator = pilotAdministrator();
    pilotSetState(PilotControl::PHASE_CLOSED, $administrator);
    $student = User::factory()->create([
        'is_diagnostic_completed' => true,
    ]);

    expect(
        fn () => app(MockBoardService::class)
            ->startPostTest($student),
    )->toThrow(
        LogicException::class,
        'The official mock-board examination is not currently open.',
    );
});

test('a student can resume an existing post test after it closes', function (): void {
    $administrator = pilotAdministrator();
    pilotSetState(PilotControl::PHASE_CLOSED, $administrator);
    $student = User::factory()->create([
        'is_diagnostic_completed' => true,
    ]);

    $existing = pilotIncompleteSession(
        $student,
        'mock_board',
        'post_test',
    );

    $resumed = app(MockBoardService::class)
        ->startPostTest($student);

    expect($resumed->session_id)
        ->toBe($existing->session_id);
});

test('research form publishing is locked after official collection begins', function (): void {
    $administrator = pilotAdministrator();
    pilotSetState(
        PilotControl::PHASE_DIAGNOSTIC_OPEN,
        $administrator,
    );

    $version = ResearchFormVersion::query()->firstOrFail();

    expect(
        fn () => app(
            ResearchFormVersionManagementService::class,
        )->publish(
            $administrator,
            $version,
            $version->lock_version,
            'Attempting to publish during official collection.',
        ),
    )->toThrow(
        LogicException::class,
        'Research configuration is locked',
    );
});

test('post test windows are locked after official collection begins', function (): void {
    $administrator = pilotAdministrator();
    pilotSetState(
        PilotControl::PHASE_DIAGNOSTIC_OPEN,
        $administrator,
    );
    $cohort = pilotCohort();

    expect(
        fn () => app(AdminCohortService::class)
            ->updatePostTestWindow(
                $administrator,
                $cohort,
                [
                    'post_test_opens_at' => now()
                        ->addDay()
                        ->toDateTimeString(),
                    'post_test_closes_at' => now()
                        ->addDays(2)
                        ->toDateTimeString(),
                    'clear_window' => false,
                ],
                'Attempting to change the official cohort window.',
            ),
    )->toThrow(
        LogicException::class,
        'Research configuration is locked',
    );
});

test('audit changes never contain the current password', function (): void {
    $administrator = pilotAdministrator();
    pilotMakeResearchFormsReady($administrator);
    pilotCohort();
    $control = PilotControl::query()->sole();

    $this->actingAs($administrator)
        ->patch(
            route('admin.pilot.update'),
            pilotTransitionData(
                $control,
                PilotControl::PHASE_DIAGNOSTIC_OPEN,
            ),
        )
        ->assertSessionHasNoErrors();

    $audit = AdminAuditLog::query()
        ->where('action', 'pilot.phase_transitioned')
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

test('direct pilot service use by a non administrator is rejected', function (): void {
    $student = User::factory()->create([
        'role' => 'student',
    ]);

    $control = PilotControl::query()->sole();

    expect(
        fn () => app(PilotControlService::class)
            ->transition($student, [
                'phase' => PilotControl::PHASE_DIAGNOSTIC_OPEN,
                'lock_version' => $control->lock_version,
                'reason' => 'Unauthorized direct service invocation.',
                'emergency_override' => false,
            ]),
    )->toThrow(
        LogicException::class,
        'Only administrators',
    );
});

test('emergency override cannot reopen an assessment', function (): void {
    $administrator = pilotAdministrator();

    $control = pilotSetState(
        PilotControl::PHASE_CLOSED,
        $administrator,
    );

    $this->actingAs($administrator)
        ->patch(
            route('admin.pilot.update'),
            pilotTransitionData(
                $control,
                PilotControl::PHASE_DIAGNOSTIC_OPEN,
                ['emergency_override' => true],
            ),
        )
        ->assertSessionHas('error');

    expect($control->fresh()->phase)
        ->toBe(PilotControl::PHASE_CLOSED);
});
