<?php

use App\Models\Cohort;
use App\Models\FacultyIntervention;
use App\Models\TosCompetency;
use App\Models\TosDomain;
use App\Models\User;
use App\Models\UserKnowledgeState;
use App\Services\FacultyDashboardService;

beforeEach(function (): void {
    $this->withoutVite();
});

function facultyDashboardCohort(
    string $name = 'Faculty Dashboard Cohort',
): Cohort {
    static $sequence = 0;

    $sequence++;

    return Cohort::query()->create([
        'cohort_name' => $name.' '.$sequence,

        'academic_year' => '2026-2027',
    ]);
}

function facultyDashboardUser(
    string $role,
    ?Cohort $cohort = null,
    array $attributes = [],
): User {
    static $sequence = 0;

    $sequence++;

    return User::factory()->create(
        array_merge(
            [
                'first_name' => ucfirst($role),

                'last_name' => 'Dashboard'.$sequence,

                'role' => $role,

                'cohort_id' => $cohort?->cohort_id,

                'student_id' => $role === 'student'
                        ? 'FD-'.str_pad(
                            (string) $sequence,
                            5,
                            '0',
                            STR_PAD_LEFT,
                        )
                        : null,

                'is_diagnostic_completed' => false,

                'predicted_readiness_pct' => null,
            ],
            $attributes,
        ),
    );
}

function facultyDashboardDomain(
    string $name = 'Test Domain',
): TosDomain {
    static $sequence = 0;

    $sequence++;

    return TosDomain::query()->create([
        'domain_number' => $sequence,

        'domain_name' => $name.' '.$sequence,

        'prc_weight_percentage' => 10.00,
    ]);
}

function facultyDashboardCompetency(
    TosDomain $domain,
    int $order,
    float $weight,
): TosCompetency {
    static $sequence = 0;

    $sequence++;

    return TosCompetency::query()->create([
        'domain_id' => $domain->domain_id,

        'competency_code' => 'FD-'.$sequence,

        'title' => 'Faculty competency '.$sequence,

        'order_index' => $order,

        'tos_weight_percentage' => $weight,

        'bkt_transition_p_t' => 0.1000,
    ]);
}

function facultyDashboardIntervention(
    User $faculty,
    User $student,
    TosDomain $domain,
    array $attributes = [],
): FacultyIntervention {
    return FacultyIntervention::query()->create(
        array_merge(
            [
                'faculty_id' => $faculty->user_id,

                'target_user_id' => $student->user_id,

                'domain_id' => $domain->domain_id,

                'competency_id' => null,

                'intervention_type' => 'advisory',

                'custom_message' => null,

                'is_completed' => false,

                'assigned_at' => now(),
            ],
            $attributes,
        ),
    );
}

it('redirects guests from the faculty dashboard', function () {
    $this
        ->get(
            route('faculty.dashboard'),
        )
        ->assertRedirect(
            route('login'),
        );
});

it(
    'blocks non faculty users from faculty routes',
    function (string $role): void {
        $cohort =
            facultyDashboardCohort();

        $user =
            facultyDashboardUser(
                $role,
                $cohort,
            );

        $this
            ->actingAs($user)
            ->get(
                route('faculty.dashboard'),
            )
            ->assertForbidden();
    },
)->with([
    'student' => 'student',
    'administrator' => 'admin',
]);

it('shows a safe state when faculty has no cohort', function () {
    $faculty =
        facultyDashboardUser(
            'faculty',
        );

    $this
        ->actingAs($faculty)
        ->get(
            route('faculty.dashboard'),
        )
        ->assertOk()
        ->assertSee(
            'No cohort assigned',
        );
});

it('shows only students from the faculty cohort', function () {
    $ownCohort =
        facultyDashboardCohort(
            'Own Cohort',
        );

    $otherCohort =
        facultyDashboardCohort(
            'Other Cohort',
        );

    $faculty =
        facultyDashboardUser(
            'faculty',
            $ownCohort,
        );

    facultyDashboardUser(
        'student',
        $ownCohort,
        [
            'first_name' => 'VisibleStudent',
        ],
    );

    facultyDashboardUser(
        'student',
        $otherCohort,
        [
            'first_name' => 'HiddenStudent',
        ],
    );

    $this
        ->actingAs($faculty)
        ->get(
            route('faculty.dashboard'),
        )
        ->assertOk()
        ->assertSee(
            'VisibleStudent',
        )
        ->assertDontSee(
            'HiddenStudent',
        );
});

it('excludes soft deleted students', function () {
    $cohort =
        facultyDashboardCohort();

    $faculty =
        facultyDashboardUser(
            'faculty',
            $cohort,
        );

    facultyDashboardUser(
        'student',
        $cohort,
        [
            'first_name' => 'ActiveStudent',
        ],
    );

    $deleted =
        facultyDashboardUser(
            'student',
            $cohort,
            [
                'first_name' => 'DeletedStudent',
            ],
        );

    $deleted->delete();

    $this
        ->actingAs($faculty)
        ->get(
            route('faculty.dashboard'),
        )
        ->assertOk()
        ->assertSee(
            'ActiveStudent',
        )
        ->assertDontSee(
            'DeletedStudent',
        );
});

