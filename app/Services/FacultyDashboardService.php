<?php

namespace App\Services;

use App\Models\Cohort;
use App\Models\FacultyIntervention;
use App\Models\TosDomain;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FacultyDashboardService
{
    public function __construct(
        protected ReadinessService $readiness,
    ) {}

    public function forFaculty(User $faculty): array
    {
        $cohort = $this->cohortFor($faculty);

        if ($cohort === null) {
            return [
                'cohort' => null,
                'summary' => null,
                'readinessBands' => null,
                'domainMastery' => collect(),
                'supportStudents' => collect(),
                'interventionCounts' => null,
                'students' => null,
                'targets' => collect(),
                'formOptions' => collect(),
                'interventionTypes' => FacultyIntervention::TYPE_LABELS,
            ];
        }

        $metrics = $this->cohortMetrics(
            $cohort,
        );

        return [
            'cohort' => $cohort,

            'summary' => [
                'total_students' => $metrics['total_students'],

                'diagnostic_completed' => $metrics['diagnostic_completed'],

                'diagnostic_completion_pct' => $metrics['diagnostic_completion_pct'],

                'avg_readiness' => $metrics['avg_readiness'],

                'pending_readiness' => $metrics['pending_readiness'],
            ],

            'readinessBands' => [
                'board_ready' => $metrics['board_ready'],

                'approaching' => $metrics['approaching'],

                'developing' => $metrics['developing'],

                'at_risk' => $metrics['at_risk'],

                'no_data' => $metrics['pending_readiness'],
            ],

            'domainMastery' => $this->domainMastery($cohort),

            'supportStudents' => $this->studentsNeedingSupport($cohort),

            'interventionCounts' => $this->interventionCounts($faculty),

            'students' => $this->paginatedStudents($cohort),

            'targets' => $this->interventionTargets($cohort),

            'formOptions' => $this->formOptions(),

            'interventionTypes' => FacultyIntervention::TYPE_LABELS,
        ];
    }

    public function cohortFor(
        User $faculty,
    ): ?Cohort {
        if (
            $faculty->role !== 'faculty'
            || $faculty->cohort_id === null
        ) {
            return null;
        }

        return Cohort::query()->find(
            $faculty->cohort_id,
        );
    }

    public function paginatedInterventions(
        User $faculty,
        int $perPage = 15,
    ): LengthAwarePaginator {
        return FacultyIntervention::query()
            ->where(
                'faculty_id',
                $faculty->user_id,
            )
            ->with([
                'targetUser:user_id,first_name,last_name,student_id,deleted_at',

                'domain:domain_id,domain_number,domain_name',

                'competency:competency_id,competency_code,title',
            ])
            ->orderByDesc(
                'assigned_at',
            )
            ->paginate(
                $perPage,
                ['*'],
                'interventions_page',
            );
    }

    public function formOptions(): Collection
    {
        return TosDomain::query()
            ->with([
                'competencies' => fn ($query) => $query
                    ->orderBy('order_index')
                    ->select([
                        'competency_id',
                        'domain_id',
                        'competency_code',
                        'title',
                    ]),
            ])
            ->orderBy(
                'domain_number',
            )
            ->get([
                'domain_id',
                'domain_number',
                'domain_name',
            ]);
    }

    private function cohortMetrics(
        Cohort $cohort,
    ): array {
        $thresholds =
            ReadinessService::BAND_THRESHOLDS;

        $row = DB::table('users')
            ->where(
                'cohort_id',
                $cohort->cohort_id,
            )
            ->where(
                'role',
                'student',
            )
            ->whereNull(
                'deleted_at',
            )
            ->selectRaw(
                'COUNT(*) AS total_students',
            )
            ->selectRaw(
                'SUM(CASE '
                    .'WHEN is_diagnostic_completed = 1 '
                    .'THEN 1 ELSE 0 END) '
                    .'AS diagnostic_completed',
            )
            ->selectRaw(
                'AVG(CASE '
                    .'WHEN is_diagnostic_completed = 1 '
                    .'THEN predicted_readiness_pct '
                    .'ELSE NULL END) '
                    .'AS avg_readiness',
            )
            ->selectRaw(
                'SUM(CASE '
                    .'WHEN is_diagnostic_completed = 1 '
                    .'AND predicted_readiness_pct >= ? '
                    .'THEN 1 ELSE 0 END) '
                    .'AS board_ready',
                [
                    $thresholds['board_ready'],
                ],
            )
            ->selectRaw(
                'SUM(CASE '
                    .'WHEN is_diagnostic_completed = 1 '
                    .'AND predicted_readiness_pct >= ? '
                    .'AND predicted_readiness_pct < ? '
                    .'THEN 1 ELSE 0 END) '
                    .'AS approaching',
                [
                    $thresholds['approaching'],
                    $thresholds['board_ready'],
                ],
            )
            ->selectRaw(
                'SUM(CASE '
                    .'WHEN is_diagnostic_completed = 1 '
                    .'AND predicted_readiness_pct >= ? '
                    .'AND predicted_readiness_pct < ? '
                    .'THEN 1 ELSE 0 END) '
                    .'AS developing',
                [
                    $thresholds['developing'],
                    $thresholds['approaching'],
                ],
            )
            ->selectRaw(
                'SUM(CASE '
                    .'WHEN is_diagnostic_completed = 1 '
                    .'AND predicted_readiness_pct < ? '
                    .'THEN 1 ELSE 0 END) '
                    .'AS at_risk',
                [
                    $thresholds['developing'],
                ],
            )
            ->first();

        $total = (int)
            ($row->total_students ?? 0);

        $completed = (int)
            ($row->diagnostic_completed ?? 0);

        $boardReady = (int)
            ($row->board_ready ?? 0);

        $approaching = (int)
            ($row->approaching ?? 0);

        $developing = (int)
            ($row->developing ?? 0);

        $atRisk = (int)
            ($row->at_risk ?? 0);

        $withReadiness =
            $boardReady
            + $approaching
            + $developing
            + $atRisk;

        return [
            'total_students' => $total,

            'diagnostic_completed' => $completed,

            'diagnostic_completion_pct' => $total > 0
                    ? round(
                        ($completed / $total) * 100,
                        1,
                    )
                    : 0.0,

            'avg_readiness' => $row->avg_readiness !== null
                    ? round(
                        (float) $row->avg_readiness,
                        2,
                    )
                    : null,

            'pending_readiness' => max(
                0,
                $total - $withReadiness,
            ),

            'board_ready' => $boardReady,
            'approaching' => $approaching,
            'developing' => $developing,
            'at_risk' => $atRisk,
        ];
    }

    private function domainMastery(
        Cohort $cohort,
    ): Collection {
        $masteryQuery =
            DB::table(
                'user_knowledge_states as uks',
            )
                ->join(
                    'users as u',
                    'u.user_id',
                    '=',
                    'uks.user_id',
                )
                ->join(
                    'tos_competencies as c',
                    'c.competency_id',
                    '=',
                    'uks.competency_id',
                )
                ->where(
                    'u.cohort_id',
                    $cohort->cohort_id,
                )
                ->where(
                    'u.role',
                    'student',
                )
                ->where(
                    'u.is_diagnostic_completed',
                    true,
                )
                ->whereNull(
                    'u.deleted_at',
                )
                ->groupBy(
                    'c.domain_id',
                )
                ->selectRaw(
                    'c.domain_id',
                )
                ->selectRaw(
                    'SUM('
                        .'uks.current_mastery_p_l '
                        .'* c.tos_weight_percentage'
                        .') '
                        .'/ NULLIF('
                        .'SUM(c.tos_weight_percentage), '
                        .'0'
                        .') AS avg_mastery',
                )
                ->selectRaw(
                    'COUNT(DISTINCT u.user_id) '
                        .'AS student_count',
                );

        return DB::table('tos_domains as d')
            ->leftJoinSub(
                $masteryQuery,
                'm',
                'm.domain_id',
                '=',
                'd.domain_id',
            )
            ->orderBy(
                'd.domain_number',
            )
            ->get([
                'd.domain_id',
                'd.domain_number',
                'd.domain_name',
                'm.avg_mastery',
                'm.student_count',
            ])
            ->map(
                function (object $row): array {
                    $mastery =
                        $row->avg_mastery !== null
                            ? (float)
                                $row->avg_mastery
                            : null;

                    return [
                        'domain_id' => (int) $row->domain_id,

                        'domain_number' => (int) $row->domain_number,

                        'domain_name' => $row->domain_name,

                        'avg_mastery' => $mastery,

                        'avg_mastery_pct' => $mastery !== null
                                ? round(
                                    $mastery * 100,
                                    1,
                                )
                                : null,

                        'student_count' => (int)
                            ($row->student_count ?? 0),

                        'band' => $mastery !== null
                                ? $this->readiness
                                    ->band(
                                        $mastery
                                        * 100,
                                    )
                                : null,
                    ];
                },
            );
    }

    private function studentsNeedingSupport(
        Cohort $cohort,
        int $limit = 20,
    ): Collection {
        $threshold =
            ReadinessService::BAND_THRESHOLDS[
                'developing'
            ];

        return User::query()
            ->where(
                'cohort_id',
                $cohort->cohort_id,
            )
            ->where(
                'role',
                'student',
            )
            ->where(
                function ($query) use (
                    $threshold,
                ): void {
                    $query
                        ->where(
                            'is_diagnostic_completed',
                            false,
                        )
                        ->orWhereNull(
                            'predicted_readiness_pct',
                        )
                        ->orWhere(
                            'predicted_readiness_pct',
                            '<',
                            $threshold,
                        );
                },
            )
            ->orderBy(
                'is_diagnostic_completed',
            )
            ->orderByRaw(
                'predicted_readiness_pct '
                    .'IS NULL DESC',
            )
            ->orderBy(
                'predicted_readiness_pct',
            )
            ->limit(
                $limit,
            )
            ->get([
                'user_id',
                'first_name',
                'middle_name',
                'last_name',
                'student_id',
                'is_diagnostic_completed',
                'predicted_readiness_pct',
            ])
            ->map(
                function (User $student): array {
                    $readiness =
                        $student
                            ->predicted_readiness_pct;

                    return [
                        'user_id' => $student->user_id,

                        'name' => $student->fullName(),

                        'student_id' => $student->student_id,

                        'is_diagnostic_completed' => $student
                            ->is_diagnostic_completed,

                        'predicted_readiness_pct' => $readiness !== null
                                ? (float) $readiness
                                : null,

                        'band' => $readiness !== null
                                ? $this->readiness
                                    ->band(
                                        (float)
                                        $readiness,
                                    )
                                : null,
                    ];
                },
            );
    }

    private function interventionCounts(
        User $faculty,
    ): array {
        $row = DB::table(
            'faculty_interventions',
        )
            ->where(
                'faculty_id',
                $faculty->user_id,
            )
            ->selectRaw(
                'COUNT(*) AS total',
            )
            ->selectRaw(
                'SUM(CASE WHEN is_completed = 0 '
                    .'THEN 1 ELSE 0 END) AS open_count',
            )
            ->selectRaw(
                'SUM(CASE WHEN is_completed = 1 '
                    .'THEN 1 ELSE 0 END) '
                    .'AS completed_count',
            )
            ->first();

        return [
            'total' => (int) ($row->total ?? 0),

            'open' => (int) ($row->open_count ?? 0),

            'completed' => (int)
                ($row->completed_count ?? 0),
        ];
    }

    private function paginatedStudents(
        Cohort $cohort,
        int $perPage = 15,
    ): LengthAwarePaginator {
        return User::query()
            ->where(
                'cohort_id',
                $cohort->cohort_id,
            )
            ->where(
                'role',
                'student',
            )
            ->orderBy(
                'last_name',
            )
            ->orderBy(
                'first_name',
            )
            ->paginate(
                $perPage,
                [
                    'user_id',
                    'first_name',
                    'middle_name',
                    'last_name',
                    'student_id',
                    'is_diagnostic_completed',
                    'predicted_readiness_pct',
                ],
                'students_page',
            );
    }

    private function interventionTargets(
        Cohort $cohort,
    ): Collection {
        return User::query()
            ->where(
                'cohort_id',
                $cohort->cohort_id,
            )
            ->where(
                'role',
                'student',
            )
            ->orderBy(
                'last_name',
            )
            ->orderBy(
                'first_name',
            )
            ->get([
                'user_id',
                'first_name',
                'last_name',
                'student_id',
            ]);
    }
}
