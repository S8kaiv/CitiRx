<?php

namespace App\Services;

use App\Models\Cohort;
use App\Models\Question;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

class AdminUserService
{
    public function __construct(
        protected AdminAuditService $audit,
    ) {}

    /**
     * @param  array{role: string, cohort_id?: string|null}  $data
     */
    public function updateAssignment(
        User $administrator,
        string $userId,
        array $data,
        ?string $reason,
    ): bool {
        return DB::transaction(function () use (
            $administrator,
            $userId,
            $data,
            $reason,
        ): bool {
            $adminIds = User::query()
                ->where('role', 'admin')
                ->lockForUpdate()
                ->pluck('user_id');

            $user = User::withTrashed()
                ->lockForUpdate()
                ->findOrFail($userId);

            if ($user->trashed()) {
                throw new LogicException(
                    'Restore this account before changing its assignment.',
                );
            }

            $newRole = $data['role'];

            $newCohortId = in_array($newRole, ['admin', 'dean'], true)
                ? null
                : ($data['cohort_id'] ?? null);

            if ($newCohortId !== null) {
                $cohortExists = Cohort::query()
                    ->active()
                    ->where(
                        'cohort_id',
                        $newCohortId,
                    )
                    ->exists();

                if (! $cohortExists) {
                    throw new LogicException(
                        'The selected cohort is archived or unavailable.',
                    );
                }
            }

            if (
                $administrator->is($user)
                && $newRole !== $user->role
            ) {
                throw new LogicException(
                    'You cannot change your own administrator role.',
                );
            }

            if ($user->role !== $newRole) {
                if (
                    $user->role === 'admin'
                    && $adminIds->count() <= 1
                ) {
                    throw new LogicException(
                        'The final administrator cannot be demoted.',
                    );
                }

                $this->assertRoleCanChange($user);
            }

            if ($user->cohort_id !== $newCohortId) {
                $this->assertCohortCanChange($user);
            }

            $changes = [];

            if ($user->role !== $newRole) {
                $changes['role'] = [
                    'old' => $user->role,
                    'new' => $newRole,
                ];
            }

            if ($user->cohort_id !== $newCohortId) {
                $changes['cohort_id'] = [
                    'old' => $user->cohort_id,
                    'new' => $newCohortId,
                ];
            }

            if ($changes === []) {
                return false;
            }

            $user->forceFill([
                'role' => $newRole,
                'cohort_id' => $newCohortId,
            ])->save();

            $this->audit->record(
                $administrator,
                'user.assignment_updated',
                $user,
                $changes,
                $reason,
            );

            return true;
        });
    }

    public function softDelete(
        User $administrator,
        string $userId,
        string $reason,
    ): void {
        DB::transaction(function () use (
            $administrator,
            $userId,
            $reason,
        ): void {
            $adminIds = User::query()
                ->where('role', 'admin')
                ->lockForUpdate()
                ->pluck('user_id');

            $user = User::query()
                ->lockForUpdate()
                ->findOrFail($userId);

            if ($administrator->is($user)) {
                throw new LogicException(
                    'You cannot delete your own account.',
                );
            }

            if (
                $user->role === 'admin'
                && $adminIds->count() <= 1
            ) {
                throw new LogicException(
                    'The final administrator cannot be deleted.',
                );
            }

            $user->delete();

            $this->audit->record(
                $administrator,
                'user.soft_deleted',
                $user,
                [],
                $reason,
            );
        });
    }

    public function assignCohort(User $actor, array $ids, string $cohortId, array $originalCohorts): int
    {
        abort_unless($actor->role === 'admin', 403);

        return DB::transaction(function () use ($actor, $ids, $cohortId, $originalCohorts): int {
            $cohort = Cohort::query()->active()->lockForUpdate()->find($cohortId);
            if (! $cohort) {
                throw ValidationException::withMessages(['cohort_id' => 'Choose an active cohort.']);
            }
            $students = User::query()->whereIn('user_id', $ids)->orderBy('user_id')->lockForUpdate()->get();
            if ($students->count() !== count($ids) || $students->contains(fn ($student) => $student->role !== 'student')) {
                throw ValidationException::withMessages(['student_ids' => 'Only active student accounts can be assigned. Refresh your selection.']);
            }
            $count = 0;
            foreach ($students as $student) {
                if (! array_key_exists($student->user_id, $originalCohorts)
                    || ($originalCohorts[$student->user_id] ?: null) !== $student->cohort_id) {
                    throw ValidationException::withMessages(['student_ids' => 'A selected cohort assignment changed. Reload and select again.']);
                }
                if ($student->cohort_id === $cohortId) {
                    continue;
                }
                try {
                    $this->assertCohortCanChange($student);
                } catch (LogicException $exception) {
                    throw ValidationException::withMessages(['student_ids' => $exception->getMessage()]);
                }
                $old = $student->cohort_id;
                $student->forceFill(['cohort_id' => $cohortId])->save();
                $this->audit->record($actor, 'user.bulk_cohort_assigned', $student, ['cohort_id' => ['old' => $old, 'new' => $cohortId]]);
                $count++;
            }

            return $count;
        }, attempts: 3);
    }

    public function restore(
        User $administrator,
        string $userId,
        ?string $reason,
    ): void {
        DB::transaction(function () use (
            $administrator,
            $userId,
            $reason,
        ): void {
            $user = User::onlyTrashed()
                ->lockForUpdate()
                ->findOrFail($userId);

            $user->restore();

            $this->audit->record(
                $administrator,
                'user.restored',
                $user,
                [],
                $reason,
            );
        });
    }

    private function assertCohortCanChange(
        User $user,
    ): void {
        $hasResearchAttempt = $user
            ->assessmentSessions()
            ->whereIn(
                'research_phase',
                [
                    'pre_test',
                    'post_test',
                ],
            )
            ->exists();

        if ($hasResearchAttempt) {
            throw new LogicException(
                'The cohort cannot be changed after an official research assessment has started.',
            );
        }
    }

    private function assertRoleCanChange(
        User $user,
    ): void {
        $hasStudentHistory = $user->role === 'student'
            && (
                $user->assessmentSessions()->exists()
                || $user->knowledgeStates()->exists()
                || $user->xpTransactions()->exists()
            );

        $hasFacultyHistory = $user->role === 'faculty'
            && $user
                ->facultyInterventionsAssigned()
                ->exists();

        $hasDeanApproval = $user->role === 'dean'
            && Question::query()
                ->where('research_validated_by', $user->user_id)
                ->exists();

        if ($hasStudentHistory || $hasFacultyHistory || $hasDeanApproval) {
            throw new LogicException(
                'This role cannot be changed because role-specific history already exists.',
            );
        }
    }
}
