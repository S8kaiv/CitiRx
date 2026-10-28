<?php

namespace App\Console\Commands;

use App\Models\SystemBackupRecord;
use Illuminate\Console\Command;

class RecordBackupStatusCommand extends Command
{
    protected $signature = 'system:record-backup
        {status : success or failed}
        {--size-bytes= : Required non-zero backup size for success}
        {--checksum= : Required SHA-256 checksum for success}';

    protected $description = 'Record the outcome of a server-managed backup job.';

    public function handle(): int
    {
        $status = mb_strtolower(
            trim((string) $this->argument('status')),
        );

        if (! in_array($status, SystemBackupRecord::STATUSES, true)) {
            $this->error('Status must be success or failed.');

            return self::INVALID;
        }

        $size = $this->option('size-bytes');

        if (
            $size !== null
            && filter_var(
                $size,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 0]],
            ) === false
        ) {
            $this->error('The backup size must be a non-negative integer.');

            return self::INVALID;
        }

        $checksum = mb_strtolower(
            trim((string) ($this->option('checksum') ?? '')),
        );

        if (
            $checksum !== ''
            && preg_match('/^[a-f0-9]{64}$/', $checksum) !== 1
        ) {
            $this->error('The checksum must be a valid SHA-256 value.');

            return self::INVALID;
        }

        if (
            $status === SystemBackupRecord::STATUS_SUCCESS
            && (
                $size === null
                || (int) $size < 1
                || $checksum === ''
            )
        ) {
            $this->error(
                'Successful backups require a non-zero size and SHA-256 checksum.',
            );

            return self::INVALID;
        }

        SystemBackupRecord::query()->create([
            'status' => $status,
            'size_bytes' => $size !== null
                ? (int) $size
                : null,
            'checksum_sha256' => $checksum !== ''
                ? $checksum
                : null,
            'completed_at' => now(),
        ]);

        $this->info('Backup status recorded.');

        return self::SUCCESS;
    }
}
