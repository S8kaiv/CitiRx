<?php

use App\Models\User;

it('allows only administrators to download the 100-row Form B authoring template', function (): void {
    $url = route('admin.research-form-imports.form-b-template');

    $this->get($url)->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create(['role' => 'faculty']))
        ->get($url)
        ->assertForbidden();

    $response = $this->actingAs(User::factory()->create(['role' => 'admin']))
        ->get($url)
        ->assertOk();

    $lines = preg_split('/\r\n|\n|\r/', trim($response->streamedContent()));
    $rows = array_map('str_getcsv', $lines);

    expect($rows)->toHaveCount(101)
        ->and($rows[1][0])->toBe('post_test_b')
        ->and($rows[1][1])->toBe('1')
        ->and($rows[100][1])->toBe('100')
        ->and($rows[1][4])->toBe('');
});
