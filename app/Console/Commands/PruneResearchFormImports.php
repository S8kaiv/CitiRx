<?php

namespace App\Console\Commands;

use App\Services\ResearchFormImportWorkflowService;
use Illuminate\Console\Command;

class PruneResearchFormImports extends Command
{
    protected $signature =
        'research:prune-imports';

    protected $description =
        'Expire research-form previews and remove private temporary CSV files';

    public function handle(
        ResearchFormImportWorkflowService $imports,
    ): int {
        $count = $imports->pruneExpired();

        $this->info(
            "{$count} expired research-form "
                .'import record(s) processed.',
        );

        return self::SUCCESS;
    }
}