it('calculates cohort summary and readiness bands', function () {
    $cohort =
        facultyDashboardCohort();

    $faculty =
        facultyDashboardUser(
            'faculty',
            $cohort,
        );

    facultyDashboardUser(
        'student',
        $cohort,
        [
            'is_diagnostic_completed' => true,

            'predicted_readiness_pct' => 80.00,
        ],
    );

    facultyDashboardUser(
        'student',
        $cohort,
        [
            'is_diagnostic_completed' => true,

            'predicted_readiness_pct' => 60.00,
        ],
    );

    facultyDashboardUser(
        'student',
        $cohort,
    );

    $response =
        $this
            ->actingAs($faculty)
            ->get(
                route('faculty.dashboard'),
            );

    $response
        ->assertOk()
        ->assertViewHas(
            'summary',
            fn (array $summary): bool => $summary['total_students']
                    === 3
                && $summary[
                    'diagnostic_completed'
                ] === 2
                && $summary[
                    'diagnostic_completion_pct'
                ] === 66.7
                && $summary[
                    'avg_readiness'
                ] === 70.0,
        )
        ->assertViewHas(
            'readinessBands',
            fn (array $bands): bool => $bands['board_ready']
                    === 1
                && $bands['approaching']
                    === 1
                && $bands['developing']
                    === 0
                && $bands['at_risk']
                    === 0
                && $bands['no_data']
                    === 1,
        );
});

it('uses weighted competency mastery for domains', function () {
    $cohort =
        facultyDashboardCohort();

    $faculty =
        facultyDashboardUser(
            'faculty',
            $cohort,
        );

    $student =
        facultyDashboardUser(
            'student',
            $cohort,
            [
                'is_diagnostic_completed' => true,

                'predicted_readiness_pct' => 70.00,
            ],
        );

    $domain =
        facultyDashboardDomain();

    $first =
        facultyDashboardCompetency(
            $domain,
            1,
            75.0,
        );

    $second =
        facultyDashboardCompetency(
            $domain,
            2,
            25.0,
        );

    UserKnowledgeState::query()->create([
        'user_id' => $student->user_id,

        'competency_id' => $first->competency_id,

        'current_mastery_p_l' => 0.8000,
    ]);

    UserKnowledgeState::query()->create([
        'user_id' => $student->user_id,

        'competency_id' => $second->competency_id,

        'current_mastery_p_l' => 0.4000,
    ]);

    $data = app(
        FacultyDashboardService::class,
    )->forFaculty(
        $faculty,
    );

    $row = $data['domainMastery']
        ->firstWhere(
            'domain_id',
            $domain->domain_id,
        );

    expect($row)
        ->not->toBeNull()
        ->and($row['avg_mastery_pct'])
        ->toBe(70.0);
});

it('creates an intervention for a student in the cohort', function () {
    $cohort =
        facultyDashboardCohort();

    $faculty =
        facultyDashboardUser(
            'faculty',
            $cohort,
        );

    $student =
        facultyDashboardUser(
            'student',
            $cohort,
        );

    $domain =
        facultyDashboardDomain();

    $competency =
        facultyDashboardCompetency(
            $domain,
            1,
            100.0,
        );

    $this
        ->actingAs($faculty)
        ->post(
            route(
                'faculty.interventions.store',
            ),
            [
                'target_user_id' => $student->user_id,

                'domain_id' => $domain->domain_id,

                'competency_id' => $competency
                    ->competency_id,

                'intervention_type' => 'study_plan',

                'custom_message' => 'Review this competency.',
            ],
        )
        ->assertRedirect(
            route('faculty.dashboard'),
        );

    $this->assertDatabaseHas(
        'faculty_interventions',
        [
            'faculty_id' => $faculty->user_id,

            'target_user_id' => $student->user_id,

            'domain_id' => $domain->domain_id,

            'competency_id' => $competency
                ->competency_id,

            'intervention_type' => 'study_plan',

            'is_completed' => false,
        ],
    );
});

it('rejects targets outside the faculty cohort', function () {
    $facultyCohort =
        facultyDashboardCohort();

    $otherCohort =
        facultyDashboardCohort();

    $faculty =
        facultyDashboardUser(
            'faculty',
            $facultyCohort,
        );

    $student =
        facultyDashboardUser(
            'student',
            $otherCohort,
        );

    $domain =
        facultyDashboardDomain();

    $this
        ->actingAs($faculty)
        ->post(
            route(
                'faculty.interventions.store',
            ),
            [
                'target_user_id' => $student->user_id,

                'domain_id' => $domain->domain_id,

                'intervention_type' => 'advisory',
            ],
        )
        ->assertSessionHasErrors(
            'target_user_id',
        );

    expect(
        FacultyIntervention::query()
            ->count(),
    )->toBe(0);
});

