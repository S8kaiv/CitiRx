<?php

namespace App\Services;

use App\Models\AdminAuditLog;
use App\Models\AssessmentSession;
use App\Models\Cohort;
use App\Models\MicroPearl;
use App\Models\Question;
use App\Models\User;

class AdminDashboardService
{
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
                        WHEN research_form IS NOT NULL
                        AND is_active = 1
                        AND (
                            research_validated_at IS NULL
                            OR research_validated_by IS NULL
                        )
                        THEN 1
                        ELSE 0
                    END
                ) AS needing_review
                SQL,
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

            'needing_review' => (int) (
                $row?->needing_review ?? 0
            ),

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
     * This is a lightweight summary, not a replacement for
     * research:audit-forms. The full command remains the source of
     * truth for blueprint and content-integrity validation.
     *
     * @return array<string, array<string, mixed>>
     */
    private function formStats(): array
    {
        $forms = [
            Question::FORM_PRE_TEST_A => 'Form A — Diagnostic',

            Question::FORM_POST_TEST_B => 'Form B — Mock Board',
        ];

        $rows = Question::query()
            ->whereIn(
                'research_form',
                array_keys($forms),
            )
            ->where('is_active', true)
            ->select('research_form')
            ->selectRaw(
                'COUNT(*) AS active_questions',
            )
            ->selectRaw(
                <<<'SQL'
                SUM(
                    CASE
                        WHEN research_validated_at IS NOT NULL
                        AND research_validated_by IS NOT NULL
                        THEN 1
                        ELSE 0
                    END
                ) AS validated_questions
                SQL,
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
                        WHEN UPPER(question_text) LIKE ?
                        THEN 1
                        ELSE 0
                    END
                ) AS placeholders
                SQL,
                ['%DEVELOPMENT MOCK%'],
            )
            ->selectRaw(
                'COUNT(DISTINCT form_position) AS distinct_positions',
            )
            ->selectRaw(
                'MIN(form_position) AS first_position',
            )
            ->selectRaw(
                'MAX(form_position) AS last_position',
            )
            ->groupBy('research_form')
            ->get()
            ->keyBy('research_form');

        $result = [];

        foreach ($forms as $form => $label) {
            $row = $rows->get($form);

            $activeQuestions = (int) (
                $row?->active_questions ?? 0
            );

            $validatedQuestions = (int) (
                $row?->validated_questions ?? 0
            );

            $validationIssues = (int) (
                $row?->validation_issues ?? 0
            );

            $placeholders = (int) (
                $row?->placeholders ?? 0
            );

            $positionsComplete =
                (int) ($row?->distinct_positions ?? 0) === 60
                && (int) ($row?->first_position ?? 0) === 1
                && (int) ($row?->last_position ?? 0) === 60;

            $result[$form] = [
                'label' => $label,

                'active_questions' => $activeQuestions,

                'validated_questions' => $validatedQuestions,

                'validation_issues' => $validationIssues,

                'placeholders' => $placeholders,

                'positions_complete' => $positionsComplete,

                /*
                 * Do not call this full research readiness.
                 * The blueprint audit still has to pass.
                 */
                'validation_complete' => $activeQuestions === 60
                    && $validatedQuestions === 60
                    && $validationIssues === 0
                    && $placeholders === 0
                    && $positionsComplete,
            ];
        }

        return $result;
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
