<?php

use App\Models\Level;
use App\Models\User;
use App\Services\LevelService;
use Database\Seeders\TierAndLevelSeeder;

beforeEach(function () {
    $this->seed(TierAndLevelSeeder::class);
});

it('syncs the level from total XP', function () {
    $user = User::factory()->create([
        'total_xp' => 700,
        'current_level' => 1,
    ]);

    $notice = app(LevelService::class)
        ->sync($user);

    expect($user->fresh()->current_level)
        ->toBe(4)
        ->and($notice)
        ->toBe([
            'level_number' => 4,
            'tier_name' => 'Intermediate',
        ]);
});

it('initializes an empty level cache without a false notification', function () {
    $user = User::factory()->create([
        'total_xp' => 0,
        'current_level' => null,
    ]);

    $notice = app(LevelService::class)
        ->sync($user);

    expect($user->fresh()->current_level)
        ->toBe(1)
        ->and($notice)
        ->toBeNull();
});

it('repairs a downward mismatch without a false level-up notification', function () {
    $user = User::factory()->create([
        'total_xp' => 100,
        'current_level' => 4,
    ]);

    $notice = app(LevelService::class)
        ->sync($user);

    expect($user->fresh()->current_level)
        ->toBe(2)
        ->and($notice)
        ->toBeNull();
});

it('shows progress toward the next level', function () {
    $user = User::factory()->create([
        'total_xp' => 200,
        'current_level' => 2,
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('progress.index'));

    $response
        ->assertOk()
        ->assertViewHas(
            'currentLevel',
            fn (Level $level) =>
                $level->level_number === 2
        )
        ->assertViewHas(
            'nextLevel',
            fn (Level $level) =>
                $level->level_number === 3
        )
        ->assertViewHas('xpToNext', 50)
        ->assertViewHas(
            'progressPercent',
            fn ($value) =>
                $value === 66.7
        );
});

it('handles the maximum level', function () {
    $user = User::factory()->create([
        'total_xp' => 12000,
        'current_level' => 9,
    ]);

    $progress = app(LevelService::class)
        ->progress($user);

    expect($user->fresh()->current_level)
        ->toBe(10)
        ->and($progress['currentLevel']->level_number)
        ->toBe(10)
        ->and($progress['nextLevel'])
        ->toBeNull()
        ->and($progress['xpToNext'])
        ->toBe(0)
        ->and($progress['progressPercent'])
        ->toBe(100.0);
});