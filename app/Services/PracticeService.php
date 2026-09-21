<?php

namespace App\Services;

use App\Models\AssessmentSession;
use App\Models\Question;
use App\Models\QuestionBookmark;
use App\Models\ResponseTelemetryLog;
use App\Models\RxVault;
use App\Models\TosDomain;
use App\Models\User;
use App\Models\UserKnowledgeState;
use App\Models\XpTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use RuntimeException;

class PracticeService
{
    // -------------------------------------------------------------
    // Configuration
    // -------------------------------------------------------------

    public const ALLOWED_LENGTHS = [
        10,
        20,
        30,
    ];

    public const MODE_ADAPTIVE = 'adaptive';

    public const MODE_MISTAKES = 'mistakes';

    public const MODE_BOOKMARKS = 'bookmarks';

    public const ALLOWED_MODES = [
        self::MODE_ADAPTIVE,
        self::MODE_MISTAKES,
        self::MODE_BOOKMARKS,
    ];

    public const DRILL_MODES = [
        self::MODE_MISTAKES,
        self::MODE_BOOKMARKS,
    ];

    public const DEFAULT_DRILL_LENGTH = 10;

    private const XP_CORRECT = 10;

    private const XP_ACCURACY_BONUS = 50;

    private const XP_COMPLETION_BONUS = 20;

    private const REASON_CORRECT =
        'Correct practice answer';

    private const REASON_ACCURACY_BONUS =
        'Practice accuracy bonus';

    private const REASON_COMPLETION_BONUS =
        'Practice completion bonus';

    private const ACCURACY_BONUS_THRESHOLD = 0.80;

    private const STREAK_ACCURACY_THRESHOLD = 0.70;

    private const STREAK_MIN_ELIGIBLE = 10;

    /*
     * CitiRx speed-guessing heuristic.
     *
     * These values are application-level heuristics unless
     * your research methodology specifically validates them.
     */
    private const MIN_READING_SECONDS = 5.0;

    private const WORDS_PER_MINUTE = 250;

    private const RECENT_EXCLUSION_LIMIT = 20;

    /*
     * Maximum value supported by DECIMAL(6, 2).
     */
    private const MAX_RESPONSE_SECONDS = 9999.99;

    public function __construct(
        protected BktService $bkt,
        protected ReadinessService $readiness,
        protected BadgeService $badges,
        protected RxVaultService $rxVault,
        protected LevelService $levels,
    ) {}

    // =============================================================
    // START SESSION
    // =============================================================

    public function startSession(
        User $user,
        int $length,
        ?int $domainFilterId = null,
        string $mode = self::MODE_ADAPTIVE,
    ): AssessmentSession {
        if (! in_array($length, self::ALLOWED_LENGTHS, true)) {
            throw new InvalidArgumentException(
                'Practice length must be one of: '
                    .implode(', ', self::ALLOWED_LENGTHS)
                    .'.'
            );
        }

        if (! in_array($mode, self::ALLOWED_MODES, true)) {
            throw new InvalidArgumentException(
                'The selected Practice mode is invalid.'
            );
        }

        /*
     * Domain filtering belongs only to normal adaptive Practice.
     */
        if (
            $mode !== self::MODE_ADAPTIVE
            && $domainFilterId !== null
        ) {
            throw new InvalidArgumentException(
                'Targeted drills cannot be combined with a domain filter.'
            );
        }

        if (
            $domainFilterId !== null
            && ! TosDomain::query()
                ->where('domain_id', $domainFilterId)
                ->exists()
        ) {
            throw new InvalidArgumentException(
                'The selected Practice domain does not exist.'
            );
        }

        return DB::transaction(function () use (
            $user,
            $length,
            $domainFilterId,
            $mode,
        ) {
            /*
         * Prevent duplicate sessions caused by rapid double-clicking.
         */
            $user = User::query()
                ->where('user_id', $user->user_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $user->is_diagnostic_completed) {
                throw new LogicException(
                    'Complete the diagnostic test before starting practice.'
                );
            }

            /*
         * Only resume a session with the exact same mode and scope.
         */
            $existing = AssessmentSession::query()
                ->where('user_id', $user->user_id)
                ->where('session_type', 'practice')
                ->whereNull('completed_at')
                ->where('practice_mode', $mode)
                ->where('target_length', $length)
                ->where('domain_filter_id', $domainFilterId)
                ->latest('started_at')
                ->first();

            if ($existing) {
                return $existing;
            }

            if (! $this->questionPoolQuery(
                $user->user_id,
                $mode,
                $domainFilterId,
            )->exists()) {
                $message = match ($mode) {
                    self::MODE_MISTAKES => 'Your Mistake Locker is currently empty.',

                    self::MODE_BOOKMARKS => 'You have not bookmarked any active questions yet.',

                    default => 'No active Practice questions are available for the selected scope.',
                };

                throw new RuntimeException($message);
            }

            return AssessmentSession::create([
                'user_id' => $user->user_id,
                'session_type' => 'practice',
                'research_phase' => 'none',
                'served_question_ids' => [],
                'target_length' => $length,
                'practice_mode' => $mode,
                'domain_filter_id' => $domainFilterId,
                'current_question_id' => null,
                'current_question_started_at' => null,
                'total_items' => 0,
                'correct_items' => 0,
                'xp_awarded' => 0,
                'started_at' => now(),
            ]);
        });
    }

