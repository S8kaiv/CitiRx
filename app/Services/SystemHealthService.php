<?php

namespace App\Services;

use App\Models\SystemBackupRecord;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class SystemHealthService
{
    public const STATUS_OK = 'ok';

    public const STATUS_WARNING = 'warning';

    public const STATUS_ERROR = 'error';

    public const STATUS_UNKNOWN = 'unknown';

    /**
     * @return array{
     *     overall: string,
     *     checked_at: Carbon,
     *     checks: array<string, array<string, mixed>>
     * }
     */
    public function report(): array
    {
        $checks = [
            'environment' => $this->environmentCheck(),
            'debug' => $this->debugCheck(),
            'database' => $this->databaseCheck(),
            'cache' => $this->cacheCheck(),
            'queue' => $this->queueCheck(),
            'failed_jobs' => $this->failedJobsCheck(),
            'scheduler' => $this->schedulerCheck(),
            'mail' => $this->mailCheck(),
            'storage' => $this->storageCheck(),
            'version' => $this->versionCheck(),
            'backup' => $this->backupCheck(),
        ];

        return [
            'overall' => $this->worstStatus($checks),
            'checked_at' => now(),
            'checks' => $checks,
        ];
    }

    private function environmentCheck(): array
    {
        $environment = (string) app()->environment();

        return $this->check(
            'Application environment',
            self::STATUS_OK,
            Str::limit($environment, 30),
        );
    }

    private function debugCheck(): array
    {
        $enabled = (bool) config('app.debug');
        $production = app()->environment('production');

        return $this->check(
            'Debug mode',
            $enabled
                ? ($production
                    ? self::STATUS_ERROR
                    : self::STATUS_WARNING)
                : self::STATUS_OK,
            $enabled ? 'Enabled' : 'Disabled',
            $enabled
                ? 'Debug mode must be disabled before the pilot.'
                : 'Detailed exception output is disabled.',
        );
    }

    private function databaseCheck(): array
    {
        try {
            DB::select('SELECT 1');

            return $this->check(
                'Database connectivity',
                self::STATUS_OK,
                'Available',
            );
        } catch (Throwable) {
            return $this->check(
                'Database connectivity',
                self::STATUS_ERROR,
                'Unavailable',
                'The application could not complete a database health query.',
            );
        }
    }

    private function cacheCheck(): array
    {
        $key = 'citirx:health-check:'.Str::uuid();

        try {
            Cache::put($key, 'available', 10);
            $available = Cache::get($key) === 'available';
            Cache::forget($key);

            return $this->check(
                'Cache availability',
                $available
                    ? self::STATUS_OK
                    : self::STATUS_ERROR,
                $available ? 'Available' : 'Unavailable',
                'Driver: '.Str::limit(
                    (string) config('cache.default'),
                    30,
                ),
            );
        } catch (Throwable) {
            return $this->check(
                'Cache availability',
                self::STATUS_ERROR,
                'Unavailable',
            );
        }
    }

    private function queueCheck(): array
    {
        $connection = (string) config('queue.default');
        $unsafeForProduction = app()->environment('production')
            && $connection === 'sync';

        return $this->check(
            'Queue configuration',
            $unsafeForProduction
                ? self::STATUS_WARNING
                : self::STATUS_OK,
            Str::limit($connection, 30),
            $unsafeForProduction
                ? 'Use a persistent queue worker for production.'
                : 'A queue connection is configured.',
        );
    }

    private function failedJobsCheck(): array
    {
        try {
            if (! Schema::hasTable('failed_jobs')) {
                return $this->check(
                    'Failed jobs',
                    self::STATUS_UNKNOWN,
                    'Table unavailable',
                );
            }

            $count = DB::table('failed_jobs')->count();

            return $this->check(
                'Failed jobs',
                $count === 0
                    ? self::STATUS_OK
                    : self::STATUS_WARNING,
                number_format($count),
                $count === 0
                    ? 'No failed queue jobs are recorded.'
                    : 'Review failed jobs without exposing their payloads here.',
            );
        } catch (Throwable) {
            return $this->check(
                'Failed jobs',
                self::STATUS_UNKNOWN,
                'Unable to check',
            );
        }
    }

    private function schedulerCheck(): array
    {
        try {
            $value = Cache::get(
                config('system-health.scheduler_cache_key'),
            );

            if (! is_string($value) || $value === '') {
                return $this->check(
                    'Scheduler heartbeat',
                    self::STATUS_WARNING,
                    'Not recorded',
                    'Run the scheduler continuously in staging and production.',
                );
            }

            $heartbeat = Carbon::parse($value);
            $ageMinutes = $heartbeat->diffInMinutes(now());
            $limit = max(
                1,
                (int) config(
                    'system-health.scheduler_stale_minutes',
                    5,
                ),
            );

            return $this->check(
                'Scheduler heartbeat',
                $ageMinutes <= $limit
                    ? self::STATUS_OK
                    : self::STATUS_WARNING,
                $heartbeat->diffForHumans(),
                "Expected within {$limit} minute(s).",
            );
        } catch (Throwable) {
            return $this->check(
                'Scheduler heartbeat',
                self::STATUS_UNKNOWN,
                'Unable to check',
            );
        }
    }

    private function mailCheck(): array
    {
        $mailer = (string) config('mail.default');
        $transport = (string) config(
            "mail.mailers.{$mailer}.transport",
            $mailer,
        );
        $developmentOnly = in_array(
            $transport,
            ['array', 'log'],
            true,
        );
        $warning = app()->environment('production')
            && $developmentOnly;

        return $this->check(
            'Mail configuration',
            $warning
                ? self::STATUS_WARNING
                : self::STATUS_OK,
            Str::limit($transport, 30),
            $warning
                ? 'The configured transport does not deliver production email.'
                : 'A mail transport is configured.',
        );
    }

    private function storageCheck(): array
    {
        $directories = [
            'Application storage' => storage_path('app'),
            'Framework cache' => storage_path('framework/cache'),
            'Framework sessions' => storage_path('framework/sessions'),
            'Compiled views' => storage_path('framework/views'),
            'Logs' => storage_path('logs'),
            'Bootstrap cache' => base_path('bootstrap/cache'),
        ];

        $unwritable = collect($directories)
            ->filter(
                fn (string $path): bool => ! is_dir($path)
                    || ! is_writable($path),
            )
            ->keys()
            ->values();

        return $this->check(
            'Writable storage',
            $unwritable->isEmpty()
                ? self::STATUS_OK
                : self::STATUS_ERROR,
            $unwritable->isEmpty()
                ? 'Available'
                : 'Needs attention',
            $unwritable->isEmpty()
                ? 'Required application directories are writable.'
                : 'Unwritable: '.$unwritable->implode(', '),
        );
    }

    private function versionCheck(): array
    {
        $version = trim((string) config(
            'system-health.application_version',
        ));
        $commit = trim((string) config(
            'system-health.application_commit',
        ));

        $safeVersion = preg_match(
            '/^[A-Za-z0-9._+\-]{1,50}$/',
            $version,
        ) === 1
            ? $version
            : null;

        $safeCommit = preg_match(
            '/^[a-fA-F0-9]{7,40}$/',
            $commit,
        ) === 1
            ? mb_substr($commit, 0, 12)
            : null;

        return $this->check(
            'Application version',
            $safeVersion !== null || $safeCommit !== null
                ? self::STATUS_OK
                : self::STATUS_UNKNOWN,
            collect([
                $safeVersion,
                $safeCommit,
            ])->filter()->implode(' · ') ?: 'Not supplied',
            'Set APP_VERSION and APP_COMMIT during deployment.',
        );
    }

    private function backupCheck(): array
    {
        try {
            if (! Schema::hasTable('system_backup_records')) {
                return $this->check(
                    'Latest backup attempt',
                    self::STATUS_UNKNOWN,
                    'Table unavailable',
                );
            }

            $backup = SystemBackupRecord::query()
                ->latest('completed_at')
                ->latest('created_at')
                ->first();

            if ($backup === null) {
                return $this->check(
                    'Latest backup attempt',
                    self::STATUS_WARNING,
                    'No backup recorded',
                    'Connect the deployment backup job to system:record-backup.',
                );
            }

            if (
                $backup->status
                === SystemBackupRecord::STATUS_FAILED
            ) {
                return $this->check(
                    'Latest backup attempt',
                    self::STATUS_ERROR,
                    'Failed',
                    'The most recent server-managed backup attempt failed.',
                );
            }

            $ageHours = $backup->completed_at
                ->diffInHours(now());

            $limit = max(
                1,
                (int) config(
                    'system-health.backup_stale_hours',
                    25,
                ),
            );

            return $this->check(
                'Latest successful backup',
                $ageHours <= $limit
                    ? self::STATUS_OK
                    : self::STATUS_WARNING,
                $backup->completed_at->diffForHumans(),
                $ageHours <= $limit
                    ? 'A recent successful backup is recorded.'
                    : "The latest successful backup is older than {$limit} hour(s).",
            );
        } catch (Throwable) {
            return $this->check(
                'Latest backup attempt',
                self::STATUS_UNKNOWN,
                'Unable to check',
            );
        }
    }

    private function check(
        string $label,
        string $status,
        string $summary,
        ?string $detail = null,
    ): array {
        return [
            'label' => $label,
            'status' => $status,
            'summary' => $summary,
            'detail' => $detail,
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $checks
     */
    private function worstStatus(array $checks): string
    {
        $weight = [
            self::STATUS_OK => 0,
            self::STATUS_UNKNOWN => 1,
            self::STATUS_WARNING => 2,
            self::STATUS_ERROR => 3,
        ];

        return collect($checks)
            ->pluck('status')
            ->sortByDesc(
                fn (string $status): int => $weight[$status] ?? 3,
            )
            ->first() ?? self::STATUS_UNKNOWN;
    }
}
