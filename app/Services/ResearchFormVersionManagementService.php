<?php

namespace App\Services;

use App\Models\Question;
use App\Models\ResearchFormVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LogicException;
use RuntimeException;

class ResearchFormVersionManagementService
{
    public function __construct(
        protected ResearchFormAuditService $audit,
        protected AdminAuditService $adminAudit,
    ) {}

    public function copyToDraft(
        User $administrator,
        ResearchFormVersion $source,
        string $versionCode,
        int $expectedLockVersion,
        string $reason,
    ): ResearchFormVersion {
        $this->assertAdministrator(
            $administrator,
        );

        return DB::transaction(
            function () use (
                $administrator,
                $source,
                $versionCode,
                $expectedLockVersion,
                $reason,
            ): ResearchFormVersion {
                $source = $this->lockedVersion(
                    $source,
                    $expectedLockVersion,
                );

                $this->assertVersionCodeMatchesForm(
                    $source->research_form,
                    $versionCode,
                );

                $questions = Question::query()
                    ->where(
                        'form_version_id',
                        $source->form_version_id,
                    )
                    ->where('is_active', true)
                    ->with([
                        'choices' => fn ($query) => $query->orderBy(
                            'choice_letter',
                        ),
                    ])
                    ->orderBy('form_position')
                    ->get();

                if ($questions->count() !== 60) {
                    throw new RuntimeException(
                        'Only a complete 60-item version '
                            .'can be copied.',
                    );
                }

                if (
                    $questions->contains(
                        fn (Question $question): bool => $question->choices->count()
                            !== 4,
                    )
                ) {
                    throw new RuntimeException(
                        'Every source question must have '
                            .'exactly four choices.',
                    );
                }

                $versionNumber =
                    $this->versionNumber(
                        $versionCode,
                    );

                $draft = ResearchFormVersion::query()
                    ->create([
                        'research_form' => $source->research_form,

                        'version_number' => $versionNumber,

                        'version_code' => $versionCode,

                        'status' => ResearchFormVersion::STATUS_DRAFT,

                        'is_active' => false,
                        'active_slot' => null,

                        'created_by' => $administrator->user_id,

                        'lock_version' => 1,
                    ]);

                $now = now();
                $questionRows = [];
                $choiceRows = [];

                foreach ($questions as $question) {
                    $newQuestionId =
                        (string) Str::uuid();

                    $questionRows[] = [
                        'question_id' => $newQuestionId,

                        'competency_id' => $question->competency_id,

                        'question_text' => $question->question_text,

                        'hypercorrection_rationale' => $question
                            ->hypercorrection_rationale,

                        'is_diagnostic_pool' => $question
                            ->is_diagnostic_pool,

                        'difficulty_index_p' => null,
                        'speed_flag_count' => 0,
                        'is_active' => true,

                        'research_form' => $source->research_form,

                        'form_version_id' => $draft->form_version_id,

                        'form_position' => $question->form_position,

                        'cognitive_level' => $question->cognitive_level,

                        'research_validated_at' => null,
                        'research_validated_by' => null,
                        'first_served_at' => null,
                        'edit_version' => 1,
                        'created_at' => $now,
                    ];

                    foreach (
                        $question->choices as $choice
                    ) {
                        $choiceRows[] = [
                            'choice_id' => (string) Str::uuid(),

                            'question_id' => $newQuestionId,

                            'choice_letter' => $choice->choice_letter,

                            'choice_text' => $choice->choice_text,

                            'is_correct' => $choice->is_correct,

                            'distractor_fallacy_note' => $choice
                                ->distractor_fallacy_note,
                        ];
                    }
                }

                /*
                 * Two bulk inserts instead of 300+ individual
                 * model operations.
                 */
                Question::query()->insert(
                    $questionRows,
                );

                DB::table(
                    'question_choices',
                )->insert($choiceRows);

                $oldLockVersion =
                    $source->lock_version;

                $source->increment(
                    'lock_version',
                );

                $this->adminAudit->record(
                    $administrator,
                    'research_form_version.created',
                    $draft,
                    [
                        'source_version' => [
                            'old' => null,
                            'new' => $source->version_code,
                        ],

                        'version_code' => [
                            'old' => null,
                            'new' => $draft->version_code,
                        ],

                        'question_count' => [
                            'old' => 0,
                            'new' => 60,
                        ],

                        'source_lock_version' => [
                            'old' => $oldLockVersion,
                            'new' => $source->fresh()
                                ->lock_version,
                        ],
                    ],
                    $reason,
                );

                return $draft->fresh();
            },
            attempts: 3,
        );
    }

    public function submitForReview(
        User $administrator,
        ResearchFormVersion $version,
        int $expectedLockVersion,
        string $reason,
    ): ResearchFormVersion {
        return $this->transition(
            administrator: $administrator,
            version: $version,
            expectedLockVersion: $expectedLockVersion,
            from: ResearchFormVersion::STATUS_DRAFT,
            to: ResearchFormVersion::STATUS_IN_REVIEW,
            requireValidation: false,
            action: 'research_form_version.submitted',
            reason: $reason,
        );
    }

    public function markValidated(
        User $administrator,
        ResearchFormVersion $version,
        int $expectedLockVersion,
        string $reason,
    ): ResearchFormVersion {
        return $this->transition(
            administrator: $administrator,
            version: $version,
            expectedLockVersion: $expectedLockVersion,
            from: ResearchFormVersion::STATUS_IN_REVIEW,
            to: ResearchFormVersion::STATUS_VALIDATED,
            requireValidation: true,
            action: 'research_form_version.validated',
            reason: $reason,
        );
    }