    // =============================================================
    // PICK NEXT QUESTION
    // =============================================================

    /**
     * Pick and serve exactly one adaptive practice question.
     *
     * Returns null when:
     *
     * - the session is complete,
     * - the target number of answered questions is reached, or
     * - no active question exists in the selected scope.
     */
    public function pickNextQuestion(
        AssessmentSession $session
    ): ?Question {
        return DB::transaction(function () use ($session) {

            $session = AssessmentSession::query()
                ->where(
                    'session_id',
                    $session->session_id
                )
                ->lockForUpdate()
                ->firstOrFail();

            if ($session->session_type !== 'practice') {
                throw new LogicException(
                    'This session is not a practice session.'
                );
            }

            if ($session->completed_at !== null) {
                return null;
            }

            /*
             * IMPORTANT:
             *
             * Reuse the unanswered current question BEFORE checking
             * the target length.
             *
             * served_question_ids includes the currently displayed
             * question even though it has not been answered yet.
             */
            if ($session->current_question_id !== null) {
                $question = Question::query()
                    ->where(
                        'question_id',
                        $session->current_question_id
                    )
                    ->where(
                        'is_active',
                        true
                    )
                    ->first();

                if ($question) {
                    /*
                     * Reset the timer when the question is resumed.
                     *
                     * This prevents several hours of idle browser time
                     * from counting as response time.
                     */
                    $session->current_question_started_at =
                        now();

                    $session->save();

                    return $question;
                }

                /*
                 * If the current question disappeared or became
                 * unavailable, clear it and select another one.
                 */
                $session->current_question_id = null;
                $session->current_question_started_at = null;
                $session->save();
            }

            /*
             * Target length means ANSWERED questions.
             *
             * Do not use count(served_question_ids) here because
             * that includes an unanswered current question.
             */
            $target =
                (int) ($session->target_length ?? 0);

            if (
                $target <= 0
                || $session->total_items >= $target
            ) {
                return null;
            }

            $served =
                $session->served_question_ids ?? [];

            $question =
                $this->selectNextQuestion(
                    $session,
                    $served
                );

            if (! $question) {
                return null;
            }

            /*
             * served_question_ids records the exact chronological
             * sequence of served questions.
             *
             * Duplicates are allowed only when the available pool
             * has been exhausted and Tier 3 is required.
             */
            $served[] =
                $question->question_id;

            $session->served_question_ids =
                $served;

            $session->current_question_id =
                $question->question_id;

            $session->current_question_started_at =
                now();

            $session->save();

            return $question;
        });
    }

    // =============================================================
    // SUBMIT ONE ANSWER
    // =============================================================

