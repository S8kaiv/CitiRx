<?php

use App\Models\Cohort;
use App\Models\User;
use App\Services\LeaderboardService;
use Illuminate\Support\Collection;

beforeEach(function (): void {
    $this->withoutVite();
});

function leaderboardCohort(
    string $name = 'Leaderboard Cohort',
): Cohort {
    static $sequence = 0;

    $sequence++;

    return Cohort::query()->create([
        'cohort_name' => $name.' '.$sequence,
        'academic_year' => '2026-2027',
    ]);
}

function leaderboardStudent(
    Cohort $cohort,
    string $firstName,
    string $lastName,
    int $xp,
    array $attributes = [],
): User {
    static $sequence = 0;

    $sequence++;

    return User::factory()->create(
        array_merge(
            [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'role' => 'student',
                'cohort_id' => $cohort->cohort_id,
                'student_id' => 'LB-'.str_pad(
                    (string) $sequence,
                    5,
                    '0',
                    STR_PAD_LEFT,
                ),
                'total_xp' => $xp,
                'current_level' => null,
                'email_verified_at' => now(),
            ],
            $attributes,
        ),
    );
}

it('redirects guests from the leaderboard', function () {
    $this
        ->get(
            route('leaderboard.index'),
        )
        ->assertRedirect(
            route('login'),
        );
});

it(
    'blocks non student roles from the leaderboard',
    function (string $role): void {
        $user = User::factory()->create([
            'role' => $role,
            'email_verified_at' => now(),
        ]);

        $this
            ->actingAs($user)
            ->get(
                route('leaderboard.index'),
            )
            ->assertForbidden();
    },
)->with([
    'faculty' => 'faculty',
    'administrator' => 'admin',
]);

it('requires the student email to be verified', function () {
    $cohort = leaderboardCohort();

    $student = User::factory()
        ->unverified()
        ->create([
            'role' => 'student',
            'cohort_id' => $cohort->cohort_id,
        ]);

    $this
        ->actingAs($student)
        ->get(
            route('leaderboard.index'),
        )
        ->assertRedirect(
            route('verification.notice'),
        );
});

it('shows a safe state when the student has no cohort', function () {
    $student = User::factory()->create([
        'role' => 'student',
        'cohort_id' => null,
    ]);

    $this
        ->actingAs($student)
        ->get(
            route('leaderboard.index'),
        )
        ->assertOk()
        ->assertSeeText(
            'No cohort assigned',
        )
        ->assertViewHas(
            'totalStudents',
            0,
        );
});

it('shows only verified students from the same cohort', function () {
    $ownCohort = leaderboardCohort(
        'Own Cohort',
    );

    $otherCohort = leaderboardCohort(
        'Other Cohort',
    );

    $viewer = leaderboardStudent(
        $ownCohort,
        'Viewer',
        'Student',
        400,
    );

    leaderboardStudent(
        $ownCohort,
        'Visible',
        'Learner',
        300,
    );

    leaderboardStudent(
        $otherCohort,
        'Hidden',
        'Outsider',
        9999,
    );

    leaderboardStudent(
        $ownCohort,
        'Pending',
        'Verification',
        800,
        [
            'email_verified_at' => null,
        ],
    );

    $this
        ->actingAs($viewer)
        ->get(
            route('leaderboard.index'),
        )
        ->assertOk()
        ->assertSeeText(
            'Viewer S.',
        )
        ->assertSeeText(
            'Visible L.',
        )
        ->assertDontSeeText(
            'Hidden O.',
        )
        ->assertDontSeeText(
            'Pending V.',
        )
        ->assertViewHas(
            'totalStudents',
            2,
        );
});

