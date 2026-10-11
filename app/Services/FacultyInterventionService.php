<?php

namespace App\Services;

use App\Models\FacultyIntervention;
use App\Models\TosCompetency;
use App\Models\TosDomain;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FacultyInterventionService
{
    public function assign(
        User $faculty,
        array $payload,
    ): FacultyIntervention {
        $this->assertFacultyWithCohort(
            $faculty,
        );

        return DB::transaction(
            function () use (
                $faculty,
                $payload,
            ): FacultyIntervention {
                /*
                 * Locking the target serializes repeated
                 * double-click submissions for this student.
                 */
                $target = User::query()
                    ->whereKey(
                        $payload['target_user_id'],
                    )
                    ->where(
                        'role',
                        'student',
                    )
                    ->where(
                        'cohort_id',
                        $faculty->cohort_id,
                    )
                    ->whereNull(
                        'deleted_at',
                    )
                    ->lockForUpdate()
                    ->first();

                if ($target === null) {
                    throw ValidationException::withMessages([
                        'target_user_id' => 'The selected student is not available in your cohort.',
                    ]);
                }

                $domainExists =
                    TosDomain::query()
                        ->whereKey(
                            $payload['domain_id'],
                        )
                        ->exists();

                if (! $domainExists) {
                    throw ValidationException::withMessages([
                        'domain_id' => 'The selected domain is invalid.',
                    ]);
                }

                $competencyId =
                    $payload['competency_id']
                        ?? null;

                if (
                    $competencyId !== null
                    && ! TosCompetency::query()
                        ->whereKey(
                            $competencyId,
                        )
                        ->where(
                            'domain_id',
                            $payload['domain_id'],
                        )
                        ->exists()
                ) {
                    throw ValidationException::withMessages([
                        'competency_id' => 'The selected competency does not belong to the selected domain.',
                    ]);
                }

                $duplicateQuery =
                    FacultyIntervention::query()
                        ->where(
                            'faculty_id',
                            $faculty->user_id,
                        )
                        ->where(
                            'target_user_id',
                            $target->user_id,
                        )
                        ->where(
                            'domain_id',
                            $payload['domain_id'],
                        )
                        ->where(
                            'intervention_type',
                            $payload[
                                'intervention_type'
                            ],
                        )
                        ->where(
                            'is_completed',
                            false,
                        );

                if ($competencyId === null) {
                    $duplicateQuery->whereNull(
                        'competency_id',
                    );
                } else {
                    $duplicateQuery->where(
                        'competency_id',
                        $competencyId,
                    );
                }

                if ($duplicateQuery->exists()) {
                    throw ValidationException::withMessages([
                        'target_user_id' => 'A matching open intervention already exists for this student.',
                    ]);
                }

                return FacultyIntervention::query()
                    ->create([
                        'faculty_id' => $faculty->user_id,

                        'target_user_id' => $target->user_id,

                        'domain_id' => $payload['domain_id'],

                        'competency_id' => $competencyId,

                        'intervention_type' => $payload[
                                'intervention_type'
                            ],

                        'custom_message' => $payload[
                                'custom_message'
                            ] ?? null,

                        'is_completed' => false,

                        'assigned_at' => now(),
                    ]);
            },
        );
    }

    public function complete(
        User $faculty,
        string $interventionId,
    ): bool {
        if ($faculty->role !== 'faculty') {
            throw new AuthorizationException(
                'Faculty access only.',
            );
        }

        return DB::transaction(
            function () use (
                $faculty,
                $interventionId,
            ): bool {
                /*
                 * Ownership-scoped lookup returns 404 for
                 * another faculty member's record instead
                 * of revealing that it exists.
                 */
                $intervention =
                    FacultyIntervention::query()
                        ->whereKey(
                            $interventionId,
                        )
                        ->where(
                            'faculty_id',
                            $faculty->user_id,
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                if ($intervention->is_completed) {
                    return false;
                }

                $intervention->forceFill([
                    'is_completed' => true,
                    'completed_at' => now(),
                ])->save();

                return true;
            },
        );
    }

    private function assertFacultyWithCohort(
        User $faculty,
    ): void {
        if ($faculty->role !== 'faculty') {
            throw new AuthorizationException(
                'Faculty access only.',
            );
        }

        if ($faculty->cohort_id === null) {
            throw ValidationException::withMessages([
                'target_user_id' => 'Your faculty account is not assigned to a cohort.',
            ]);
        }
    }
}
