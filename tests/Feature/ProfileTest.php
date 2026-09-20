<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'first_name' => 'Test',
            'middle_name' => 'Middle',
            'last_name' => 'Student',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    expect($user->first_name)
        ->toBe('Test');

    expect($user->middle_name)
        ->toBe('Middle');

    expect($user->last_name)
        ->toBe('Student');

    expect($user->email)
        ->toBe('test@example.com');

    /*
     * Changing the email should require
     * verification again.
     */
    expect($user->email_verified_at)
        ->toBeNull();
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $originalVerifiedAt =
        $user->email_verified_at;

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'first_name' => $user->first_name,
            'middle_name' => $user->middle_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    expect($user->email_verified_at)
        ->not->toBeNull();

    expect(
        $user->email_verified_at->timestamp
    )->toBe(
        $originalVerifiedAt->timestamp
    );
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete('/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('login'));

    $this->assertGuest();

    /*
     * CitiRx uses SoftDeletes.
     *
     * The record remains but deleted_at
     * must now contain a timestamp.
     */
    $this->assertSoftDeleted(
        'users',
        [
            'user_id' => $user->user_id,
        ]
    );

    expect(
        $user->fresh()->deleted_at
    )->not->toBeNull();
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->delete('/profile', [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrorsIn(
            'userDeletion',
            ['password']
        )
        ->assertRedirect('/profile');

    expect(
        $user->fresh()->deleted_at
    )->toBeNull();
});

test('email can be reused after account deletion', function () {
    $email = 'reusable@example.com';

    $user = User::factory()->create([
        'email' => $email,
    ]);

    $this
        ->actingAs($user)
        ->delete('/profile', [
            'password' => 'password',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('login'));

    $this->assertGuest();

    $response = $this->post('/register', [
        'first_name' => 'New',
        'middle_name' => null,
        'last_name' => 'Student',
        'email' => $email,
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();

    $this->assertDatabaseHas('users', [
        'email' => $email,
        'deleted_at' => null,
    ]);
});
