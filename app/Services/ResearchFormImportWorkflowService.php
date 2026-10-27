<?php

namespace App\Services;

use App\Models\Question;
use App\Models\ResearchFormImport;
use App\Models\ResearchFormVersion;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LogicException;
use RuntimeException;
use Throwable;

class ResearchFormImportWorkflowService
{
    public function __construct(
        protected ResearchFormVersionTargetService $targets,
        protected PilotControlService $pilotControls,
        protected AdminAuditService $audit,
    ) {}

    public function createPreview(
        User $administrator,
        UploadedFile $file,
        string $type,
        string $versionId,
        ?string $validatorId,
    ): ResearchFormImport {
        $this->assertAdministrator(
            $administrator,
        );

        $type = mb_strtolower(
            trim($type),
        );

        if (
            ! in_array(
                $type,
                ResearchFormImport::TYPES,
                true,
            )
        ) {
            throw ValidationException::withMessages([
                'import_type' => [
                    'The selected import type is invalid.',
                ],
            ]);
        }

        $storedPath = $this->storeUpload(
            $administrator,
            $file,
        );

        try {
            return DB::transaction(
                function () use (
                    $administrator,
                    $file,
                    $type,
                    $versionId,
                    $validatorId,
                    $storedPath,
                ): ResearchFormImport {
                    /*
                     * This obtains the pilot-control row lock.
                     * Configuration cannot change after official
                     * collection has begun.
                     */
                    $this->pilotControls
                        ->assertResearchConfigurationMutable();

                    $version = ResearchFormVersion::query()
                        ->whereKey($versionId)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $this->assertImportAllowed(
                        $type,
                        $version,
                    );

                    $validator = $this->resolveValidator(
                        $type,
                        $validatorId,
                    );

                    $absolutePath = Storage::disk(
                        'local',
                    )->path($storedPath);

                    $previewOutput = $this->executeImport(
                        type: $type,
                        version: $version,
                        absolutePath: $absolutePath,
                        validator: $validator,
                        dryRun: true,
                    );

                    $hash = hash_file(
                        'sha256',
                        $absolutePath,
                    );

                    if ($hash === false) {
                        throw new RuntimeException(
                            'The uploaded CSV could not be hashed.',
                        );
                    }

                    $minutes = max(
                        5,
                        min(
                            120,
                            (int) config(
                                'citirx.research.'
                                    .'import_preview_minutes',
                                30,
                            ),
                        ),
                    );

                    return ResearchFormImport::query()
                        ->create([
                            'admin_id' => $administrator
                                ->user_id,

                            'form_version_id' => $version
                                ->form_version_id,

                            'validator_id' => $validator
                                ?->user_id,

                            'import_type' => $type,

                            'status' => ResearchFormImport::STATUS_PREVIEWED,

                            'original_filename' => $this
                                ->safeOriginalFilename(
                                    $file,
                                ),

                            'storage_path' => $storedPath,

                            'file_sha256' => $hash,

                            'file_size' => Storage::disk(
                                'local',
                            )->size($storedPath),

                            'expected_form_lock_version' => (int) $version->lock_version,

                            'lock_version' => 1,

                            'preview_output' => $previewOutput,

                            'expires_at' => now()
                                ->addMinutes($minutes),
                        ]);
                },
            );
        } catch (Throwable $exception) {
            Storage::disk('local')->delete(
                $storedPath,
            );

            throw $exception;
        }
    }

