<?php

use App\Models\AssessmentSession;
use App\Models\User;
use Database\Seeders\TierAndLevelSeeder;
use Database\Seeders\TosSeeder;

it('keeps terminal practice messages for the visible navigation after a background fetch', function (string $destination): void {
    $this->withoutVite();
    $this->seed([TierAndLevelSeeder::class, TosSeeder::class]);
    $student = User::factory()->create(['role' => 'student', 'is_diagnostic_completed' => true]);
    $session = AssessmentSession::query()->create([
        'user_id' => $student->user_id, 'session_type' => 'practice', 'started_at' => now()->subMinutes(5),
        'completed_at' => now(), 'total_items' => 0, 'correct_items' => 0, 'target_length' => 10, 'xp_awarded' => 0,
    ]);
    $url = $destination === 'summary' ? route('practice.summary', $session) : route('practice.intro');
    $notice = ['level_number' => 2, 'previous_level' => 1, 'tier_name' => 'Beginner'];
    $this->actingAs($student)->withSession([
        'level_up' => $notice, 'status' => 'Fixture status message.', 'error' => 'Fixture error explanation.',
        '_flash' => ['old' => ['level_up', 'status', 'error'], 'new' => []],
    ])->withCookie(config('session.cookie'), session()->getId());

    $this->withHeader('X-CitiRx-Practice', 'panel')->get($url)->assertOk()
        ->assertSessionHas('level_up', $notice)->assertSessionHas('status', 'Fixture status message.')
        ->assertSessionHas('error', 'Fixture error explanation.');
    $this->withoutHeader('X-CitiRx-Practice')->get($url)->assertOk()
        ->assertSessionMissing('level_up')->assertSessionMissing('status')->assertSessionMissing('error');
})->with(['intro', 'summary']);
