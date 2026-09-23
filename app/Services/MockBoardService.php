<?php

namespace App\Services;

use App\Models\AssessmentResponse;
use App\Models\AssessmentSession;
use App\Models\Question;
use App\Models\QuestionChoice;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;
use RuntimeException;

class MockBoardService
{
    public function __construct(
        protected ResearchFormService $researchForms,
        protected ReadinessService $readiness,
    ) {}

    /**
     * Return the student's Mock Board availability and attempt status.
     *
     * @return array<string, mixed>
     */
    public function status(User $user): array
    {
        $user->loadMissing('cohort');

        $sessions = AssessmentSession::query()
            ->where('user_id', $user->user_id)
            ->where('session_type', 'mock_board')
            ->where('research_phase', 'post_test')
            ->orderByDesc('started_at')
            ->get();

        $completed = $sessions->first(
            fn (AssessmentSession $session): bool => $session->completed_at !== null,
        );

        $inProgress = $completed
            ? null
            : $sessions->first(
                fn (AssessmentSession $session): bool => $session->completed_at === null,
            );

        $availability = $this->researchForms->availability(
            Question::FORM_POST_TEST_B,
        );

        $canStart = false;
        $reason = null;

        if ($completed) {
            $reason = 'You have already completed the research post-test.';
        } elseif ($inProgress) {
            $reason = 'Resume your existing research post-test.';
        } elseif (! $user->is_diagnostic_completed) {
            $reason = 'Complete the diagnostic before starting the research post-test.';
        } elseif (! $this->cohortWindowIsOpen($user)) {
            $reason = 'The research post-test is not currently available for your cohort.';
        } else {
            $shortSubjects = $availability->filter(
                fn (array $subject): bool => $subject['available'] !== $subject['required'],
            );

            if ($shortSubjects->isNotEmpty()) {
                $summary = $shortSubjects
                    ->map(
                        fn (array $subject): string => $subject['domain_name'].' ('
                            .$subject['available'].'/'
                            .$subject['required'].')',
                    )
                    ->implode(', ');

                $reason = "Form B is incomplete: {$summary}.";
            } else {
                $canStart = true;
            }
        }

        return [
            'can_start' => $canStart,
            'reason' => $reason,
            'availability' => $availability,
            'in_progress' => $inProgress,
            'completed' => $completed,
            'duration_minutes' => $this->durationMinutes(),
            'form_version' => $this->formVersion(),
        ];
    }

    /**
     * Start Form B or return the existing unfinished attempt.
     */
    public function startPostTest(User $user): AssessmentSession
    {
        return DB::transaction(function () use ($user): AssessmentSession {
            /*
             * Locking the user prevents two rapid requests from creating
             * two post-test sessions.
             */
            $user = User::query()
                ->with('cohort')
                ->whereKey($user->user_id)
                ->lockForUpdate()
                ->firstOrFail();

            $completed = AssessmentSession::query()
                ->where('user_id', $user->user_id)
                ->where('session_type', 'mock_board')
                ->where('research_phase', 'post_test')
                ->whereNotNull('completed_at')
                ->exists();

            if ($completed) {
                throw new LogicException(
                    'You have already completed the research post-test.',
                );
            }

            $existing = AssessmentSession::query()
                ->where('user_id', $user->user_id)
                ->where('session_type', 'mock_board')
                ->where('research_phase', 'post_test')
                ->whereNull('completed_at')
                ->latest('started_at')
                ->first();

            if ($existing) {
                return $existing;
            }

            $this->assertStartEligibility($user);

            /*
             * ResearchFormService guarantees:
             *
             * - exactly 60 questions
             * - exactly 10 per subject
             * - positions 1 through 60
             * - four choices per question
             * - one correct choice per question
             */
            $questions = $this->researchForms->load(
                Question::FORM_POST_TEST_B,
            );

            /*
             * Preserve readiness before the post-test. The Mock Board
             * must not alter BKT mastery, XP, badges, or readiness.
             */
            $readiness = $this->readiness->breakdown($user);

            $domainSnapshot = collect($readiness['domains'])
                ->mapWithKeys(
                    fn (array $domain): array => [
                        (string) $domain['domain_id'] => round(
                            (float) $domain['mastery'],
                            4,
                        ),
                    ],
                )
                ->all();

            /*
             * The contents of Form B remain fixed. Only presentation
             * order is randomized for this student's attempt.
             */
            $orderedQuestions = $questions->shuffle()->values();
            $startedAt = now();

            $session = AssessmentSession::query()->create([
                'user_id' => $user->user_id,
                'session_type' => 'mock_board',
                'research_phase' => 'post_test',
                'served_question_ids' => $orderedQuestions
                    ->pluck('question_id')
                    ->all(),
                'target_length' => ResearchFormService::TOTAL_ITEMS,
                'domain_filter_id' => null,
                'current_question_id' => null,
                'current_question_started_at' => null,
                'draft_answers' => null,
                'total_items' => ResearchFormService::TOTAL_ITEMS,
                'correct_items' => 0,
                'xp_awarded' => 0,
                'started_at' => $startedAt,
                'expires_at' => $startedAt
                    ->copy()
                    ->addMinutes($this->durationMinutes()),
                'readiness_snapshot_pct' => $readiness['total'],
                'domain_mastery_snapshot' => $domainSnapshot,
                'exam_form_version' => $this->formVersion(),
                'submission_reason' => null,
            ]);

            $timestamp = now();

            /*
             * One bulk insert is more efficient than creating 60
             * AssessmentResponse models individually.
             */
            AssessmentResponse::query()->insert(
                $orderedQuestions
                    ->map(
                        fn (Question $question, int $index): array => [
                            'response_id' => (string) Str::uuid(),
                            'session_id' => $session->session_id,
                            'question_id' => $question->question_id,
                            'selected_choice_id' => null,
                            'item_position' => $index + 1,
                            'is_correct' => null,
                            'answered_at' => null,
                            'created_at' => $timestamp,
                            'updated_at' => $timestamp,
                        ],
                    )
                    ->all(),
            );

            return $session;
        });
    }

