<?php

use App\Models\User;

it('renders the public authentication pages', function (string $routeName) {
    $this
        ->get(route($routeName))
        ->assertOk()
        ->assertSee('auth-screen', false);
})->with([
    'login' => 'login',
    'registration' => 'register',
    'forgotten password' => 'password.request',
]);

it('renders the password reset form', function () {
    $this
        ->get(route('password.reset', [
            'token' => 'test-token',
            'email' => 'student@gmail.com',
        ]))
        ->assertOk()
        ->assertSee('name="token"', false)
        ->assertSee('name="password_confirmation"', false);
});

it('renders the password confirmation page without native autofocus', function () {
    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->get(route('password.confirm'))
        ->assertOk()
        ->assertDontSee(' autofocus', false)
        ->assertSee('pointer: fine', false);
});

it('renders the verification page for an unverified user', function () {
    $user = User::factory()
        ->unverified()
        ->create();

    $this
        ->actingAs($user)
        ->get(route('verification.notice'))
        ->assertOk()
        ->assertSee('Resend verification email');
});

it('connects registration hints to their fields', function () {
    $this
        ->get(route('register'))
        ->assertOk()
        ->assertSee('id="email-hint"', false)
        ->assertSee(
            'aria-describedby="email-hint"',
            false,
        );
});

it('connects registration errors to invalid fields', function () {
    $this
        ->from(route('register'))
        ->followingRedirects()
        ->post(route('register'), [
            'first_name' => '',
            'middle_name' => null,
            'last_name' => '',
            'email' => 'invalid',
            'password' => 'short',
            'password_confirmation' => 'different',
        ])
        ->assertOk()
        ->assertSee('aria-invalid="true"', false)
        ->assertSee('id="email-error"', false)
        ->assertSee(
            'aria-describedby="email-error"',
            false,
        );
});