    public function publish(
        User $administrator,
        ResearchFormVersion $version,
        int $expectedLockVersion,
        string $reason,
    ): ResearchFormVersion {
        $this->assertAdministrator(
            $administrator,
        );

        return DB::transaction(
            function () use (
                $administrator,
                $version,
                $expectedLockVersion,
                $reason,
            ): ResearchFormVersion {
                $version = $this->lockedVersion(
                    $version,
                    $expectedLockVersion,
                );

                if (
                    $version->status
                    !== ResearchFormVersion::STATUS_VALIDATED
                ) {
                    throw ValidationException::withMessages([
                        'version' => (
                            'Only a validated version can be published.'
                        ),
                    ]);
                }

                if (
                    (bool) config(
                        'citirx.research.allow_unvalidated_forms',
                        false,
                    )
                ) {
                    throw ValidationException::withMessages([
                        'version' => (
                            'Publishing is disabled while the '
                            .'unvalidated research-form override is enabled.'
                        ),
                    ]);
                }

                $report = $this->audit->auditVersion(
                    $version,
                    requireValidation: true,
                );

                if ($report['errors'] !== []) {
                    throw ValidationException::withMessages([
                        'version' => $report['errors'],
                    ]);
                }

                $existingActive =
                    ResearchFormVersion::query()
                        ->where(
                            'research_form',
                            $version->research_form,
                        )
                        ->where('is_active', true)
                        ->whereKeyNot(
                            $version->form_version_id,
                        )
                        ->lockForUpdate()
                        ->get();

                foreach (
                    $existingActive as $activeVersion
                ) {
                    $activeVersion->forceFill([
                        'status' => ResearchFormVersion::STATUS_RETIRED,

                        'is_active' => false,
                        'active_slot' => null,

                        'lock_version' => $activeVersion
                            ->lock_version + 1,
                    ])->save();
                }

                $oldStatus = $version->status;

                $version->forceFill([
                    'status' => ResearchFormVersion::STATUS_PUBLISHED,

                    'is_active' => true,

                    'active_slot' => $version->research_form,

                    'published_by' => $administrator->user_id,

                    'published_at' => now(),

                    'lock_version' => $version->lock_version + 1,
                ])->save();

                $this->adminAudit->record(
                    $administrator,
                    'research_form_version.published',
                    $version,
                    [
                        'status' => [
                            'old' => $oldStatus,
                            'new' => ResearchFormVersion::STATUS_PUBLISHED,
                        ],

                        'active_version' => [
                            'old' => $existingActive
                                ->pluck('version_code')
                                ->all(),

                            'new' => $version->version_code,
                        ],
                    ],
                    $reason,
                );

                return $version->fresh();
            },
            attempts: 3,
        );
    }

    private function transition(
        User $administrator,
        ResearchFormVersion $version,
        int $expectedLockVersion,
        string $from,
        string $to,
        bool $requireValidation,
        string $action,
        string $reason,
    ): ResearchFormVersion {
        $this->assertAdministrator(
            $administrator,
        );

        return DB::transaction(
            function () use (
                $administrator,
                $version,
                $expectedLockVersion,
                $from,
                $to,
                $requireValidation,
                $action,
                $reason,
            ): ResearchFormVersion {
                $version = $this->lockedVersion(
                    $version,
                    $expectedLockVersion,
                );

                if ($version->status !== $from) {
                    throw ValidationException::withMessages([
                        'version' => (
                            "Version must be {$from} "
                            ."before changing to {$to}."
                        ),
                    ]);
                }

                $report = $this->audit->auditVersion(
                    $version,
                    $requireValidation,
                );

                if ($report['errors'] !== []) {
                    throw ValidationException::withMessages([
                        'version' => $report['errors'],
                    ]);
                }

                $oldLockVersion =
                    $version->lock_version;

                $version->forceFill([
                    'status' => $to,

                    'lock_version' => $oldLockVersion + 1,
                ])->save();

                $this->adminAudit->record(
                    $administrator,
                    $action,
                    $version,
                    [
                        'status' => [
                            'old' => $from,
                            'new' => $to,
                        ],

                        'lock_version' => [
                            'old' => $oldLockVersion,
                            'new' => $version->lock_version,
                        ],
                    ],
                    $reason,
                );

                return $version->fresh();
            },
            attempts: 3,
        );
    }

    private function lockedVersion(
        ResearchFormVersion $version,
        int $expectedLockVersion,
    ): ResearchFormVersion {
        $locked = ResearchFormVersion::query()
            ->whereKey(
                $version->form_version_id,
            )
            ->lockForUpdate()
            ->firstOrFail();

        if (
            $locked->lock_version
            !== $expectedLockVersion
        ) {
            throw ValidationException::withMessages([
                'expected_lock_version' => (
                    'This version changed in another request. '
                    .'Refresh the page before trying again.'
                ),
            ]);
        }

        return $locked;
    }

    private function assertVersionCodeMatchesForm(
        string $form,
        string $versionCode,
    ): void {
        $pattern = '/^'
            .preg_quote($form, '/')
            .'_v([1-9][0-9]*)$/';

        if (
            preg_match(
                $pattern,
                $versionCode,
            ) !== 1
        ) {
            throw ValidationException::withMessages([
                'version_code' => (
                    "Use {$form}_v2, {$form}_v3, "
                    .'and so on.'
                ),
            ]);
        }
    }

    private function versionNumber(
        string $versionCode,
    ): int {
        preg_match(
            '/_v([1-9][0-9]*)$/',
            $versionCode,
            $matches,
        );

        return (int) $matches[1];
    }

    private function assertAdministrator(
        User $administrator,
    ): void {
        if ($administrator->role !== 'admin') {
            throw new LogicException(
                'Only administrators may manage '
                    .'research-form versions.',
            );
        }
    }
}