it('rejects a competency from a different domain', function () {
    $cohort =
        facultyDashboardCohort();

    $faculty =
        facultyDashboardUser(
            'faculty',
            $cohort,
        );

    $student =
        facultyDashboardUser(
            'student',
            $cohort,
        );

    $selectedDomain =
        facultyDashboardDomain();

    $otherDomain =
        facultyDashboardDomain();

    $wrongCompetency =
        facultyDashboardCompetency(
            $otherDomain,
            1,
            100.0,
        );

    $this
        ->actingAs($faculty)
        ->post(
            route(
                'faculty.interventions.store',
            ),
            [
                'target_user_id' => $student->user_id,

                'domain_id' => $selectedDomain->domain_id,

                'competency_id' => $wrongCompetency
                    ->competency_id,

                'intervention_type' => 'advisory',
            ],
        )
        ->assertSessionHasErrors(
            'competency_id',
        );

    expect(
        FacultyIntervention::query()
            ->count(),
    )->toBe(0);
});

it('prevents duplicate open interventions', function () {
    $cohort =
        facultyDashboardCohort();

    $faculty =
        facultyDashboardUser(
            'faculty',
            $cohort,
        );

    $student =
        facultyDashboardUser(
            'student',
            $cohort,
        );

    $domain =
        facultyDashboardDomain();

    $payload = [
        'target_user_id' => $student->user_id,

        'domain_id' => $domain->domain_id,

        'intervention_type' => 'advisory',
    ];

    $this
        ->actingAs($faculty)
        ->post(
            route(
                'faculty.interventions.store',
            ),
            $payload,
        )
        ->assertRedirect(
            route('faculty.dashboard'),
        );

    $this
        ->actingAs($faculty)
        ->post(
            route(
                'faculty.interventions.store',
            ),
            $payload,
        )
        ->assertSessionHasErrors(
            'target_user_id',
        );

    expect(
        FacultyIntervention::query()
            ->count(),
    )->toBe(1);
});

it('completes an owned intervention only once', function () {
    $cohort =
        facultyDashboardCohort();

    $faculty =
        facultyDashboardUser(
            'faculty',
            $cohort,
        );

    $student =
        facultyDashboardUser(
            'student',
            $cohort,
        );

    $domain =
        facultyDashboardDomain();

    $intervention =
        facultyDashboardIntervention(
            $faculty,
            $student,
            $domain,
        );

    $route = route(
        'faculty.interventions.complete',
        [
            'intervention' => $intervention
                ->intervention_id,
        ],
    );

    $this
        ->actingAs($faculty)
        ->patch($route)
        ->assertRedirect(
            route(
                'faculty.interventions.index',
            ),
        );

    $firstCompletedAt =
        $intervention
            ->fresh()
            ->completed_at;

    $this
        ->actingAs($faculty)
        ->patch($route)
        ->assertRedirect(
            route(
                'faculty.interventions.index',
            ),
        );

    expect(
        $intervention
            ->fresh()
            ->is_completed,
    )
        ->toBeTrue()
        ->and(
            $intervention
                ->fresh()
                ->completed_at
                ?->equalTo(
                    $firstCompletedAt,
                ),
        )
        ->toBeTrue();
});

it('does not reveal another faculty intervention', function () {
    $cohort =
        facultyDashboardCohort();

    $owner =
        facultyDashboardUser(
            'faculty',
            $cohort,
        );

    $otherFaculty =
        facultyDashboardUser(
            'faculty',
            $cohort,
        );

    $student =
        facultyDashboardUser(
            'student',
            $cohort,
        );

    $domain =
        facultyDashboardDomain();

    $intervention =
        facultyDashboardIntervention(
            $owner,
            $student,
            $domain,
        );

    $this
        ->actingAs($otherFaculty)
        ->patch(
            route(
                'faculty.interventions.complete',
                [
                    'intervention' => $intervention
                        ->intervention_id,
                ],
            ),
        )
        ->assertNotFound();

    expect(
        $intervention
            ->fresh()
            ->is_completed,
    )->toBeFalse();
});

it('shows only the logged in faculty intervention history', function () {
    $cohort =
        facultyDashboardCohort();

    $faculty =
        facultyDashboardUser(
            'faculty',
            $cohort,
        );

    $otherFaculty =
        facultyDashboardUser(
            'faculty',
            $cohort,
        );

    $student =
        facultyDashboardUser(
            'student',
            $cohort,
        );

    $domain =
        facultyDashboardDomain();

    facultyDashboardIntervention(
        $faculty,
        $student,
        $domain,
        [
            'custom_message' => 'VISIBLE_INTERVENTION',
        ],
    );

    facultyDashboardIntervention(
        $otherFaculty,
        $student,
        $domain,
        [
            'custom_message' => 'HIDDEN_INTERVENTION',
        ],
    );

    $this
        ->actingAs($faculty)
        ->get(
            route(
                'faculty.interventions.index',
            ),
        )
        ->assertOk()
        ->assertSee(
            'VISIBLE_INTERVENTION',
        )
        ->assertDontSee(
            'HIDDEN_INTERVENTION',
        );
});