    public function submitAnswer(
        User $user,
        AssessmentSession $session,
        string $questionId,
        string $choiceId,
    ): array {
        return DB::transaction(function () use (
            $user,
            $session,
            $questionId,
            $choiceId
        ) {

            /*
             * Lock session first.
             */
            $session = AssessmentSession::query()
                ->where(
                    'session_id',
                    $session->session_id
                )
                ->lockForUpdate()
                ->firstOrFail();

            /*
             * Lock the user too.
             *
             * This serializes XP/readiness/streak updates if the
             * same student somehow has multiple practice sessions
             * open at once.
             */
            $user = User::query()
                ->where(
                    'user_id',
                    $user->user_id
                )
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertCanAnswer(
                $user,
                $session,
                $questionId
            );

            $question = Question::query()
                ->with([
                    'choices',
                    'competency',
                ])
                ->where(
                    'question_id',
                    $questionId
                )
                ->firstOrFail();

            $selected = $question
                ->choices
                ->firstWhere(
                    'choice_id',
                    $choiceId
                );

            if (! $selected) {
                throw new InvalidArgumentException(
                    'Selected choice does not belong to this question.'
                );
            }

            /*
             * Server-side response timing.
             */
            $responseSeconds =
                $this->computeResponseSeconds(
                    $session
                );

            /*
             * Reading threshold includes both the question stem
             * and visible answer choices.
             */
            $minimumSeconds =
                $this->minReadingTime(
                    $question
                );

            $isSpeedFlagged =
                $responseSeconds < $minimumSeconds;

            $isCorrect =
                (bool) $selected->is_correct;

            $competency =
                $question->competency;

            // -----------------------------------------------------
            // BKT STATE
            // -----------------------------------------------------

            /*
             * Lock an existing state before modifying it.
             */
            $state = UserKnowledgeState::query()
                ->where(
                    'user_id',
                    $user->user_id
                )
                ->where(
                    'competency_id',
                    $competency->competency_id
                )
                ->lockForUpdate()
                ->first();

            if (! $state) {
                $state = UserKnowledgeState::create([
                    'user_id' => $user->user_id,

                    'competency_id' => $competency->competency_id,

                    'current_mastery_p_l' => BktService::DEFAULT_INITIAL_MASTERY,

                    'total_attempts' => 0,

                    'total_correct' => 0,

                    'last_evaluated_at' => now(),
                ]);
            }

            $prior =
                (float) $state->current_mastery_p_l;

            $posterior =
                $this->bkt->updateMastery(
                    priorMastery: $prior,
                    isCorrect: $isCorrect,
                    pTransit: (float) $competency
                        ->bkt_transition_p_t,
                );

            // -----------------------------------------------------
            // TELEMETRY
            // -----------------------------------------------------

            $log = ResponseTelemetryLog::create([
                'session_id' => $session->session_id,

                'user_id' => $user->user_id,

                'question_id' => $question->question_id,

                'competency_id' => $competency->competency_id,

                'selected_choice_id' => $selected->choice_id,

                'is_correct' => $isCorrect,

                'response_time_seconds' => $responseSeconds,

                'item_position' => $session->total_items + 1,

                'is_speed_flagged' => $isSpeedFlagged,

                'prior_p_l' => $prior,

                /*
                 * In CitiRx this is the final post-transition
                 * mastery returned by updateMastery().
                 */
                'posterior_p_l' => $posterior,
            ]);

            // -----------------------------------------------------
            // RX VAULT
            // -----------------------------------------------------

            $this->rxVault->handleResponse(
                $user,
                $question->question_id,
                $isCorrect,
                $isSpeedFlagged,
            );

            // -----------------------------------------------------
            // UPDATE KNOWLEDGE STATE
            // -----------------------------------------------------

            $state->current_mastery_p_l =
                $posterior;

            $state->total_attempts += 1;

            if ($isCorrect) {
                $state->total_correct += 1;
            }

            $state->last_evaluated_at =
                now();

            $state->save();

            // -----------------------------------------------------
            // ANSWER XP
            // -----------------------------------------------------

            $answerXp = 0;

            /*
             * Speed-flagged responses still update BKT,
             * but never earn answer XP.
             */
            if (
                ! $isSpeedFlagged
                && $isCorrect
            ) {
                $answerXp =
                    self::XP_CORRECT;

                $this->awardXp(
                    $user,
                    'response_telemetry_log',
                    $log->log_id,
                    self::XP_CORRECT,
                    self::REASON_CORRECT,
                );
            }

            // -----------------------------------------------------
            // SESSION COUNTERS
            // -----------------------------------------------------

            $session->total_items += 1;

            if ($isCorrect) {
                $session->correct_items += 1;
            }

            // -----------------------------------------------------
            // READINESS
            // -----------------------------------------------------

            /*
             * State has already been saved, so readiness sees
             * the newest mastery.
             */
            $user->predicted_readiness_pct =
                $this->readiness->compute(
                    $user
                );

            // -----------------------------------------------------
            // FINAL ANSWER?
            // -----------------------------------------------------

            $bonuses = [
                'accuracy_xp' => 0,

                'completion_xp' => 0,

                'streak_incremented' => false,
            ];

            $targetReached =
                $session->total_items
                >= (int) $session->target_length;

            if ($targetReached) {
                $bonuses =
                    $this->finalizeInsideTransaction(
                        $user,
                        $session,
                        targetReached: true,
                    );

                $session->completed_at =
                    now();
            }

            /*
             * Current question has now been answered.
             */
            $session->current_question_id =
                null;

            $session->current_question_started_at =
                null;

            /*
             * xp_awarded represents ALL XP produced by
             * this assessment session, including bonuses.
             */
            $sessionXp =
                $answerXp
                + $bonuses['accuracy_xp']
                + $bonuses['completion_xp'];

            $session->xp_awarded +=
                $sessionXp;

            $session->save();

            // -----------------------------------------------------
            // USER CACHED XP
            // -----------------------------------------------------

            $user->total_xp +=
                $sessionXp;

            $user->save();

            // -----------------------------------------------------
            // BADGES
            // -----------------------------------------------------

            /*
            * Only evaluate badges when this answer
            * actually completed the Practice session.
            */
            if ($targetReached) {
                $this->badges->evaluateAndAward(
                    $user
                );
            }

            /*
            * Sync after all XP for this answer has been recorded.
            *
            * targetReached also covers badge XP that may have been
            * awarded using a separately loaded User model.
            */

            $levelUp = (
                $sessionXp > 0
                || $targetReached
            )
                ? $this->levels->sync($user)
                : null;

            return [
                'is_correct' => $isCorrect,

                'is_speed_flagged' => $isSpeedFlagged,

                'correct_choice_id' => $question
                    ->choices
                    ->firstWhere(
                        'is_correct',
                        true
                    )?->choice_id,

                'rationale' => $question
                    ->hypercorrection_rationale,

                'prior_mastery' => $prior,

                'posterior_mastery' => $posterior,

                'answer_xp' => $answerXp,

                'is_final' => $session->completed_at
                    !== null,

                'bonuses' => $bonuses,

                'response_seconds' => $responseSeconds,

                'minimum_seconds' => $minimumSeconds,

                'level_up' => $levelUp,
            ];
        });
    }

