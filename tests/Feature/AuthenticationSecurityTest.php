<?php

use App\Models\User;

beforeEach(function (): void {
    $this->withoutVite();
});

it(
    'adds private no-cache headers to authenticated pages',
    function () {
        $user = User::factory()->create([
            'role' => 'student',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard'));

        $response->assertOk();

        $cacheControl = (string) $response->headers->get(
            'Cache-Control',
        );

        expect($cacheControl)
            ->toContain('no-store')
            ->toContain('no-cache')
            ->toContain('must-revalidate')
            ->toContain('max-age=0')
            ->toContain('private')
            ->and($response->headers->get('Pragma'))
            ->toBe('no-cache')
            ->and($response->headers->get('Expires'))
            ->toBe('0');
    },
);

it(
    'invalidates the session and protects the dashboard after logout',
    function () {
        $user = User::factory()->create([
            'role' => 'student',
        ]);

        $this->actingAs($user);

        $response = $this->post(
            route('logout'),
        );

        $response->assertRedirect(
            route('login'),
        );

        $this->assertGuest();

        $this->get(
            route('dashboard'),
        )->assertRedirect(
            route('login'),
        );
    },
);

it(
    'does not allow a student to access administrator pages',
    function () {
        $student = User::factory()->create([
            'role' => 'student',
        ]);

        $this
            ->actingAs($student)
            ->get(route('admin.questions.index'))
            ->assertForbidden();

        $this
            ->actingAs($student)
            ->get(route('admin.research-analytics.index'))
            ->assertForbidden();
    },
);

it(
    'allows only an administrator to access the question bank',
    function () {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this
            ->actingAs($admin)
            ->get(route('admin.questions.index'))
            ->assertOk()
            ->assertSee('Question Bank');
    },
);
