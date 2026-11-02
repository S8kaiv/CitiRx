<?php

namespace App\Services;

use App\Models\AdminAuditLog;
use App\Models\AssessmentSession;
use App\Models\Cohort;
use App\Models\MicroPearl;
use App\Models\Question;
use App\Models\ResearchFormVersion;
use App\Models\User;

class AdminDashboardService
{
    public function __construct(
        protected ResearchFormAuditService $formAudits,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        $accountStats = $this->accountStats();

        return [
            'accountStats' => $accountStats,

            'cohortStats' => $this->cohortStats(),

            'assessmentStats' => $this->assessmentStats(
                $accountStats['students'],
            ),

            'questionStats' => $this->questionStats(),

            'formStats' => $this->formStats(),

            'pearlStats' => $this->pearlStats(),

            'recentAudits' => AdminAuditLog::query()
                ->select([
                    'log_id',
                    'admin_id',
                    'actor_name',
                    'action',
                    'target_type',
                    'target_id',
                    'reason',
                    'created_at',
                ])
                ->with([
                    'admin:user_id,first_name,middle_name,last_name,deleted_at',
                ])
                ->latest('created_at')
                ->limit(8)
                ->get(),
        ];
    }

    /**
     * Only active, non-deleted accounts are counted.
     *
     * @return array<string, int>
     */
    private function accountStats(): array
    {
        $row = User::query()
            ->selectRaw(
                'SUM(CASE WHEN role = ? THEN 1 ELSE 0 END) AS students',
                ['student'],
            )
            ->selectRaw(
                'SUM(CASE WHEN role = ? THEN 1 ELSE 0 END) AS faculty',
                ['faculty'],
            )
            ->selectRaw(
                'SUM(CASE WHEN role = ? AND cohort_id IS NULL THEN 1 ELSE 0 END) AS students_without_cohort',
                ['student'],
            )
            ->selectRaw(
                'SUM(CASE WHEN role = ? AND cohort_id IS NULL THEN 1 ELSE 0 END) AS faculty_without_cohort',
                ['faculty'],
            )
            ->selectRaw(
                'SUM(CASE WHEN email_verified_at IS NULL THEN 1 ELSE 0 END) AS unverified',
            )
            ->first();

        return [
            'students' => (int) ($row?->students ?? 0),

            'faculty' => (int) ($row?->faculty ?? 0),

            'students_without_cohort' => (int) (
                $row?->students_without_cohort ?? 0
            ),

            'faculty_without_cohort' => (int) (
                $row?->faculty_without_cohort ?? 0
            ),

            'unverified' => (int) ($row?->unverified ?? 0),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function cohortStats(): array
    {
        $row = Cohort::query()
            ->selectRaw(
                'SUM(CASE WHEN archived_at IS NULL THEN 1 ELSE 0 END) AS active',
            )
            ->selectRaw(
                'SUM(CASE WHEN archived_at IS NOT NULL THEN 1 ELSE 0 END) AS archived',
            )
            ->first();

        return [
            'active' => (int) ($row?->active ?? 0),

            'archived' => (int) ($row?->archived ?? 0),
        ];
    }

    /**
     * Count students, not attempts. A student with repeated attempts
     * should still count only once in the dashboard completion rate.
     *
     * @return array<string, int|float>
     */
    private function assessmentStats(
        int $studentCount,
    ): array {
        $row = AssessmentSession::query()
            ->join(
                'users',
                'users.user_id',
                '=',
                'assessment_sessions.user_id',
            )
            ->where('users.role', 'student')
            ->whereNull('users.deleted_at')
            ->whereNotNull('assessment_sessions.completed_at')
            ->selectRaw(
                <<<'SQL'
                COUNT(
                    DISTINCT CASE
                        WHEN assessment_sessions.session_type = ?
                        AND assessment_sessions.research_phase = ?
                        THEN assessment_sessions.user_id
                    END
                ) AS diagnostic_completed
                SQL,
                [
                    'diagnostic',
                    'pre_test',
                ],
            )
            ->selectRaw(
                <<<'SQL'
                COUNT(
                    DISTINCT CASE
                        WHEN assessment_sessions.session_type = ?
                        AND assessment_sessions.research_phase = ?
                        THEN assessment_sessions.user_id
                    END
                ) AS mock_board_completed
                SQL,
                [
                    'mock_board',
                    'post_test',
                ],
            )
            ->first();

        $diagnosticCompleted = (int) (
            $row?->diagnostic_completed ?? 0
        );

        $mockBoardCompleted = (int) (
            $row?->mock_board_completed ?? 0
        );

        return [
            'diagnostic_completed' => $diagnosticCompleted,

            'mock_board_completed' => $mockBoardCompleted,

            'diagnostic_rate' => $this->percentage(
                $diagnosticCompleted,
                $studentCount,
            ),

            'mock_board_rate' => $this->percentage(
                $mockBoardCompleted,
                $studentCount,
            ),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function questionStats(): array
    {
        /*
        * Only questions belonging to a version formally submitted
        * for review belong in the validation work queue.
        */
        $needingReview = Question::query()
            ->whereNotNull('research_form')
            ->where('is_active', true)
            ->whereNull('research_validated_at')
            ->whereNull('research_validated_by')
            ->whereHas(
                'formVersion',
                fn ($versionQuery) => $versionQuery
                    ->where(
                        'status',
                        ResearchFormVersion::STATUS_IN_REVIEW,
                    ),
            )
            ->count();

        $row = Question::query()
            ->selectRaw(
                'COUNT(*) AS total',
            )
            ->selectRaw(
                'SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active',
            )
            ->selectRaw(
                <<<'SQL'
                SUM(
                    CASE
                        WHEN (
                            research_validated_at IS NULL
                        ) <> (
                            research_validated_by IS NULL
                        )
                        THEN 1
                        ELSE 0
                    END
                ) AS validation_issues
                SQL,
            )
            ->selectRaw(
                <<<'SQL'
                SUM(
                    CASE
                        WHEN first_served_at IS NOT NULL
                        OR research_validated_at IS NOT NULL
                        OR research_validated_by IS NOT NULL
                        THEN 1
                        ELSE 0
                    END
                ) AS locked
                SQL,
            )
            ->selectRaw(
                <<<'SQL'
                SUM(
                    CASE
                        WHEN UPPER(question_text) LIKE ?
                        THEN 1
                        ELSE 0
                    END
                ) AS placeholders
                SQL,
                ['%DEVELOPMENT MOCK%'],
            )
            ->first();

        return [
            'total' => (int) ($row?->total ?? 0),

            'active' => (int) ($row?->active ?? 0),

            'needing_review' => $needingReview,

            'validation_issues' => (int) (
                $row?->validation_issues ?? 0
            ),

            'locked' => (int) ($row?->locked ?? 0),

            'placeholders' => (int) (
                $row?->placeholders ?? 0
            ),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function formStats(): array
    {
        $reports = $this->formAudits->auditAll(
            requireValidation: true,
        );

        return collect($reports)
            ->mapWithKeys(
                function (
                    array $report,
                    string $form,
                ): array {
                    return [
                        $form => [
                            'label' => $report['label'],

                            'version_code' => $report['expected_version'],

                            'version_status' => $report['version_status'],

                            'active_questions' => $report['active_questions'],

                            'validated_questions' => $report['fully_validated'],

                            'validation_issues' => $report['partial_validation'],

                            'placeholders' => $report['placeholders'],

                            'positions_complete' => $report['positions_complete'],

                            /*
                            * This includes structure, validation,
                            * publication, version binding, and the
                            * unsafe-development-override gate.
                            */
                            'validation_complete' => $report['content_gate_ready'],
                        ],
                    ];
                },
            )
            ->all();
    }

    /**
     * @return array<string, int>
     */
    private function pearlStats(): array
    {
        $row = MicroPearl::query()
            ->selectRaw(
                'SUM(CASE WHEN content_status = ? THEN 1 ELSE 0 END) AS drafts',
                [MicroPearl::STATUS_DRAFT],
            )
            ->selectRaw(
                'SUM(CASE WHEN content_status = ? THEN 1 ELSE 0 END) AS in_review',
                [MicroPearl::STATUS_IN_REVIEW],
            )
            ->first();

        return [
            'drafts' => (int) ($row?->drafts ?? 0),

            'in_review' => (int) (
                $row?->in_review ?? 0
            ),

            /*
             * Use the real publication gate instead of merely
             * counting rows whose status string says published.
             */
            'published' => MicroPearl::query()
                ->published()
                ->count(),
        ];
    }

    private function percentage(
        int $completed,
        int $total,
    ): float {
        if ($total === 0) {
            return 0.0;
        }

        return round(
            $completed * 100 / $total,
            1,
        );
    }
}