    // =============================================================
    // FINALIZE EARLY / POOL-EXHAUSTION SAFETY NET
    // =============================================================

    /**
     * Normally the final submitted answer completes the session.
     *
     * This method exists only for the edge case where
     * pickNextQuestion() cannot provide another question.
     */
    public function finalizeSession(
        AssessmentSession $session
    ): array {
        return DB::transaction(function () use ($session) {

            $session = AssessmentSession::query()
                ->where(
                    'session_id',
                    $session->session_id
                )
                ->lockForUpdate()
                ->firstOrFail();

            if ($session->session_type !== 'practice') {
                throw new LogicException(
                    'This session is not a practice session.'
                );
            }

            if ($session->completed_at !== null) {
                return [
                    'accuracy_xp' => 0,
                    'completion_xp' => 0,
                    'streak_incremented' => false,
                    'level_up' => null,
                ];
            }

            /*
             * An unanswered current question means the session
             * is NOT eligible for early finalization.
             */
            if ($session->current_question_id !== null) {
                throw new LogicException(
                    'The current practice question must be answered first.'
                );
            }

            $targetReached =
                $session->total_items
                >= (int) $session->target_length;

            /*
             * If target was not reached, verify that the question
             * pool really is exhausted.
             *
             * Tier 3 permits repeats, so this should only happen
             * when there are no active questions in scope.
             */
            if (! $targetReached) {
                $candidate =
                    $this->selectNextQuestion(
                        $session,
                        $session->served_question_ids ?? []
                    );

                if ($candidate !== null) {
                    throw new LogicException(
                        'This practice session still has available questions.'
                    );
                }
            }

            $user = User::query()
                ->where(
                    'user_id',
                    $session->user_id
                )
                ->lockForUpdate()
                ->firstOrFail();

            $bonuses =
                $this->finalizeInsideTransaction(
                    $user,
                    $session,
                    targetReached: $targetReached,
                );

            $session->completed_at =
                now();

            $session->current_question_id =
                null;

            $session->current_question_started_at =
                null;

            $bonusXp =
                $bonuses['accuracy_xp']
                + $bonuses['completion_xp'];

            $session->xp_awarded +=
                $bonusXp;

            $session->save();

            $user->total_xp +=
                $bonusXp;

            $user->save();

            // -----------------------------------------------------
            // BADGES
            // -----------------------------------------------------

            $this->badges->evaluateAndAward(
                $user
            );

            $bonuses['level_up'] =
                $this->levels->sync($user);

            return $bonuses;
        });
    }

