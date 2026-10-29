<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests are redirected from the style guide', function (): void {
    $this
        ->get(route('admin.style-guide'))
        ->assertRedirect(route('login'));
});

test('students cannot view the style guide', function (): void {
    $student = User::factory()->create([
        'role' => 'student',
    ]);

    $this
        ->actingAs($student)
        ->get(route('admin.style-guide'))
        ->assertForbidden();
});

test('faculty cannot view the style guide', function (): void {
    $faculty = User::factory()->create([
        'role' => 'faculty',
    ]);

    $this
        ->actingAs($faculty)
        ->get(route('admin.style-guide'))
        ->assertForbidden();
});

test('administrators can view the style guide', function (): void {
    $administrator = User::factory()->create([
        'role' => 'admin',
    ]);

    $this
        ->actingAs($administrator)
        ->get(route('admin.style-guide'))
        ->assertOk()
        ->assertSeeText('Interface style guide')
        ->assertSeeText('Student')
        ->assertSeeText('Faculty')
        ->assertSeeText('Admin')
        ->assertSeeText('Light')
        ->assertSeeText('Dark');
});
