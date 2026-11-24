<?php

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

test('email verification screen can be rendered', function () {
    $user = User::factory()
        ->unverified()
        ->create();

    $response = $this
        ->actingAs($user)
        ->get('/verify-email');

    $response->assertStatus(200);
});

test('email can be verified', function () {
    $user = User::factory()
        ->unverified()
        ->create(['role' => 'faculty']);

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        [
            'id' => $user->getKey(),
            'hash' => sha1($user->getEmailForVerification()),
        ]
    );

    $response = $this
        ->actingAs($user)
        ->get($verificationUrl);

    Event::assertDispatched(Verified::class);

    expect(
        $user->fresh()->hasVerifiedEmail()
    )->toBeTrue();

    $response->assertRedirect(
        route('dashboard', absolute: false)
        .'?verified=1'
    );
});

test('email is not verified with invalid hash', function () {
    $user = User::factory()
        ->unverified()
        ->create(['role' => 'faculty']);

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        [
            'id' => $user->getKey(),
            'hash' => sha1('wrong-email@example.com'),
        ]
    );

    $this
        ->actingAs($user)
        ->get($verificationUrl);

    expect(
        $user->fresh()->hasVerifiedEmail()
    )->toBeFalse();

});

test('unverified users cannot access verified routes', function () {
    $user = User::factory()
        ->unverified()
        ->create([
            'role' => 'student',
        ]);

    $this
        ->actingAs($user)
        ->get(
            route('dashboard'),
        )
        ->assertRedirect(
            route('verification.notice'),
        );
});

test('only an admin can verify a student email in CitiRx', function (): void {
    $student = User::factory()->unverified()->create(['role' => 'student']);
    $admin = User::factory()->create(['role' => 'admin']);
    $dean = User::factory()->create(['role' => 'dean']);

    $verify = route('admin.users.verify-email', ['userId' => $student->user_id]);
    $payload = [
        'reviewed_email' => $student->email,
        'reason' => 'Confirmed in person against the school directory.',
    ];

    $oldLink = URL::temporarySignedRoute('verification.verify', now()->addMinutes(10), [
        'id' => $student->getKey(),
        'hash' => sha1($student->getEmailForVerification()),
    ]);

    $this->actingAs($student)
        ->get($oldLink)
        ->assertRedirect(route('verification.notice'));

    $this->post(route('verification.send'))->assertRedirect(route('verification.notice'));
    $this->get(route('verification.notice'))
        ->assertSee('Email verification pending')
        ->assertDontSee('Resend verification email');

    $this->actingAs($dean)->post($verify, $payload)->assertForbidden();
    expect($student->fresh()->hasVerifiedEmail())->toBeFalse();

    $this->actingAs($admin)->post($verify, [
        ...$payload,
        'reviewed_email' => 'old-address@example.com',
    ])->assertSessionHas('error');

    expect($student->fresh()->hasVerifiedEmail())->toBeFalse();

    $this->post($verify, $payload)->assertSessionHas('status', 'Student email verified.');
    expect($student->fresh()->hasVerifiedEmail())->toBeTrue();
});