    // =============================================================
    // FINALIZATION INTERNALS
    // =============================================================

    private function finalizeInsideTransaction(
        User $user,
        AssessmentSession $session,
        bool $targetReached,
    ): array {
        /*
         * Speed-flagged responses do NOT contribute to
         * streak/accuracy-bonus accuracy.
         */
        $eligible = ResponseTelemetryLog::query()
            ->where(
                'session_id',
                $session->session_id
            )
            ->where(
                'is_speed_flagged',
                false
            );

        $eligibleCount =
            (clone $eligible)->count();

        $eligibleCorrect =
            (clone $eligible)
                ->where(
                    'is_correct',
                    true
                )
                ->count();

        $accuracy =
            $eligibleCount > 0
            ? $eligibleCorrect
            / $eligibleCount
            : 0.0;

        $accuracyXp = 0;
        $completionXp = 0;
        $streakHit = false;

        // ---------------------------------------------------------
        // ACCURACY BONUS
        // ---------------------------------------------------------

        if (
            $eligibleCount
            >= self::STREAK_MIN_ELIGIBLE
            && $accuracy
            >= self::ACCURACY_BONUS_THRESHOLD
        ) {
            if (
                ! $this->bonusAlreadyAwarded(
                    $session,
                    self::REASON_ACCURACY_BONUS
                )
            ) {
                $this->awardXp(
                    $user,
                    'assessment_session',
                    $session->session_id,
                    self::XP_ACCURACY_BONUS,
                    self::REASON_ACCURACY_BONUS,
                );

                $accuracyXp =
                    self::XP_ACCURACY_BONUS;
            }
        }

        // ---------------------------------------------------------
        // COMPLETION BONUS
        // ---------------------------------------------------------

        /*
         * Completion XP is only earned when the student's
         * chosen target length was actually reached.
         */
        if (
            $targetReached
            && ! $this->bonusAlreadyAwarded(
                $session,
                self::REASON_COMPLETION_BONUS
            )
        ) {
            $this->awardXp(
                $user,
                'assessment_session',
                $session->session_id,
                self::XP_COMPLETION_BONUS,
                self::REASON_COMPLETION_BONUS,
            );

            $completionXp =
                self::XP_COMPLETION_BONUS;
        }

        // ---------------------------------------------------------
        // DAILY STREAK
        // ---------------------------------------------------------

        if (
            $eligibleCount
            >= self::STREAK_MIN_ELIGIBLE
            && $accuracy
            >= self::STREAK_ACCURACY_THRESHOLD
        ) {
            $streakHit =
                $this->updateStreak(
                    $user
                );
        }

        return [
            'accuracy_xp' => $accuracyXp,

            'completion_xp' => $completionXp,

            'streak_incremented' => $streakHit,
        ];
    }

    // =============================================================
    // XP
    // =============================================================

    private function awardXp(
        User $user,
        string $sourceType,
        string $sourceId,
        int $delta,
        string $reason,
    ): XpTransaction {
        return XpTransaction::create([
            'user_id' => $user->user_id,

            'source_type' => $sourceType,

            'source_id' => $sourceId,

            'xp_delta' => $delta,

            'reason' => $reason,
        ]);
    }

    /**
     * Exact event name matching — no fuzzy LIKE queries.
     */
    private function bonusAlreadyAwarded(
        AssessmentSession $session,
        string $reason,
    ): bool {
        return XpTransaction::query()
            ->where(
                'source_type',
                'assessment_session'
            )
            ->where(
                'source_id',
                $session->session_id
            )
            ->where(
                'reason',
                $reason
            )
            ->exists();
    }

    // =============================================================
    // STREAK
    // =============================================================