    public function commit(
        User $administrator,
        ResearchFormImport $import,
        int $expectedLockVersion,
        string $reason,
    ): ResearchFormImport {
        $this->assertAdministrator(
            $administrator,
        );

        /*
         * Becomes true only after ownership and the preview
         * lock have been verified. Unauthorized attempts must
         * never invalidate another administrator's preview.
         */
        $ownedPreviewConfirmed = false;

        try {
            $committed = DB::transaction(
                function () use (
                    $administrator,
                    $import,
                    $expectedLockVersion,
                    $reason,
                    &$ownedPreviewConfirmed,
                ): ResearchFormImport {
                    $import = ResearchFormImport::query()
                        ->whereKey($import->import_id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    if (
                        $import->admin_id
                        !== $administrator->user_id
                    ) {
                        throw new LogicException(
                            'This preview belongs to another '
                                .'administrator.',
                        );
                    }

                    if (
                        $import->status
                        !== ResearchFormImport::STATUS_PREVIEWED
                    ) {
                        throw new LogicException(
                            'This import preview is no longer '
                                .'available for committing.',
                        );
                    }

                    if (
                        $import->lock_version
                        !== $expectedLockVersion
                    ) {
                        throw new LogicException(
                            'This preview changed in another '
                                .'browser session. Refresh the page.',
                        );
                    }

                    $ownedPreviewConfirmed = true;

                    if ($import->isExpired()) {
                        throw new LogicException(
                            'This preview expired. Upload the CSV again.',
                        );
                    }

                    $this->pilotControls
                        ->assertResearchConfigurationMutable();

                    $version = ResearchFormVersion::query()
                        ->whereKey(
                            $import->form_version_id,
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                    if (
                        $version->lock_version
                        !== $import
                            ->expected_form_lock_version
                    ) {
                        throw new LogicException(
                            'The form version changed after '
                                .'the preview. Upload the CSV again.',
                        );
                    }

                    $this->assertImportAllowed(
                        $import->import_type,
                        $version,
                    );

                    $validator = $this->resolveValidator(
                        $import->import_type,
                        $import->validator_id,
                    );

                    $absolutePath = $this
                        ->assertStoredFileIntact(
                            $import,
                        );

                    $oldQuestionCount = $this
                        ->activeQuestionCount(
                            $version,
                        );

                    $oldValidatedCount = $this
                        ->validatedQuestionCount(
                            $version,
                        );

                    /*
                     * The existing command runs in this PHP process.
                     * No shell is started and no browser-supplied
                     * command or filesystem path is accepted.
                     */
                    $this->executeImport(
                        type: $import->import_type,
                        version: $version,
                        absolutePath: $absolutePath,
                        validator: $validator,
                        dryRun: false,
                    );

                    $newQuestionCount = $this
                        ->activeQuestionCount(
                            $version,
                        );

                    $newValidatedCount = $this
                        ->validatedQuestionCount(
                            $version,
                        );

                    $oldVersionLock = (int) $version->lock_version;

                    /*
                     * Importing content or approval metadata changes
                     * the form. Any older browser page must become
                     * stale before another transition is attempted.
                     */
                    $version->forceFill([
                        'lock_version' => $oldVersionLock + 1,
                    ])->save();

                    $import->forceFill([
                        'status' => ResearchFormImport::STATUS_COMMITTED,

                        'committed_at' => now(),

                        'reason' => trim($reason),

                        'failure_message' => null,

                        'lock_version' => (int) $import->lock_version + 1,
                    ])->save();

                    $this->audit->record(
                        $administrator,

                        $import->import_type
                            === ResearchFormImport::TYPE_CONTENT
                            ? 'research_form.content_imported'
                            : 'research_form.validation_imported',

                        $version,

                        [
                            'questions_count' => [
                                'old' => $oldQuestionCount,
                                'new' => $newQuestionCount,
                            ],

                            'validated_questions_count' => [
                                'old' => $oldValidatedCount,
                                'new' => $newValidatedCount,
                            ],

                            'lock_version' => [
                                'old' => $oldVersionLock,
                                'new' => $oldVersionLock + 1,
                            ],
                        ],

                        trim($reason),
                    );

                    return $import;
                },
            );
        } catch (Throwable $exception) {
            if ($ownedPreviewConfirmed) {
                $this->markFailed(
                    $import,
                );
            }

            throw $exception;
        }

        $this->purgeStoredFile(
            $committed,
        );

        return $committed->fresh([
            'version',
            'validator',
        ]);
    }

    public function cancel(
        User $administrator,
        ResearchFormImport $import,
    ): void {
        $this->assertAdministrator(
            $administrator,
        );

        $cancelled = DB::transaction(
            function () use (
                $administrator,
                $import,
            ): ResearchFormImport {
                $import = ResearchFormImport::query()
                    ->whereKey($import->import_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    $import->admin_id
                    !== $administrator->user_id
                ) {
                    throw new LogicException(
                        'This preview belongs to another '
                            .'administrator.',
                    );
                }

                if (
                    ! in_array(
                        $import->status,
                        [
                            ResearchFormImport::STATUS_PREVIEWED,

                            ResearchFormImport::STATUS_FAILED,
                        ],
                        true,
                    )
                ) {
                    throw new LogicException(
                        'This import can no longer be cancelled.',
                    );
                }

                $import->forceFill([
                    'status' => ResearchFormImport::STATUS_CANCELLED,

                    'lock_version' => (int) $import->lock_version + 1,
                ])->save();

                return $import;
            },
        );

        $this->purgeStoredFile(
            $cancelled,
        );
    }

    public function pruneExpired(): int
    {
        $ids = ResearchFormImport::query()
            ->where(
                'expires_at',
                '<=',
                now(),
            )
            ->where(
                function ($query): void {
                    $query
                        ->where(
                            'status',
                            ResearchFormImport::STATUS_PREVIEWED,
                        )
                        ->orWhereNotNull(
                            'storage_path',
                        );
                },
            )
            ->pluck('import_id');

        $pruned = 0;

        foreach ($ids as $id) {
            $import = DB::transaction(
                function () use (
                    $id,
                ): ?ResearchFormImport {
                    $import = ResearchFormImport::query()
                        ->whereKey($id)
                        ->lockForUpdate()
                        ->first();

                    if (
                        ! $import
                        || $import->expires_at->isFuture()
                    ) {
                        return null;
                    }

                    if (
                        $import->status
                        === ResearchFormImport::STATUS_PREVIEWED
                    ) {
                        $import->forceFill([
                            'status' => ResearchFormImport::STATUS_EXPIRED,

                            'lock_version' => (int) $import->lock_version + 1,
                        ])->save();
                    }

                    return $import;
                },
            );

            if (! $import) {
                continue;
            }

            $this->purgeStoredFile(
                $import,
            );

            $pruned++;
        }

        return $pruned;
    }

    private function executeImport(
        string $type,
        ResearchFormVersion $version,
        string $absolutePath,
        ?User $validator,
        bool $dryRun,
    ): string {
        $command = $type
            === ResearchFormImport::TYPE_CONTENT
            ? 'research:import-form-content'
            : 'research:import-validation';

        $arguments = [
            'file' => $absolutePath,

            '--form' => $version->research_form,

            '--form-version' => $version->version_code,
        ];

        if (
            $type
            === ResearchFormImport::TYPE_VALIDATION
        ) {
            if (! $validator) {
                throw new LogicException(
                    'A valid faculty or administrator '
                        .'reviewer is required.',
                );
            }

            $arguments['--validator-email'] =
                $validator->email;
        }

        if ($dryRun) {
            $arguments['--dry-run'] = true;
        }

        $exitCode = Artisan::call(
            $command,
            $arguments,
        );

        $output = $this->sanitizeOutput(
            Artisan::output(),
            $absolutePath,
        );

        if ($exitCode !== 0) {
            throw ValidationException::withMessages([
                'csv' => [
                    $output !== ''
                        ? $output
                        : 'The CSV failed import validation.',
                ],
            ]);
        }

        return $output !== ''
            ? $output
            : 'Dry run completed successfully.';
    }

    private function assertImportAllowed(
        string $type,
        ResearchFormVersion $version,
    ): void {
        if (
            $type
            === ResearchFormImport::TYPE_CONTENT
        ) {
            $this->targets
                ->assertContentImportAllowed(
                    $version,
                );

            return;
        }

        if (
            $type
            === ResearchFormImport::TYPE_VALIDATION
        ) {
            $this->targets
                ->assertValidationImportAllowed(
                    $version,
                );

            return;
        }

        throw new LogicException(
            'The import type is invalid.',
        );
    }

    private function resolveValidator(
        string $type,
        ?string $validatorId,
    ): ?User {
        if (
            $type
            === ResearchFormImport::TYPE_CONTENT
        ) {
            if (filled($validatorId)) {
                throw ValidationException::withMessages([
                    'validator_id' => [
                        'A validator is only used for '
                            .'validation imports.',
                    ],
                ]);
            }

            return null;
        }

        if (! filled($validatorId)) {
            throw ValidationException::withMessages([
                'validator_id' => [
                    'Select the faculty or administrator '
                        .'who approved this file.',
                ],
            ]);
        }

        $validator = User::query()
            ->whereKey($validatorId)
            ->whereIn(
                'role',
                [
                    'faculty',
                    'admin',
                ],
            )
            ->whereNotNull(
                'email_verified_at',
            )
            ->first();

        if (! $validator) {
            throw ValidationException::withMessages([
                'validator_id' => [
                    'The reviewer must be an active, '
                        .'verified faculty or administrator.',
                ],
            ]);
        }

        return $validator;
    }

    private function storeUpload(
        User $administrator,
        UploadedFile $file,
    ): string {
        $directory = 'research-form-imports/'
            .$administrator->user_id;

        $path = $file->storeAs(
            $directory,
            Str::uuid().'.csv',
            'local',
        );

        if (! is_string($path)) {
            throw new RuntimeException(
                'The CSV could not be stored securely.',
            );
        }

        return $path;
    }

    private function assertStoredFileIntact(
        ResearchFormImport $import,
    ): string {
        $path = $import->storage_path;

        if (
            ! filled($path)
            || ! Storage::disk('local')->exists($path)
        ) {
            throw new LogicException(
                'The private uploaded file is missing. '
                    .'Upload the CSV again.',
            );
        }

        $absolutePath = Storage::disk(
            'local',
        )->path($path);

        $hash = hash_file(
            'sha256',
            $absolutePath,
        );

        if (
            $hash === false
            || ! hash_equals(
                $import->file_sha256,
                $hash,
            )
            || Storage::disk('local')->size($path)
                !== $import->file_size
        ) {
            throw new LogicException(
                'The uploaded CSV changed after preview. '
                    .'Upload and preview it again.',
            );
        }

        return $absolutePath;
    }

    private function markFailed(
        ResearchFormImport $import,
    ): void {
        $failed = DB::transaction(
            function () use (
                $import,
            ): ?ResearchFormImport {
                $locked = ResearchFormImport::query()
                    ->whereKey($import->import_id)
                    ->lockForUpdate()
                    ->first();

                if (
                    ! $locked
                    || $locked->status
                        !== ResearchFormImport::STATUS_PREVIEWED
                ) {
                    return $locked;
                }

                /*
                 * Do not persist database exceptions, paths,
                 * credentials, or CSV contents.
                 */
                $locked->forceFill([
                    'status' => ResearchFormImport::STATUS_FAILED,

                    'failure_message' => 'Commit failed. Upload and preview '
                        .'the CSV again.',

                    'lock_version' => (int) $locked->lock_version + 1,
                ])->save();

                return $locked;
            },
        );

        if ($failed) {
            $this->purgeStoredFile(
                $failed,
            );
        }
    }

    private function purgeStoredFile(
        ResearchFormImport $import,
    ): void {
        $path = $import->storage_path;

        if (! filled($path)) {
            return;
        }

        $disk = Storage::disk('local');

        $removed = ! $disk->exists($path)
            || $disk->delete($path);

        if (! $removed) {
            /*
             * Keep the private path in the database so the
             * scheduled cleanup can retry later.
             */
            return;
        }

        ResearchFormImport::query()
            ->whereKey($import->import_id)
            ->where('storage_path', $path)
            ->update([
                'storage_path' => null,
            ]);

        $import->storage_path = null;
    }

    private function activeQuestionCount(
        ResearchFormVersion $version,
    ): int {
        return Question::query()
            ->where(
                'form_version_id',
                $version->form_version_id,
            )
            ->where('is_active', true)
            ->count();
    }

    private function validatedQuestionCount(
        ResearchFormVersion $version,
    ): int {
        return Question::query()
            ->where(
                'form_version_id',
                $version->form_version_id,
            )
            ->where('is_active', true)
            ->whereNotNull(
                'research_validated_at',
            )
            ->whereNotNull(
                'research_validated_by',
            )
            ->count();
    }

    private function sanitizeOutput(
        string $output,
        string $absolutePath,
    ): string {
        $output = str_ireplace(
            [
                $absolutePath,
                str_replace(
                    '\\',
                    '/',
                    $absolutePath,
                ),
            ],
            '[private CSV]',
            $output,
        );

        $output = preg_replace(
            '/\e\[[\d;]*[A-Za-z]/',
            '',
            $output,
        ) ?? $output;

        return mb_substr(
            trim($output),
            0,
            12000,
        );
    }

    private function safeOriginalFilename(
        UploadedFile $file,
    ): string {
        $name = basename(
            $file->getClientOriginalName(),
        );

        $name = preg_replace(
            '/[\x00-\x1F\x7F]/u',
            '',
            $name,
        ) ?? '';

        return mb_substr(
            $name !== ''
                ? $name
                : 'research-form.csv',
            0,
            255,
        );
    }

    private function assertAdministrator(
        User $administrator,
    ): void {
        if ($administrator->role !== 'admin') {
            throw new LogicException(
                'Only administrators may import '
                    .'research-form files.',
            );
        }
    }
}
