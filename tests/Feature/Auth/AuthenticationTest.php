<?php

use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect(route('login'));
});

test('the third failed login locks further attempts for the rate-limit window', function (): void {
    $this->freezeTime();

    $user = User::factory()->create();
    $wrong = ['email' => $user->email, 'password' => 'wrong-password'];
    $correct = ['email' => $user->email, 'password' => 'password'];
    $message = __('auth.throttle', ['seconds' => 60, 'minutes' => 1]);

    $this->post('/login', $wrong)->assertSessionHasErrors(['email' => __('auth.failed')]);
    $this->post('/login', $wrong)->assertSessionHasErrors(['email' => __('auth.failed')]);
    $this->post('/login', $wrong)->assertSessionHasErrors(['email' => $message]);
    $this->post('/login', $correct)->assertSessionHasErrors(['email' => $message]);
    $this->assertGuest();

    $this->travel(61)->seconds();

    $this->post('/login', $correct)->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticatedAs($user);
});
