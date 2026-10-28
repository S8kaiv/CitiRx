<?php

namespace App\Services;

use App\Models\Question;
use App\Models\ResearchFormVersion;
use InvalidArgumentException;
use RuntimeException;

class ResearchFormVersionTargetService
{
    public function __construct(
        protected LegacyResearchCommandGuard $legacyGuard,
    ) {}

    public function resolve(
        string $form,
        ?string $versionCode,
        string $operation,
    ): ResearchFormVersion {
        $this->assertKnownForm($form);

        $versionCode = trim(
            (string) $versionCode,
        );

        /*
         * Backward-compatible behavior is permitted only while the
         * form still has exactly one active registered version.
         */
        if ($versionCode === '') {
            $this->legacyGuard->assertSingleVersion(
                $form,
                $operation,
            );

            return ResearchFormVersion::query()
                ->where('research_form', $form)
                ->where('is_active', true)
                ->where('active_slot', $form)
                ->sole();
        }

        $version = ResearchFormVersion::query()
            ->where('research_form', $form)
            ->where('version_code', $versionCode)
            ->first();

        if (! $version) {
            throw new RuntimeException(
                "{$operation} cannot run because "
                    ."{$versionCode} is not a registered "
                    ."version of {$form}.",
            );
        }

        return $version;
    }

    public function assertContentImportAllowed(
        ResearchFormVersion $version,
    ): void {
        if (
            $version->status
            !== ResearchFormVersion::STATUS_DRAFT
        ) {
            throw new RuntimeException(
                'Research content may only be imported into '
                    .'a draft form version.',
            );
        }

        $this->assertUnserved(
            $version,
            'Research content import',
        );
    }

    public function assertValidationImportAllowed(
        ResearchFormVersion $version,
    ): void {
        if (
            $version->status
            !== ResearchFormVersion::STATUS_IN_REVIEW
        ) {
            throw new RuntimeException(
                'Faculty validation may only be imported into '
                    .'a version that is in review.',
            );
        }

        $this->assertUnserved(
            $version,
            'Research validation import',
        );
    }

    private function assertUnserved(
        ResearchFormVersion $version,
        string $operation,
    ): void {
        if (
            $version->first_served_at !== null
            || $version->sessions()->exists()
        ) {
            throw new RuntimeException(
                "{$operation} cannot change "
                    ."{$version->version_code} because it "
                    .'has already been used by an assessment.',
            );
        }
    }

    private function assertKnownForm(
        string $form,
    ): void {
        if (
            ! in_array(
                $form,
                [
                    Question::FORM_PRE_TEST_A,
                    Question::FORM_POST_TEST_B,
                ],
                true,
            )
        ) {
            throw new InvalidArgumentException(
                "Unknown research form: {$form}",
            );
        }
    }
}