    private function updateStreak(
        User $user
    ): bool {
        /*
         * For the current version of CitiRx,
         * last_active_date means:
         *
         * "last qualifying streak day"
         *
         * TODO later:
         * create a dedicated last_streak_date column if
         * last_active_date becomes general activity tracking.
         */
        $today =
            now()->toDateString();

        if (
            $user->last_active_date
                ?->toDateString()
            === $today
        ) {
            /*
             * Already received today's streak credit.
             */
            return false;
        }

        $yesterday =
            now()
                ->subDay()
                ->toDateString();

        if (
            $user->last_active_date
                ?->toDateString()
            === $yesterday
        ) {
            $user->streak_count += 1;
        } else {
            $user->streak_count = 1;
        }

        $user->last_active_date =
            $today;

        return true;
    }

    // =============================================================
    // VALIDATION
    // =============================================================

    private function assertCanAnswer(
        User $user,
        AssessmentSession $session,
        string $questionId,
    ): void {
        if (
            $session->user_id
            !== $user->user_id
        ) {
            throw new LogicException(
                'This practice session does not belong to you.'
            );
        }

        if (
            $session->session_type
            !== 'practice'
        ) {
            throw new LogicException(
                'This session is not a practice session.'
            );
        }

        if (
            $session->completed_at
            !== null
        ) {
            throw new LogicException(
                'This practice session is already complete.'
            );
        }

        if (
            $session->current_question_id
            !== $questionId
        ) {
            throw new LogicException(
                'This is not the current practice question.'
            );
        }
    }

    // =============================================================
    // RESPONSE TIMING
    // =============================================================

    private function computeResponseSeconds(
        AssessmentSession $session
    ): float {
        $started =
            $session->current_question_started_at;

        if (! $started) {
            return 0.0;
        }

        /*
     * Do not use abs(). A timestamp accidentally set in the
     * future should produce zero instead of a large duration.
     */
        $milliseconds = max(
            0,
            now()->getTimestampMs()
                - $started->getTimestampMs()
        );

        $seconds =
            $milliseconds / 1000;

        /*
     * Prevent an abandoned browser tab from overflowing
     * response_telemetry_logs.response_time_seconds.
     */
        return round(
            min(
                $seconds,
                self::MAX_RESPONSE_SECONDS
            ),
            2
        );
    }

    /**
     * CitiRx heuristic:
     *
     * T_min =
     * max(
     *     5 seconds,
     *     visible word count / 250 WPM × 60
     * )
     *
     * Includes question stem AND answer choices.
     */
    private function minReadingTime(
        Question $question
    ): float {
        $visibleText =
            $question->question_text
            .' '
            .$question
                ->choices
                ->pluck('choice_text')
                ->implode(' ');

        $wordCount =
            str_word_count(
                strip_tags($visibleText)
            );

        $seconds =
            (
                $wordCount
                / self::WORDS_PER_MINUTE
            ) * 60;

        return max(
            self::MIN_READING_SECONDS,
            $seconds
        );
    }

    // =============================================================
    // ADAPTIVE QUESTION SELECTION
    // =============================================================
    /**
     * Build the question pool for the selected Practice mode.
     */
    private function questionPoolQuery(
        string $userId,
        string $mode,
        ?int $domainFilterId,
    ): Builder {
        $query = Question::query()
            ->where('is_active', true)
            ->whereNull('research_form');

        if ($mode === self::MODE_MISTAKES) {
            return $query->whereIn(
                'question_id',
                RxVault::query()
                    ->select('question_id')
                    ->where('user_id', $userId)
                    ->where('is_cleared', false)
            );
        }

        if ($mode === self::MODE_BOOKMARKS) {
            return $query->whereIn(
                'question_id',
                QuestionBookmark::query()
                    ->select('question_id')
                    ->where('user_id', $userId)
            );
        }

        if ($mode !== self::MODE_ADAPTIVE) {
            throw new InvalidArgumentException(
                'The Practice session contains an invalid mode.'
            );
        }

        if ($domainFilterId !== null) {
            $query->whereHas(
                'competency',
                fn (Builder $competencyQuery) => $competencyQuery->where(
                    'domain_id',
                    $domainFilterId
                )
            );
        }

        return $query;
    }

