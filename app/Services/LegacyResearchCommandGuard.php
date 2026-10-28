<?php

namespace App\Services;

use App\Models\ResearchFormVersion;
use RuntimeException;

class LegacyResearchCommandGuard
{
    public function assertSingleVersion(
        string $form,
        string $operation,
    ): void {
        /*
         * One indexed query retrieves everything needed for the
         * safety check. These commands are administrative operations,
         * so this does not affect normal application performance.
         */
        $versions = ResearchFormVersion::query()
            ->where('research_form', $form)
            ->orderBy('version_number')
            ->get([
                'form_version_id',
                'version_code',
                'is_active',
                'active_slot',
            ]);

        if ($versions->isEmpty()) {
            throw new RuntimeException(
                "{$operation} cannot run because {$form} "
                    .'has no registered form version.',
            );
        }

        if ($versions->count() > 1) {
            throw new RuntimeException(
                "{$operation} cannot run because {$form} "
                    .'has multiple registered versions: '
                    .$versions
                        ->pluck('version_code')
                        ->implode(', ')
                    .'. Use the version-aware administrator '
                    .'workflow instead.',
            );
        }

        $version = $versions->firstOrFail();

        if (
            ! $version->is_active
            || $version->active_slot !== $form
        ) {
            throw new RuntimeException(
                "{$operation} cannot run because the only "
                    ."registered {$form} version is not active.",
            );
        }
    }
}
