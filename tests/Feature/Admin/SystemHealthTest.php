<?php

use App\Models\SystemBackupRecord;
use App\Models\User;
use App\Services\SystemHealthService;
use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    $this->withoutVite();
});

function systemHealthAdmin(string $role = 'admin'): User
{
    return User::factory()->create([
        'role' => $role,
    ]);
}

test('guests are redirected from system health', function (): void {
    $this->get(route('admin.system-health.index'))
        ->assertRedirect(route('login'));
});

test('non administrators cannot view system health', function (
    string $role,
): void {
    $this->actingAs(systemHealthAdmin($role))
        ->get(route('admin.system-health.index'))
        ->assertForbidden();
})->with([
    'student',
    'faculty',
]);

test('administrators can view safe system health information', function (): void {
    $administrator = systemHealthAdmin();

    $databaseSecret = 'database-super-secret';
    $mailSecret = 'mail-super-secret';
    $applicationKey = 'base64:'.base64_encode(
        str_repeat('k', 32),
    );

    config()->set(
        'database.connections.mysql.password',
        $databaseSecret,
    );

    config()->set(
        'mail.mailers.smtp.password',
        $mailSecret,
    );

    config()->set(
        'app.key',
        $applicationKey,
    );

    $this->actingAs($administrator)
        ->get(route('admin.system-health.index'))
        ->assertOk()
        ->assertSee('System Health')
        ->assertSee('Database connectivity')
        ->assertSee('Scheduler heartbeat')
        ->assertSee('Latest backup attempt')
        ->assertDontSee($databaseSecret, false)
        ->assertDontSee($mailSecret, false)
        ->assertDontSee($applicationKey, false);
});

test('debug mode produces a warning or error', function (): void {
    config()->set('app.debug', true);

    $report = app(SystemHealthService::class)->report();

    expect($report['checks']['debug']['status'])
        ->toBeIn(['warning', 'error'])
        ->and($report['checks']['debug']['summary'])
        ->toBe('Enabled');
});

test('a current scheduler heartbeat is healthy', function (): void {
    Cache::put(
        config('system-health.scheduler_cache_key'),
        now()->toIso8601String(),
        now()->addHour(),
    );

    $report = app(SystemHealthService::class)->report();

    expect($report['checks']['scheduler']['status'])
        ->toBe(SystemHealthService::STATUS_OK);
});

test('a stale scheduler heartbeat produces a warning', function (): void {
    config()->set(
        'system-health.scheduler_stale_minutes',
        5,
    );

    Cache::put(
        config('system-health.scheduler_cache_key'),
        now()->subMinutes(10)->toIso8601String(),
        now()->addHour(),
    );

    $report = app(SystemHealthService::class)->report();

    expect($report['checks']['scheduler']['status'])
        ->toBe(SystemHealthService::STATUS_WARNING);
});

test('a recent successful backup is healthy', function (): void {
    SystemBackupRecord::query()->create([
        'status' => SystemBackupRecord::STATUS_SUCCESS,
        'size_bytes' => 1024,
        'checksum_sha256' => str_repeat('a', 64),
        'completed_at' => now()->subHour(),
    ]);

    $report = app(SystemHealthService::class)->report();

    expect($report['checks']['backup']['status'])
        ->toBe(SystemHealthService::STATUS_OK);
});

test('a stale successful backup produces a warning', function (): void {
    config()->set('system-health.backup_stale_hours', 25);

    SystemBackupRecord::query()->create([
        'status' => SystemBackupRecord::STATUS_SUCCESS,
        'completed_at' => now()->subHours(30),
    ]);

    $report = app(SystemHealthService::class)->report();

    expect($report['checks']['backup']['status'])
        ->toBe(SystemHealthService::STATUS_WARNING);
});

test('the heartbeat command records scheduler activity', function (): void {
    Cache::forget(
        config('system-health.scheduler_cache_key'),
    );

    $this->artisan('system:heartbeat')
        ->assertSuccessful();

    expect(
        Cache::get(
            config('system-health.scheduler_cache_key'),
        ),
    )->not->toBeNull();
});

test('the backup command records only validated status data', function (): void {
    $this->artisan('system:record-backup', [
        'status' => 'success',
        '--size-bytes' => '2048',
        '--checksum' => str_repeat('b', 64),
    ])->assertSuccessful();

    $record = SystemBackupRecord::query()->firstOrFail();

    expect($record->status)
        ->toBe(SystemBackupRecord::STATUS_SUCCESS)
        ->and($record->size_bytes)
        ->toBe(2048)
        ->and($record->checksum_sha256)
        ->toBe(str_repeat('b', 64));
});

test('system health exposes no mutation endpoint', function (): void {
    $administrator = systemHealthAdmin();

    $this->actingAs($administrator)
        ->post('/admin/system-health')
        ->assertStatus(405);
});

test('a failed latest backup attempt is reported', function (): void {
    SystemBackupRecord::query()->create([
        'status' => SystemBackupRecord::STATUS_SUCCESS,
        'size_bytes' => 2048,
        'checksum_sha256' => str_repeat('a', 64),
        'completed_at' => now()->subHours(2),
    ]);

    SystemBackupRecord::query()->create([
        'status' => SystemBackupRecord::STATUS_FAILED,
        'completed_at' => now()->subHour(),
    ]);

    $report = app(SystemHealthService::class)->report();

    expect($report['checks']['backup']['status'])
        ->toBe(SystemHealthService::STATUS_ERROR)
        ->and($report['checks']['backup']['summary'])
        ->toBe('Failed');
});

test('the backup command rejects an unverifiable success', function (): void {
    $this->artisan('system:record-backup', [
        'status' => 'success',
    ])->assertFailed();

    expect(SystemBackupRecord::query()->count())
        ->toBe(0);
});