    /**
     * Select the next question while avoiding unnecessary
     * per-competency database queries.
     */
    private function selectNextQuestion(
        AssessmentSession $session,
        array $servedIds,
    ): ?Question {
        $recentIds = ResponseTelemetryLog::query()
            ->where('user_id', $session->user_id)
            ->latest('created_at')
            ->limit(self::RECENT_EXCLUSION_LIMIT)
            ->pluck('question_id')
            ->all();

        $mode = $session->practice_mode
            ?: self::MODE_ADAPTIVE;

        /*
     * Tier 1:
     * Exclude current-session and recently answered questions.
     *
     * Tier 2:
     * Exclude only current-session questions.
     *
     * Tier 3:
     * Allow current-session repeats, but only for adaptive Practice.
     *
     * Targeted drills intentionally stop after every scoped
     * question has appeared once. This prevents a single bookmark
     * from repeating ten times and producing excessive XP.
     */
        $exclusionTiers = [
            array_values(array_unique([
                ...$servedIds,
                ...$recentIds,
            ])),

            array_values(array_unique($servedIds)),
        ];

        if ($mode === self::MODE_ADAPTIVE) {
            $exclusionTiers[] = [];
        }

        /*
     * Avoid repeating an identical query when two tiers
     * happen to contain the same exclusions.
     */
        $seenTiers = [];

        foreach ($exclusionTiers as $excludedIds) {
            sort($excludedIds);

            $tierKey = implode('|', $excludedIds);

            if (isset($seenTiers[$tierKey])) {
                continue;
            }

            $seenTiers[$tierKey] = true;

            $query = $this->questionPoolQuery(
                $session->user_id,
                $mode,
                $session->domain_filter_id,
            );

            if ($excludedIds !== []) {
                $query->whereNotIn(
                    'question_id',
                    $excludedIds
                );
            }

            /*
         * Only fetch the two small columns required to perform
         * weighted selection. The complete chosen question is
         * fetched afterward.
         */
            $candidates = $query->get([
                'question_id',
                'competency_id',
            ]);

            if ($candidates->isEmpty()) {
                continue;
            }

            return $this->pickWeightedQuestion(
                $session->user_id,
                $candidates,
            );
        }

        return null;
    }

    /**
     * Weight candidate competencies according to current BKT mastery,
     * then return one complete Question model.
     */
    private function pickWeightedQuestion(
        string $userId,
        Collection $candidates,
    ): ?Question {
        $byCompetency = $candidates->groupBy(
            fn (Question $question) => (string) $question->competency_id
        );

        $masteries = UserKnowledgeState::query()
            ->where('user_id', $userId)
            ->whereIn(
                'competency_id',
                $byCompetency->keys()->all()
            )
            ->pluck(
                'current_mastery_p_l',
                'competency_id'
            );

        $weights = [];

        foreach ($byCompetency as $competencyId => $questions) {
            $mastery = (float) (
                $masteries[$competencyId]
                ?? BktService::DEFAULT_INITIAL_MASTERY
            );

            $mastery = max(
                0.0,
                min(1.0, $mastery)
            );

            $weights[(string) $competencyId] =
                (1.0 - $mastery) + 0.10;
        }

        $chosenCompetencyId =
            $this->weightedRandomPick($weights);

        if ($chosenCompetencyId === null) {
            return null;
        }

        $questions =
            $byCompetency->get($chosenCompetencyId);

        if (! $questions || $questions->isEmpty()) {
            return null;
        }

        $questionId =
            $questions->random()->question_id;

        return Question::query()
            ->where('is_active', true)
            ->find($questionId);
    }

    /**
     * Non-cryptographic weighted-random selection.
     *
     * @param  array<string, float>  $weights
     */
    private function weightedRandomPick(
        array $weights
    ): ?string {
        $total =
            array_sum(
                $weights
            );

        if ($total <= 0.0) {
            return null;
        }

        $roll =
            (
                mt_rand()
                / mt_getrandmax()
            ) * $total;

        $cumulative =
            0.0;

        foreach (
            $weights as $id => $weight
        ) {
            $cumulative +=
                $weight;

            if (
                $roll
                <= $cumulative
            ) {
                return (string) $id;
            }
        }

        $lastKey =
            array_key_last(
                $weights
            );

        return $lastKey !== null
            ? (string) $lastKey
            : null;
    }
}
