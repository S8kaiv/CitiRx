<?php

namespace App\Services;

use App\Models\AssessmentSession;
use App\Models\Cohort;
use App\Models\CohortAssignmentImport;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;
use RuntimeException;

class CohortAssignmentImportService
{
    private const HEADER = [
        'email',
        'student_id',
        'role',
        'cohort_name',
        'academic_year',
    ];

    private const MAX_ROWS = 500;

    public function __construct(
        protected AdminAuditService $audit,
    ) {}

    public function createPreview(
        User $administrator,
        UploadedFile $file,
    ): CohortAssignmentImport {
        $this->assertAdministrator(
            $administrator,
        );

        $rawRows = $this->readCsv($file);

        $emails = collect($rawRows)
            ->pluck('email')
            ->map(
                fn (string $email): string => mb_strtolower(
                    trim($email),
                ),
            )
            ->unique()
            ->values();

        $users = User::withTrashed()
            ->whereIn('email', $emails)
            ->get()
            ->keyBy(
                fn (User $user): string => mb_strtolower(
                    $user->email,
                ),
            );

        $cohorts = Cohort::query()
            ->get()
            ->keyBy(
                fn (Cohort $cohort): string => $this->cohortKey(
                    $cohort->cohort_name,
                    $cohort->academic_year,
                ),
            );

        $errors = [];
        $seenUsers = [];
        $assignments = [];
        $changed = 0;
        $unchanged = 0;

        foreach ($rawRows as $index => $row) {
            $line = $index + 2;

            $email = mb_strtolower(
                trim($row['email']),
            );

            $role = mb_strtolower(
                trim($row['role']),
            );

            if (
                ! filter_var(
                    $email,
                    FILTER_VALIDATE_EMAIL,
                )
            ) {
                $errors[] = "CSV line {$line} has an invalid email.";

                continue;
            }

            if (
                ! in_array(
                    $role,
                    ['student', 'faculty'],
                    true,
                )
            ) {
                $errors[] = "CSV line {$line} must use student or faculty.";

                continue;
            }

            $user = $users->get($email);

            if (! $user) {
                $errors[] = "CSV line {$line} does not match an existing user.";

                continue;
            }

            if ($user->trashed()) {
                $errors[] = "CSV line {$line} refers to a deleted account.";

                continue;
            }

            if (isset($seenUsers[$user->user_id])) {
                $errors[] = "CSV line {$line} duplicates an earlier user.";

                continue;
            }

            $seenUsers[$user->user_id] = true;

            if ($user->role !== $role) {
                $errors[] = "CSV line {$line} does not match the user's current role.";

                continue;
            }

            $studentId = trim(
                $row['student_id'],
            );

            if (
                $role === 'student'
                && (
                    $studentId === ''
                    || $studentId !== (string) $user->student_id)
            ) {
                $errors[] = "CSV line {$line} has an incorrect student ID.";

                continue;
            }

            if (
                $role === 'faculty'
                && $studentId !== ''
                && $studentId !== (string) $user->student_id
            ) {
                $errors[] = "CSV line {$line} has an incorrect faculty identifier.";

                continue;
            }

            $key = $this->cohortKey(
                $row['cohort_name'],
                $row['academic_year'],
            );

            $cohort = $cohorts->get($key);

            if (! $cohort) {
                $errors[] = "CSV line {$line} does not match an existing cohort.";

                continue;
            }

            if ($cohort->archived_at !== null) {
                $errors[] = "CSV line {$line} targets an archived cohort.";

                continue;
            }

            $isChanged = $user->cohort_id
                !== $cohort->cohort_id;

            $isChanged
                ? $changed++
                : $unchanged++;

            /*
             * Raw email, name, and student ID are intentionally
             * not stored in the preview record.
             */
            $assignments[] = [
                'user_id' => $user->user_id,
                'role' => $user->role,
                'old_cohort_id' => $user->cohort_id,
                'new_cohort_id' => $cohort->cohort_id,
            ];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages([
                'csv' => $errors,
            ]);
        }

        if ($assignments === []) {
            throw ValidationException::withMessages([
                'csv' => [
                    'The CSV does not contain any assignment rows.',
                ],
            ]);
        }

        return CohortAssignmentImport::query()->create([
            'admin_id' => $administrator->user_id,
            'original_filename' => basename(
                $file->getClientOriginalName(),
            ),
            'file_sha256' => hash_file(
                'sha256',
                $file->getRealPath(),
            ),
            'assignment_rows' => $assignments,
            'summary' => [
                'total' => count($assignments),
                'changed' => $changed,
                'unchanged' => $unchanged,
            ],
            'lock_version' => 1,
            'expires_at' => now()->addMinutes(30),
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function displayRows(
        CohortAssignmentImport $import,
    ): Collection {
        $rows = collect(
            $import->assignment_rows,
        );

        $users = User::withTrashed()
            ->with('cohort')
            ->whereIn(
                'user_id',
                $rows->pluck('user_id'),
            )
            ->get()
            ->keyBy('user_id');

        $cohorts = Cohort::query()
            ->whereIn(
                'cohort_id',
                $rows->pluck('new_cohort_id'),
            )
            ->get()
            ->keyBy('cohort_id');

        return $rows->map(
            function (
                array $row,
            ) use (
                $users,
                $cohorts,
            ): array {
                return [
                    'user' => $users->get(
                        $row['user_id'],
                    ),
                    'target_cohort' => $cohorts->get(
                        $row['new_cohort_id'],
                    ),
                    'changed' => $row['old_cohort_id']
                        !== $row['new_cohort_id'],
                ];
            },
        );
    }

    /**
     * @return array{changed: int, unchanged: int}
     */
    public function commit(
        User $administrator,
        CohortAssignmentImport $import,
        int $expectedVersion,
        string $reason,
    ): array {
        $this->assertAdministrator(
            $administrator,
        );

        return DB::transaction(function () use (
            $administrator,
            $import,
            $expectedVersion,
            $reason,
        ): array {
            $import = CohortAssignmentImport::query()
                ->lockForUpdate()
                ->findOrFail($import->import_id);

            if (
                $import->admin_id
                !== $administrator->user_id
            ) {
                throw new LogicException(
                    'This preview belongs to another administrator.',
                );
            }

            if ($import->committed_at !== null) {
                throw new LogicException(
                    'This preview has already been committed.',
                );
            }

            if ($import->expires_at->isPast()) {
                throw new LogicException(
                    'This preview expired. Upload the CSV again.',
                );
            }

            if (
                $import->lock_version
                !== $expectedVersion
            ) {
                throw new LogicException(
                    'This preview is stale. Reload the page.',
                );
            }

            $rows = collect(
                $import->assignment_rows,
            );

            $userIds = $rows
                ->pluck('user_id')
                ->sort()
                ->values();

            $users = User::withTrashed()
                ->whereIn('user_id', $userIds)
                ->orderBy('user_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('user_id');

            $targetCohortIds = $rows
                ->pluck('new_cohort_id')
                ->unique()
                ->values();

            $activeCohorts = Cohort::query()
                ->active()
                ->whereIn(
                    'cohort_id',
                    $targetCohortIds,
                )
                ->lockForUpdate()
                ->get()
                ->keyBy('cohort_id');

            if (
                $activeCohorts->count()
                !== $targetCohortIds->count()
            ) {
                throw new LogicException(
                    'A target cohort was archived or removed after preview.',
                );
            }

            $changingUserIds = $rows
                ->filter(
                    fn (array $row): bool => $row['old_cohort_id']
                        !== $row['new_cohort_id'],
                )
                ->pluck('user_id');

            $researchLockedUserIds = AssessmentSession::query()
                ->whereIn('user_id', $changingUserIds)
                ->whereIn(
                    'research_phase',
                    [
                        'pre_test',
                        'post_test',
                    ],
                )
                ->distinct()
                ->pluck('user_id')
                ->flip();

            $changed = 0;
            $unchanged = 0;

            foreach ($rows as $row) {
                /** @var User|null $user */
                $user = $users->get(
                    $row['user_id'],
                );

                if (! $user || $user->trashed()) {
                    throw new LogicException(
                        'A user became unavailable after preview.',
                    );
                }

                if ($user->role !== $row['role']) {
                    throw new LogicException(
                        'A user role changed after preview.',
                    );
                }

                if (
                    $user->cohort_id
                    !== $row['old_cohort_id']
                ) {
                    throw new LogicException(
                        'A user cohort changed after preview. Upload the CSV again.',
                    );
                }

                if (
                    $user->cohort_id
                    === $row['new_cohort_id']
                ) {
                    $unchanged++;

                    continue;
                }

                if (
                    $researchLockedUserIds->has(
                        $user->user_id,
                    )
                ) {
                    throw new LogicException(
                        'A cohort cannot be changed after an official research assessment has started.',
                    );
                }

                $oldCohortId = $user->cohort_id;

                $user->forceFill([
                    'cohort_id' => $row['new_cohort_id'],
                ])->save();

                /*
                 * Do not record email, name, student ID, password,
                 * or raw CSV contents in the audit log.
                 */
                $this->audit->record(
                    $administrator,
                    'user.bulk_cohort_assigned',
                    $user,
                    [
                        'cohort_id' => [
                            'old' => $oldCohortId,
                            'new' => $row['new_cohort_id'],
                        ],
                    ],
                    $reason,
                );

                $changed++;
            }

            $import->forceFill([
                'committed_at' => now(),
                'lock_version' => (int) $import->lock_version + 1,
            ])->save();

            return [
                'changed' => $changed,
                'unchanged' => $unchanged,
            ];
        });
    }

    public function cancel(
        User $administrator,
        CohortAssignmentImport $import,
    ): void {
        $this->assertAdministrator(
            $administrator,
        );

        DB::transaction(function () use (
            $administrator,
            $import,
        ): void {
            $import = CohortAssignmentImport::query()
                ->lockForUpdate()
                ->findOrFail($import->import_id);

            if (
                $import->admin_id
                !== $administrator->user_id
            ) {
                throw new LogicException(
                    'This preview belongs to another administrator.',
                );
            }

            if ($import->committed_at !== null) {
                throw new LogicException(
                    'A committed import cannot be cancelled.',
                );
            }

            $import->delete();
        });
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function readCsv(
        UploadedFile $file,
    ): array {
        $path = $file->getRealPath();

        if ($path === false) {
            throw new RuntimeException(
                'The uploaded CSV could not be read.',
            );
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException(
                'The uploaded CSV could not be opened.',
            );
        }

        try {
            $header = fgetcsv(
                $handle,
                null,
                ',',
                '"',
                '',
            );

            if (! is_array($header)) {
                throw ValidationException::withMessages([
                    'csv' => [
                        'The CSV header is missing.',
                    ],
                ]);
            }

            $header = array_map(
                fn (mixed $value): string => mb_strtolower(
                    trim(
                        preg_replace(
                            '/^\xEF\xBB\xBF/',
                            '',
                            (string) $value,
                        ) ?? (string) $value,
                    ),
                ),
                $header,
            );

            if ($header !== self::HEADER) {
                throw ValidationException::withMessages([
                    'csv' => [
                        'The CSV header must be: '
                            .implode(',', self::HEADER),
                    ],
                ]);
            }

            $rows = [];

            while (
                (
                    $columns = fgetcsv(
                        $handle,
                        null,
                        ',',
                        '"',
                        '',
                    )
                ) !== false
            ) {
                if (
                    collect($columns)
                        ->every(
                            fn ($value): bool => trim(
                                (string) $value,
                            ) === '',
                        )
                ) {
                    continue;
                }

                if (
                    count($columns)
                    !== count(self::HEADER)
                ) {
                    throw ValidationException::withMessages([
                        'csv' => [
                            'A CSV row has an incorrect number of columns.',
                        ],
                    ]);
                }

                $row = array_combine(
                    self::HEADER,
                    array_map(
                        fn ($value): string => trim(
                            (string) $value,
                        ),
                        $columns,
                    ),
                );

                if ($row === false) {
                    throw new RuntimeException(
                        'A CSV row could not be parsed.',
                    );
                }

                $rows[] = $row;

                if (
                    count($rows)
                    > self::MAX_ROWS
                ) {
                    throw ValidationException::withMessages([
                        'csv' => [
                            'A maximum of '
                                .self::MAX_ROWS
                                .' rows is allowed per import.',
                        ],
                    ]);
                }
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }

    private function cohortKey(
        string $name,
        string $academicYear,
    ): string {
        return mb_strtolower(trim($name))
            .'|'
            .mb_strtolower(trim($academicYear));
    }

    private function assertAdministrator(
        User $administrator,
    ): void {
        if ($administrator->role !== 'admin') {
            throw new LogicException(
                'Only administrators may import cohort assignments.',
            );
        }
    }
}
