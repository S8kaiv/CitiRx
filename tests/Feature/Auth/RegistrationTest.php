<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'first_name' => 'Test',
        'middle_name' => null,
        'last_name' => 'Student',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();

    $response->assertRedirect(
        route('dashboard', absolute: false)
    );
});

test('registration form uses the required name fields', function () {
    $response = $this->get('/register');

    $response
        ->assertOk()
        ->assertSee('name="first_name"', false)
        ->assertSee('name="middle_name"', false)
        ->assertSee('name="last_name"', false);
});
