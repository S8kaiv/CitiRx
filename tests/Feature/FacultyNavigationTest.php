<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows the complete faculty navigation on pearl management pages', function (): void {
    $faculty = User::factory()->create([
        'role' => 'faculty',
        'email_verified_at' => now(),
    ]);

    $response = $this
        ->actingAs($faculty)
        ->get(route('content.pearls.index'));

    $response
        ->assertOk()
        ->assertSeeText('Dashboard')
        ->assertSeeText('Interventions')
        ->assertSeeText('Pearl Library')
        ->assertSeeText('Manage Pearls')
        ->assertSee(
            route('faculty.dashboard'),
            false,
        )
        ->assertSee(
            route('faculty.interventions.index'),
            false,
        )
        ->assertSee(
            route('pearls.index'),
            false,
        )
        ->assertSee(
            route('content.pearls.index'),
            false,
        )
        ->assertSee('aria-current="page"', false);
});

it('shows a useful first-pearl empty state', function (): void {
    $faculty = User::factory()->create([
        'role' => 'faculty',
        'email_verified_at' => now(),
    ]);

    $this
        ->actingAs($faculty)
        ->get(route('content.pearls.index'))
        ->assertOk()
        ->assertSeeText('No Micro Pearls have been created yet.')
        ->assertSeeText('Create First Draft');
});
