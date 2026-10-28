<?php

namespace App\Console\Commands;

use App\Models\ResearchFormVersion;
use App\Services\ResearchFormAuditService;
use Illuminate\Console\Command;

class AuditResearchForms extends Command
{
    protected $signature = 'research:audit-forms
        {--form= : Audit one form only}
        {--form-version= : Audit one exact registered version code}
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

        $selectedVersionCode = trim(
            (string) ($this->option('form-version') ?? ''),
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

        $requireValidation = (bool) $this->option(
            'require-validation',
        );

        if ($selectedVersionCode !== '') {
            return $this->auditExactVersion(
                audit: $audit,
                selectedForm: $selectedForm,
                versionCode: $selectedVersionCode,
                requireValidation: $requireValidation,
            );
        }

        $forms = $selectedForm === ''
            ? array_keys(
                $audit->formDefinitions(),
            )
            : [$selectedForm];

        $failed = false;

        foreach ($forms as $form) {
            $report = $audit->audit(
                $form,
                $requireValidation,
            );

            $this->displayReport(
                "{$form} active version",
                $report,
            );

            if ($report['errors'] !== []) {
                $failed = true;
            }
        }

        return $failed
            ? self::FAILURE
            : self::SUCCESS;
    }

    private function auditExactVersion(
        ResearchFormAuditService $audit,
        string $selectedForm,
        string $versionCode,
        bool $requireValidation,
    ): int {
        $version = ResearchFormVersion::query()
            ->where('version_code', $versionCode)
            ->first();

        if (! $version) {
            $this->error(
                "Unknown research-form version: {$versionCode}",
            );

            return self::FAILURE;
        }

        if (
            $selectedForm !== ''
            && $version->research_form
                !== $selectedForm
        ) {
            $this->error(
                "{$versionCode} belongs to "
                    ."{$version->research_form}, not "
                    ."{$selectedForm}.",
            );

            return self::FAILURE;
        }

        $report = $audit->auditVersion(
            $version,
            $requireValidation,
        );

        $this->displayReport(
            "{$version->research_form} "
                ."({$version->version_code})",
            $report,
        );

        return $report['errors'] === []
            ? self::SUCCESS
            : self::FAILURE;
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function displayReport(
        string $label,
        array $report,
    ): void {
        $this->newLine();

        $this->info(
            "Research-form audit: {$label}",
        );

        $this->table(
            ['Check', 'Result'],
            collect($report['console_metrics'])
                ->map(
                    fn (
                        mixed $value,
                        string $metric,
                    ): array => [
                        $metric,
                        $value,
                    ],
                )
                ->values()
                ->all(),
        );

        if ($report['errors'] === []) {
            $this->info(
                "{$label} passed the requested audit.",
            );

            return;
        }

        $this->error(
            "{$label} failed with "
                .count($report['errors'])
                .' issue(s):',
        );

        foreach ($report['errors'] as $error) {
            $this->line(
                "  - {$error}",
            );
        }
    }
}