    /**
     * Save or clear one answer without revealing correctness.
     */
    public function saveAnswer(
        User $user,
        AssessmentSession $session,
        string $questionId,
        ?string $choiceId,
    ): void {
        DB::transaction(function () use (
            $user,
            $session,
            $questionId,
            $choiceId,
        ): void {
            $session = AssessmentSession::query()
                ->whereKey($session->session_id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertResearchSession($session);

            if ($session->user_id !== $user->user_id) {
                throw new LogicException(
                    'This research post-test session does not belong to you.',
                );
            }

            if ($session->completed_at !== null) {
                throw new LogicException(
                    'This research post-test has already been submitted.',
                );
            }

            if ($this->hasExpired($session)) {
                throw new LogicException(
                    'The research post-test time limit has expired.',
                );
            }

            $response = AssessmentResponse::query()
                ->where('session_id', $session->session_id)
                ->where('question_id', $questionId)
                ->lockForUpdate()
                ->first();

            if (! $response) {
                throw new InvalidArgumentException(
                    'This question is not part of the research post-test.',
                );
            }

            if ($choiceId !== null) {
                $choiceExists = QuestionChoice::query()
                    ->where('choice_id', $choiceId)
                    ->where('question_id', $questionId)
                    ->exists();

                if (! $choiceExists) {
                    throw new InvalidArgumentException(
                        'The selected choice does not belong to this question.',
                    );
                }
            }

            $response->forceFill([
                'selected_choice_id' => $choiceId,
                'is_correct' => null,
                'answered_at' => $choiceId === null ? null : now(),
            ])->save();
        });
    }

    /**
     * Load the questions and saved selections for the exam page.
     *
     * @return array<string, mixed>
     */
    public function takeData(AssessmentSession $session): array
    {
        $this->assertResearchSession($session);

        if ($session->completed_at !== null) {
            throw new LogicException(
                'This research post-test has already been submitted.',
            );
        }

        if ($session->expires_at === null) {
            throw new RuntimeException(
                'This research post-test has no server deadline.',
            );
        }

        $responses = AssessmentResponse::query()
            ->with([
                'question.choices' => fn ($query) => $query->orderBy('choice_letter'),
                'question.competency.domain',
            ])
            ->where('session_id', $session->session_id)
            ->orderBy('item_position')
            ->get();

        $this->assertCompleteResponseSet(
            $session,
            $responses,
        );

        return [
            'responses' => $responses,
            'seconds_remaining' => max(
                0,
                $session->expires_at->timestamp - now()->timestamp,
            ),
        ];
    }

    /**
     * Score persisted answers and complete the post-test.
     *
     * @return array<string, mixed>
     */
    public function submit(
        AssessmentSession $session,
        string $reason = 'manual',
    ): array {
        if (! in_array($reason, ['manual', 'expired'], true)) {
            throw new InvalidArgumentException(
                'Invalid research post-test submission reason.',
            );
        }

        return DB::transaction(
            function () use ($session, $reason): array {
                $session = AssessmentSession::query()
                    ->whereKey($session->session_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->assertResearchSession($session);

                /*
                 * An already completed session is not scored again.
                 */
                if ($session->completed_at !== null) {
                    return $this->results($session);
                }

                if ($session->expires_at === null) {
                    throw new RuntimeException(
                        'This research post-test has no server deadline.',
                    );
                }

                $expired = $this->hasExpired($session);

                $submissionReason = $expired ? 'expired' : $reason;

                if ($reason === 'expired' && ! $expired) {
                    throw new LogicException(
                        'This research post-test has not expired.',
                    );
                }

                $responses = AssessmentResponse::query()
                    ->with([
                        'selectedChoice',
                        'question.competency.domain',
                    ])
                    ->where('session_id', $session->session_id)
                    ->orderBy('item_position')
                    ->get();

                $this->assertCompleteResponseSet(
                    $session,
                    $responses,
                );

                $timestamp = now();

                $updates = $responses->map(
                    function (
                        AssessmentResponse $response
                    ) use ($timestamp): array {
                        if (
                            $response->selected_choice_id !== null
                            && $response->selectedChoice === null
                        ) {
                            throw new RuntimeException(
                                'A saved selected choice could not be loaded.',
                            );
                        }

                        if (
                            $response->selectedChoice !== null
                            && $response->selectedChoice->question_id
                                !== $response->question_id
                        ) {
                            throw new RuntimeException(
                                'A saved choice does not belong to its question.',
                            );
                        }

                        $isCorrect =
                            $response->selected_choice_id === null
                                ? null
                                : (bool) $response
                                    ->selectedChoice
                                    ->is_correct;

                        /*
                         * Update the in-memory collection too so the
                         * summary uses the newly computed result.
                         */
                        $response->is_correct = $isCorrect;

                        return [
                            'response_id' => $response->response_id,
                            'session_id' => $response->session_id,
                            'question_id' => $response->question_id,
                            'selected_choice_id' => $response->selected_choice_id,
                            'item_position' => $response->item_position,
                            'is_correct' => $isCorrect,
                            'answered_at' => $response->answered_at,
                            'created_at' => $response->created_at,
                            'updated_at' => $timestamp,
                        ];
                    },
                );

                /*
                 * Update all 60 answers in one database operation.
                 */
                AssessmentResponse::query()->upsert(
                    $updates->all(),
                    ['response_id'],
                    [
                        'is_correct',
                        'updated_at',
                    ],
                );

                $correct = $responses->filter(
                    fn (AssessmentResponse $response): bool => $response->is_correct === true,
                )->count();

                $session->forceFill([
                    'total_items' => ResearchFormService::TOTAL_ITEMS,
                    'correct_items' => $correct,
                    'completed_at' => $timestamp,
                    'submission_reason' => $submissionReason,
                    'current_question_id' => null,
                    'current_question_started_at' => null,
                    'draft_answers' => null,
                    'xp_awarded' => 0,
                ])->save();

                return $this->summarize(
                    $session,
                    $responses,
                );
            },
        );
    }

    /**
     * Return score-level results without exposing answer keys.
     *
     * @return array<string, mixed>
     */
    public function results(AssessmentSession $session): array
    {
        $session = $session->fresh();

        $this->assertResearchSession($session);

        if ($session->completed_at === null) {
            throw new LogicException(
                'This research post-test has not been submitted.',
            );
        }

        $responses = AssessmentResponse::query()
            ->with('question.competency.domain')
            ->where('session_id', $session->session_id)
            ->orderBy('item_position')
            ->get();

        $this->assertCompleteResponseSet(
            $session,
            $responses,
        );

        return $this->summarize(
            $session,
            $responses,
        );
    }

    private function assertStartEligibility(User $user): void
    {
        if (! $user->is_diagnostic_completed) {
            throw new LogicException(
                'Complete the diagnostic before starting the research post-test.',
            );
        }

        if (! $this->cohortWindowIsOpen($user)) {
            throw new LogicException(
                'The research post-test is not currently available for your cohort.',
            );
        }
    }

    private function cohortWindowIsOpen(User $user): bool
    {
        $cohort = $user->cohort;

        if (
            ! $cohort
            || ! $cohort->post_test_opens_at
            || ! $cohort->post_test_closes_at
        ) {
            return false;
        }

        $now = now();

        return $now->greaterThanOrEqualTo(
            $cohort->post_test_opens_at,
        ) && $now->lessThan(
            $cohort->post_test_closes_at,
        );
    }

    private function assertResearchSession(
        AssessmentSession $session,
    ): void {
        if (
            $session->session_type !== 'mock_board'
            || $session->research_phase !== 'post_test'
        ) {
            throw new InvalidArgumentException(
                'The supplied session is not a research post-test session.',
            );
        }
    }

    private function hasExpired(
        AssessmentSession $session,
    ): bool {
        return $session->expires_at !== null
            && now()->greaterThanOrEqualTo(
                $session->expires_at,
            );
    }

    private function durationMinutes(): int
    {
        $duration = (int) config(
            'citirx.research.post_test_duration_minutes',
            0,
        );

        if ($duration < 1 || $duration > 600) {
            throw new RuntimeException(
                'The post-test duration configuration is invalid.',
            );
        }

        return $duration;
    }

    private function formVersion(): string
    {
        $version = trim(
            (string) config(
                'citirx.research.post_test_form_version',
                '',
            ),
        );

        if ($version === '') {
            throw new RuntimeException(
                'The post-test form version is not configured.',
            );
        }

        return $version;
    }

    /**
     * @param  Collection<int, AssessmentResponse>  $responses
     */
    private function assertCompleteResponseSet(
        AssessmentSession $session,
        Collection $responses,
    ): void {
        if (
            $responses->count()
            !== ResearchFormService::TOTAL_ITEMS
        ) {
            throw new RuntimeException(
                'The research post-test response set is incomplete.',
            );
        }

        $servedIds = collect(
            $session->served_question_ids ?? [],
        )
            ->map(fn ($id): string => (string) $id)
            ->sort()
            ->values();

        $responseIds = $responses
            ->pluck('question_id')
            ->map(fn ($id): string => (string) $id)
            ->sort()
            ->values();

        if ($servedIds->all() !== $responseIds->all()) {
            throw new RuntimeException(
                'The saved responses do not match the served questions.',
            );
        }
    }

    /**
     * @param  Collection<int, AssessmentResponse>  $responses
     * @return array<string, mixed>
     */
    private function summarize(
        AssessmentSession $session,
        Collection $responses,
    ): array {
        $total = $responses->count();

        $answered = $responses->filter(
            fn (AssessmentResponse $response): bool => $response->selected_choice_id !== null,
        )->count();

        $correct = $responses->filter(
            fn (AssessmentResponse $response): bool => $response->is_correct === true,
        )->count();

        $perDomain = $responses
            ->groupBy(
                fn (AssessmentResponse $response) => $response
                    ->question
                    ->competency
                    ->domain
                    ->domain_id,
            )
            ->map(function (Collection $group): array {
                $domain = $group
                    ->first()
                    ->question
                    ->competency
                    ->domain;

                $domainTotal = $group->count();

                $domainAnswered = $group->filter(
                    fn (AssessmentResponse $response): bool => $response->selected_choice_id !== null,
                )->count();

                $domainCorrect = $group->filter(
                    fn (AssessmentResponse $response): bool => $response->is_correct === true,
                )->count();

                return [
                    'domain_id' => $domain->domain_id,
                    'domain_number' => $domain->domain_number,
                    'domain_name' => $domain->domain_name,
                    'total' => $domainTotal,
                    'answered' => $domainAnswered,
                    'unanswered' => $domainTotal - $domainAnswered,
                    'correct' => $domainCorrect,
                    'percentage' => $domainTotal > 0
                        ? round(
                            ($domainCorrect / $domainTotal) * 100,
                            2,
                        )
                        : 0.0,
                ];
            })
            ->sortBy('domain_number')
            ->values()
            ->all();

        return [
            'total' => $total,
            'answered' => $answered,
            'unanswered' => $total - $answered,
            'correct' => $correct,
            'percentage' => $total > 0
                ? round(($correct / $total) * 100, 2)
                : 0.0,
            'per_domain' => $perDomain,
            'submission_reason' => $session->submission_reason,
        ];
    }
}