it('excludes soft deleted students', function () {
    $cohort = leaderboardCohort();

    $viewer = leaderboardStudent(
        $cohort,
        'Active',
        'Viewer',
        100,
    );

    $deleted = leaderboardStudent(
        $cohort,
        'Deleted',
        'Student',
        5000,
    );

    $deleted->delete();

    $this
        ->actingAs($viewer)
        ->get(
            route('leaderboard.index'),
        )
        ->assertOk()
        ->assertDontSeeText(
            'Deleted S.',
        )
        ->assertViewHas(
            'totalStudents',
            1,
        );
});

it('orders students by descending XP', function () {
    $cohort = leaderboardCohort();

    leaderboardStudent(
        $cohort,
        'Top',
        'Student',
        1000,
    );

    leaderboardStudent(
        $cohort,
        'Middle',
        'Student',
        500,
    );

    $viewer = leaderboardStudent(
        $cohort,
        'Bottom',
        'Student',
        100,
    );

    $this
        ->actingAs($viewer)
        ->get(
            route('leaderboard.index'),
        )
        ->assertOk()
        ->assertViewHas(
            'currentRank',
            3,
        )
        ->assertViewHas(
            'rows',
            function (Collection $rows): bool {
                return $rows
                    ->pluck('total_xp')
                    ->all() === [
                        1000,
                        500,
                        100,
                    ];
            },
        );
});

it('assigns competition ranks when XP values are tied', function () {
    $cohort = leaderboardCohort();

    leaderboardStudent(
        $cohort,
        'First',
        'Alpha',
        500,
    );

    leaderboardStudent(
        $cohort,
        'Second',
        'Beta',
        500,
    );

    $viewer = leaderboardStudent(
        $cohort,
        'Third',
        'Gamma',
        100,
    );

    $data = app(
        LeaderboardService::class,
    )->forStudent(
        $viewer,
    );

    expect(
        $data['rows']
            ->pluck('rank')
            ->all(),
    )->toBe([
        1,
        1,
        3,
    ]);
});

it('shows the current student summary and highlights their row', function () {
    $cohort = leaderboardCohort();

    leaderboardStudent(
        $cohort,
        'Top',
        'Student',
        1000,
    );

    $viewer = leaderboardStudent(
        $cohort,
        'Current',
        'Viewer',
        100,
    );

    $this
        ->actingAs($viewer)
        ->get(
            route('leaderboard.index'),
        )
        ->assertOk()
        ->assertSeeText(
            'Your rank',
        )
        ->assertSeeText(
            '(you)',
        )
        ->assertViewHas(
            'currentRank',
            2,
        );
});

it('shows only a first name and last initial', function () {
    $cohort = leaderboardCohort();

    $student = leaderboardStudent(
        $cohort,
        'Juan',
        'Dela Cruz',
        500,
    );

    $viewer = leaderboardStudent(
        $cohort,
        'Viewer',
        'Student',
        100,
    );

    $this
        ->actingAs($viewer)
        ->get(
            route('leaderboard.index'),
        )
        ->assertOk()
        ->assertSeeText(
            'Juan D.',
        )
        ->assertDontSeeText(
            'Dela Cruz',
        )
        ->assertDontSee(
            $student->email,
        );
});

it('does not render database user identifiers', function () {
    $cohort = leaderboardCohort();

    $student = leaderboardStudent(
        $cohort,
        'Juan',
        'Cruz',
        500,
    );

    $viewer = leaderboardStudent(
        $cohort,
        'Viewer',
        'Student',
        100,
    );

    $this
        ->actingAs($viewer)
        ->get(
            route('leaderboard.index'),
        )
        ->assertOk()
        ->assertDontSee(
            (string) $student->user_id,
        )
        ->assertDontSee(
            (string) $viewer->user_id,
        );
});

it('handles a whitespace-only last name defensively', function () {
    $cohort = leaderboardCohort();

    $student = leaderboardStudent(
        $cohort,
        'Singleton',
        '   ',
        100,
    );

    $this
        ->actingAs($student)
        ->get(
            route('leaderboard.index'),
        )
        ->assertOk()
        ->assertSeeText(
            'Singleton',
        )
        ->assertDontSeeText(
            'Singleton .',
        );
});
