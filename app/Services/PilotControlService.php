<?php

namespace App\Services;

use App\Models\AssessmentSession;
use App\Models\Cohort;
use App\Models\PilotControl;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use LogicException;
use RuntimeException;

class PilotControlService
{
    private const TRANSITIONS = [
        PilotControl::PHASE_SETUP => [
            PilotControl::PHASE_DIAGNOSTIC_OPEN,
        ],
        PilotControl::PHASE_DIAGNOSTIC_OPEN => [
            PilotControl::PHASE_BETWEEN_ASSESSMENTS,
        ],
        PilotControl::PHASE_BETWEEN_ASSESSMENTS => [
            PilotControl::PHASE_POST_TEST_OPEN,
        ],
        PilotControl::PHASE_POST_TEST_OPEN => [
            PilotControl::PHASE_CLOSED,
        ],
        PilotControl::PHASE_CLOSED => [],
    ];

    public function __construct(
        protected ResearchFormAuditService $formAudits,
        protected AdminAuditService $audit,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function dashboardData(): array
    {
        $cohorts = Cohort::query()
            ->active()
            ->withCount([
                'students as eligible_students_count' => fn (Builder $query): Builder => $query
                    ->whereNotNull('email_verified_at'),

                'students as diagnostic_completed_count' => fn (Builder $query): Builder => $query
                    ->whereHas(
                        'assessmentSessions',
                        fn (Builder $sessions): Builder => $sessions
                            ->where(
                                'research_phase',
                                'pre_test',
                            )
                            ->whereNotNull(
                                'completed_at',
                            ),
                    ),

                'students as diagnostic_incomplete_count' => fn (Builder $query): Builder => $query
                    ->whereHas(
                        'assessmentSessions',
                        fn (Builder $sessions): Builder => $sessions
                            ->where(
                                'research_phase',
                                'pre_test',
                            )
                            ->whereNull(
                                'completed_at',
                            ),
                    ),

                'students as post_test_completed_count' => fn (Builder $query): Builder => $query
                    ->whereHas(
                        'assessmentSessions',
                        fn (Builder $sessions): Builder => $sessions
                            ->where(
                                'research_phase',
                                'post_test',
                            )
                            ->whereNotNull(
                                'completed_at',
                            ),
                    ),

                'students as post_test_incomplete_count' => fn (Builder $query): Builder => $query
                    ->whereHas(
                        'assessmentSessions',
                        fn (Builder $sessions): Builder => $sessions
                            ->where(
                                'research_phase',
                                'post_test',
                            )
                            ->whereNull(
                                'completed_at',
                            ),
                    ),
            ])
            ->orderBy('cohort_name')
            ->get();

        $submissionReasons = AssessmentSession::query()
            ->join(
                'users',
                'users.user_id',
                '=',
                'assessment_sessions.user_id',
            )
            ->whereNotNull('users.cohort_id')
            ->whereNotNull(
                'assessment_sessions.completed_at',
            )
            ->whereNotNull(
                'assessment_sessions.submission_reason',
            )
            ->selectRaw(
                'users.cohort_id, '
                    .'assessment_sessions.submission_reason, '
                    .'COUNT(*) AS total',
            )
            ->groupBy(
                'users.cohort_id',
                'assessment_sessions.submission_reason',
            )
            ->get()
            ->groupBy('cohort_id');

        return [
            'control' => PilotControl::query()
                ->with('starter')
                ->sole(),

            'formAudits' => [
                Question::FORM_PRE_TEST_A => $this->formAudits->audit(
                    Question::FORM_PRE_TEST_A,
                    true,
                ),

                Question::FORM_POST_TEST_B => $this->formAudits->audit(
                    Question::FORM_POST_TEST_B,
                    true,
                ),
            ],

            'cohorts' => $cohorts,

            'submissionReasons' => $submissionReasons,

            'studentsWithoutCohorts' => User::query()
                ->where('role', 'student')
                ->whereNull('cohort_id')
                ->count(),

            'unverifiedStudents' => User::query()
                ->where('role', 'student')
                ->whereNull('email_verified_at')
                ->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function transition(
        User $administrator,
        array $data,
    ): PilotControl {
        $this->assertAdministrator($administrator);

        return DB::transaction(function () use (
            $administrator,
            $data,
        ): PilotControl {
            $control = PilotControl::query()
                ->lockForUpdate()
                ->sole();

            if (
                $control->lock_version
                !== (int) $data['lock_version']
            ) {
                throw new LogicException(
                    'Pilot settings changed in another '
                        .'browser session. Refresh and try again.',
                );
            }

            $nextPhase = $data['phase'];

            if ($nextPhase === $control->phase) {
                throw new LogicException(
                    'The pilot is already in that phase.',
                );
            }

            $emergency = (bool) (
                $data['emergency_override'] ?? false
            );

            if (
                $emergency
                && ! $control->officialCollectionHasStarted()
            ) {
                throw new LogicException(
                    'Emergency override is available only after '
                        .'official collection has started.',
                );
            }

            if (
                $emergency
                && $nextPhase !== PilotControl::PHASE_CLOSED
            ) {
                throw new LogicException(
                    'Emergency override may only close '
                        .'official data collection.',
                );
            }

            $allowed = in_array(
                $nextPhase,
                self::TRANSITIONS[$control->phase] ?? [],
                true,
            );

            if (! $allowed && ! $emergency) {
                throw new LogicException(
                    'That pilot phase transition is not allowed. '
                        .'Use the normal research sequence.',
                );
            }

            if (
                $nextPhase === PilotControl::PHASE_SETUP
                && $control->officialCollectionHasStarted()
            ) {
                throw new LogicException(
                    'Official collection cannot be returned '
                        .'to setup mode.',
                );
            }

            if (
                $nextPhase
                === PilotControl::PHASE_DIAGNOSTIC_OPEN
            ) {
                $this->assertAllFormsReady();

                $this->assertActiveCohortExists();

                /*
                * Cohort schedules become immutable when official
                * collection starts, so they must be configured first.
                */
                $this->assertUsablePostTestWindowExists();
            }

            if (
                $nextPhase
                === PilotControl::PHASE_POST_TEST_OPEN
            ) {
                $this->assertFormReady(
                    Question::FORM_POST_TEST_B,
                );

                $this->assertUsablePostTestWindowExists();
            }

            $changes = [
                'phase' => [
                    'old' => $control->phase,
                    'new' => $nextPhase,
                ],
            ];

            if (
                $control
                    ->official_collection_started_at
                    === null
                    && $nextPhase
                    === PilotControl::PHASE_DIAGNOSTIC_OPEN
            ) {
                $startedAt = now();

                $control
                    ->official_collection_started_at =
                    $startedAt;

                $control
                    ->official_collection_started_by =
                    $administrator->user_id;

                $changes['official_collection_started_at'] = [
                    'old' => null,
                    'new' => $startedAt
                        ->toDateTimeString(),
                ];
            }

            $control->phase = $nextPhase;
            $control->lock_version++;
            $control->save();

            $this->audit->record(
                $administrator,
                $emergency
                    ? 'pilot.phase_emergency_override'
                    : 'pilot.phase_transitioned',
                $control,
                $changes,
                $data['reason'],
            );

            return $control->fresh('starter');
        });
    }

    public function assertDiagnosticStartAllowed(): void
    {
        $control = PilotControl::query()->sole();

        if (! $control->diagnosticIsOpen()) {
            throw new LogicException(
                'The official diagnostic is not currently open.',
            );
        }
    }

    public function assertPostTestStartAllowed(): void
    {
        $control = PilotControl::query()->sole();

        if (! $control->postTestIsOpen()) {
            throw new LogicException(
                'The official mock-board examination '
                    .'is not currently open.',
            );
        }
    }

    public function assertResearchConfigurationMutable(): void
    {
        /*
        * This method must be called from inside a database
        * transaction so the row lock remains active until
        * the configuration change is committed or rolled back.
        */
        $control = PilotControl::query()
            ->lockForUpdate()
            ->sole();

        if ($control->officialCollectionHasStarted()) {
            throw new LogicException(
                'Research configuration is locked because '
                    .'official data collection has started.',
            );
        }
    }

    private function assertAllFormsReady(): void
    {
        $this->assertFormReady(
            Question::FORM_PRE_TEST_A,
        );

        $this->assertFormReady(
            Question::FORM_POST_TEST_B,
        );
    }

    private function assertFormReady(string $form): void
    {
        $audit = $this->formAudits->audit(
            $form,
            true,
        );

        if (! $audit['content_gate_ready']) {
            throw new RuntimeException(
                "{$form} is not ready for official use.",
            );
        }
    }

    private function assertActiveCohortExists(): void
    {
        if (! Cohort::query()->active()->exists()) {
            throw new RuntimeException(
                'At least one active cohort is required.',
            );
        }
    }

    private function assertUsablePostTestWindowExists(): void
    {
        $exists = Cohort::query()
            ->active()
            ->whereNotNull('post_test_opens_at')
            ->whereNotNull('post_test_closes_at')
            ->whereColumn(
                'post_test_closes_at',
                '>',
                'post_test_opens_at',
            )
            ->where(
                'post_test_closes_at',
                '>',
                now(),
            )
            ->exists();

        if (! $exists) {
            throw new RuntimeException(
                'No active cohort has a valid upcoming '
                    .'or open post-test window.',
            );
        }
    }

    private function assertAdministrator(
        User $administrator,
    ): void {
        if ($administrator->role !== 'admin') {
            throw new LogicException(
                'Only administrators may change pilot controls.',
            );
        }
    }
}
