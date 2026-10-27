<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;

class SystemHeartbeatCommand extends Command
{
    protected $signature = 'system:heartbeat';

    protected $description = 'Record the scheduler heartbeat used by system health.';

    public function handle(): int
    {
        try {
            Cache::put(
                config('system-health.scheduler_cache_key'),
                now()->toIso8601String(),
                now()->addDay(),
            );
        } catch (Throwable) {
            $this->error(
                'The scheduler heartbeat could not be recorded.',
            );

            return self::FAILURE;
        }

        $this->info('Scheduler heartbeat recorded.');

        return self::SUCCESS;
    }
}
