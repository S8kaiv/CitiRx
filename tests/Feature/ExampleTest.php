<?php

use App\Models\User;

beforeEach(function (): void {
    $this->withoutVite();
});

it(
    'shows the landing page to guests',
    function () {
        $this
            ->get(route('welcome'))
            ->assertOk()
            ->assertViewIs('welcome')
            ->assertSeeText('Prepare for the PHLE')
            ->assertSee(
                'Quality Control/Quality Assurance'
            )
            ->assertDontSee(
                'Bronze toward Platinum'
            );
    }
);

it(
    'redirects authenticated users to the dashboard',
    function () {
        $user = User::factory()->create([
            'role' => 'student',
        ]);

        $this
            ->actingAs($user)
            ->get(route('welcome'))
            ->assertRedirect(
                route('dashboard')
            );
    }
);
