<?php

namespace App\Console\Commands;

use App\Services\MicroPearlImportService;
use Illuminate\Console\Command;

class PruneMicroPearlImports extends Command
{
    protected $signature =
        'content:prune-pearl-imports';

    protected $description =
        'Expire Micro Pearl import previews and remove private temporary CSV files';

    public function handle(
        MicroPearlImportService $imports,
    ): int {
        $count = $imports->pruneExpired();

        $this->info(
            "{$count} expired Micro Pearl "
                .'import record(s) processed.',
        );

        return self::SUCCESS;
    }
}
