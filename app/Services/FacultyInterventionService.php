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
    public function __construct(protected AdminAuditService $audit) {}

    public function assign(
        User $actor,
        array $payload,
        ?string $facultyId = null,
    ): FacultyIntervention {
        $isDean = $actor->role === 'dean';

        if (! $isDean) {
            $this->assertFacultyWithCohort($actor);
        }

        return DB::transaction(
            function () use ($actor, $payload, $facultyId, $isDean): FacultyIntervention {
                $faculty = $actor;
                if ($isDean) {
                    $faculty = User::query()
                        ->where('role', 'faculty')
                        ->whereNotNull('cohort_id')
                        ->lockForUpdate()
                        ->find($facultyId);

                    if ($faculty === null) {
                        throw ValidationException::withMessages([
                            'faculty_id' => 'Choose an available faculty member.',
                        ]);
                    }
                }
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
                        'target_user_id' => $isDean
                            ? 'The student must be in the same cohort as the selected faculty member.'
                            : 'The selected student is not available in your cohort.',
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
                        ->whereHas('targetUser', fn ($query) => $query->where('cohort_id', $faculty->cohort_id)->where('role', 'student')->whereNull('deleted_at'))
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

                $intervention = FacultyIntervention::query()
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

                if ($isDean) {
                    $this->audit->record($actor, 'intervention.assigned', $intervention, [
                        'faculty_id' => ['old' => null, 'new' => $faculty->user_id],
                    ]);
                }

                return $intervention;
            },
        );
    }

    public function complete(
        User $actor,
        string $interventionId,
    ): bool {
        $isDean = $actor->role === 'dean';

        if (! $isDean) {
            $this->assertFacultyWithCohort($actor);
        }

        return DB::transaction(
            function () use ($actor, $interventionId, $isDean): bool {
                /*
                 * Ownership-scoped lookup returns 404 for
                 * another faculty member's record instead
                 * of revealing that it exists.
                 */
                $intervention =
                    FacultyIntervention::query()
                        ->whereKey($interventionId)
                        ->when(! $isDean, fn ($query) => $query
                            ->where('faculty_id', $actor->user_id)
                            ->whereHas('targetUser', fn ($query) => $query
                                ->where('cohort_id', $actor->cohort_id)
                                ->where('role', 'student')
                                ->whereNull('deleted_at')))
                        ->lockForUpdate()
                        ->firstOrFail();

                if ($intervention->is_completed) {
                    return false;
                }

                $intervention->forceFill([
                    'is_completed' => true,
                    'completed_at' => now(),
                ])->save();

                if ($isDean) {
                    $this->audit->record($actor, 'intervention.completed', $intervention, [
                        'is_completed' => ['old' => false, 'new' => true],
                    ]);
                }

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
