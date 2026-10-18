<?php

namespace App\Console\Commands;

use App\Services\ResearchFormAuditService;
use Illuminate\Console\Command;

class AuditResearchForms extends Command
{
    protected $signature = 'research:audit-forms
        {--form= : Audit one form only}
        {--require-validation : Require both validation fields}';

    protected $description =
        'Audit research forms for blueprint, content, '
        .'answer-key, and validation integrity';

    public function handle(
        ResearchFormAuditService $audit,
    ): int {
        $selectedForm = trim(
            (string) ($this->option('form') ?? ''),
        );

        if (
            $selectedForm !== ''
            && ! $audit->hasForm($selectedForm)
        ) {
            $this->error(
                "Unknown research form: {$selectedForm}",
            );

            return self::FAILURE;
        }

        $forms = $selectedForm === ''
            ? array_keys($audit->formDefinitions())
            : [$selectedForm];

        $requireValidation = (bool) $this->option(
            'require-validation',
        );

        $failed = false;

        foreach ($forms as $form) {
            $report = $audit->audit(
                $form,
                $requireValidation,
            );

            $this->newLine();
            $this->info(
                "Research-form audit: {$form}",
            );

            $this->table(
                ['Check', 'Result'],
                collect($report['console_metrics'])
                    ->map(
                        fn (
                            mixed $value,
                            string $label,
                        ): array => [
                            $label,
                            $value,
                        ],
                    )
                    ->values()
                    ->all(),
            );

            if ($report['errors'] === []) {
                $this->info(
                    "{$form} passed the requested audit.",
                );

                continue;
            }

            $failed = true;

            $this->error(
                "{$form} failed with "
                .count($report['errors'])
                .' issue(s):',
            );

            foreach ($report['errors'] as $error) {
                $this->line("  - {$error}");
            }
        }

        return $failed
            ? self::FAILURE
            : self::SUCCESS;
    }
}
