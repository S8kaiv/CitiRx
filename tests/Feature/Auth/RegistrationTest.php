<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('registration screen can be rendered', function () {
    $this
        ->get(
            route('register'),
        )
        ->assertOk();
});

test('new users can register and receive verification email', function () {
    Notification::fake();

    $response = $this->post(
        route('register'),
        [
            'first_name' => 'Test',
            'middle_name' => null,
            'last_name' => 'Student',
            'email' => 'citirx.registration@gmail.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ],
    );

    $user = User::query()
        ->where(
            'email',
            'citirx.registration@gmail.com',
        )
        ->firstOrFail();

    $this->assertAuthenticatedAs(
        $user,
    );

    expect(
        $user->email_verified_at,
    )->toBeNull();

    $response->assertRedirect(
        route('verification.notice'),
    );

    Notification::assertSentTo(
        $user,
        VerifyEmail::class,
    );
});

test('registration form uses the required name fields', function () {
    $this
        ->get(
            route('register'),
        )
        ->assertOk()
        ->assertSee(
            'name="first_name"',
            false,
        )
        ->assertSee(
            'name="middle_name"',
            false,
        )
        ->assertSee(
            'name="last_name"',
            false,
        );
});

test('registration normalizes names and email', function () {
    Notification::fake();

    $this
        ->post(
            route('register'),
            [
                'first_name' => '  Test  ',
                'middle_name' => '  Sample  ',
                'last_name' => '  Student  ',
                'email' => '  CitiRx.Student@GMAIL.COM  ',
                'password' => 'password',
                'password_confirmation' => 'password',
            ],
        )
        ->assertRedirect(
            route('verification.notice'),
        );

    $this->assertDatabaseHas(
        'users',
        [
            'first_name' => 'Test',
            'middle_name' => 'Sample',
            'last_name' => 'Student',
            'email' => 'citirx.student@gmail.com',
        ],
    );
});

test(
    'registration rejects disposable and reserved email domains',
    function (string $email): void {
        $this
            ->post(
                route('register'),
                [
                    'first_name' => 'Temporary',
                    'middle_name' => null,
                    'last_name' => 'Account',
                    'email' => $email,
                    'password' => 'password',
                    'password_confirmation' => 'password',
                ],
            )
            ->assertSessionHasErrors(
                'email',
            );

        $this->assertGuest();

        $this->assertDatabaseMissing(
            'users',
            [
                'email' => mb_strtolower(
                    trim($email),
                ),
            ],
        );
    },
)->with([
    'disposable domain' => 'student@mailinator.com',

    'disposable subdomain' => 'student@inbox.mailinator.com',

    'reserved domain' => 'student@example.com',
]);

test('registration rejects malformed email addresses', function () {
    $this
        ->post(
            route('register'),
            [
                'first_name' => 'Invalid',
                'middle_name' => null,
                'last_name' => 'Address',
                'email' => 'not-an-email',
                'password' => 'password',
                'password_confirmation' => 'password',
            ],
        )
        ->assertSessionHasErrors(
            'email',
        );

    $this->assertGuest();
});

test('strict allowlist rejects domains that are not approved', function () {
    config()->set(
        'registration.allowed_email_domains',
        [
            'gmail.com',
        ],
    );

    $this
        ->post(
            route('register'),
            [
                'first_name' => 'Unapproved',
                'middle_name' => null,
                'last_name' => 'Domain',
                'email' => 'student@yahoo.com',
                'password' => 'password',
                'password_confirmation' => 'password',
            ],
        )
        ->assertSessionHasErrors(
            'email',
        );

    $this->assertGuest();
});
