<?php

namespace App\Services;

use App\Models\AssessmentSession;
use App\Models\Question;
use App\Models\QuestionChoice;
use App\Models\ResponseTelemetryLog;
use App\Models\TosDomain;
use App\Models\User;
use App\Models\UserKnowledgeState;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use RuntimeException;

class DiagnosticService
{
    private const SUBJECT_COUNT = 6;

    private const QUESTIONS_PER_SUBJECT = 10;

    private const TOTAL_QUESTIONS = 60;

    public function __construct(
        protected BktService $bkt,
        protected ReadinessService $readiness,
        protected BadgeService $badges,
        protected LevelService $levels,
    ) {}

    /**
     * Pick up to $perDomain diagnostic questions from each PhLE domain.
     *
     * Questions are kept in a deterministic order:
     * domain -> created_at -> question_id.
     */
    public function pickQuestions(): Collection
    {
        $domains = TosDomain::query()
            ->orderBy('domain_number')
            ->get();

        if ($domains->count() !== self::SUBJECT_COUNT) {
            throw new RuntimeException(
                'The diagnostic requires exactly six official PhLE subjects.'
            );
        }

        $picked = collect();

        foreach ($domains as $domain) {
            $questions = Question::query()
                ->where('is_active', true)
                ->where(
                    'research_form',
                    Question::FORM_PRE_TEST_A
                )
                ->whereHas(
                    'competency',
                    fn ($query) => $query->where(
                        'domain_id',
                        $domain->domain_id
                    )
                )
                ->orderBy('form_position')
                ->get();

            if (
                $questions->count()
                !== self::QUESTIONS_PER_SUBJECT
            ) {
                throw new RuntimeException(
                    "{$domain->domain_name} requires exactly "
                        .self::QUESTIONS_PER_SUBJECT
                        .' approved Form A questions; found '
                        .$questions->count()
                        .'.'
                );
            }

            $picked = $picked->concat($questions);
        }

        $picked = $picked
            ->sortBy('form_position')
            ->values();

        if ($picked->count() !== self::TOTAL_QUESTIONS) {
            throw new RuntimeException(
                'Form A must contain exactly 60 questions.'
            );
        }

        $positions = $picked
            ->pluck('form_position')
            ->sort()
            ->values()
            ->all();

        if ($positions !== range(1, self::TOTAL_QUESTIONS)) {
            throw new RuntimeException(
                'Form A positions must be unique and cover 1 through 60.'
            );
        }

        return $picked;
    }

    /**
     * Start a diagnostic session.
     *
     * If the student already has an unfinished diagnostic,
     * return that session instead of creating another one.
     */
    public function startSession(
        User $user
    ): AssessmentSession {
        if ($user->is_diagnostic_completed) {
            throw new LogicException(
                'This user has already completed the diagnostic test.'
            );
        }

        $existingSession = AssessmentSession::query()
            ->where('user_id', $user->user_id)
            ->where('session_type', 'diagnostic')
            ->whereNull('completed_at')
            ->orderByDesc('started_at')
            ->first();

        if ($existingSession) {
            return $existingSession;
        }

        $questions = $this->pickQuestions();

        if ($questions->isEmpty()) {
            throw new RuntimeException(
                'No diagnostic questions are currently available.'
            );
        }

        return AssessmentSession::create([
            'user_id' => $user->user_id,
            'session_type' => 'diagnostic',
            'research_phase' => 'pre_test',
            'served_question_ids' => $questions
                ->pluck('question_id')
                ->values()
                ->all(),
            'draft_answers' => [],
            'total_items' => $questions->count(),
            'correct_items' => 0,
            'started_at' => now(),
        ]);
    }

    /**
     * Score a completed diagnostic test.
     *
     * Each answer must contain:
     *
     * [
     *     'question_id' => 'uuid',
     *     'selected_choice_id' => 'uuid',
     * ]
     *
     * The diagnostic establishes the student's initial mastery.
     * It does NOT apply the BKT learning-transition update.
     *
     * updateMastery() will be used later during practice.
     */
    public function score(
        AssessmentSession $session,
        array $answers
    ): array {
        return DB::transaction(function () use ($session, $answers) {

            /*
             * Reload and lock the session while scoring.
             * This helps prevent the same diagnostic from being
             * submitted twice at nearly the same time.
             */
            $session = AssessmentSession::query()
                ->where('session_id', $session->session_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($session->session_type !== 'diagnostic') {
                throw new InvalidArgumentException(
                    'The supplied assessment session is not a diagnostic session.'
                );
            }

            if ($session->completed_at !== null) {
                throw new LogicException(
                    'This diagnostic session has already been completed.'
                );
            }

            $servedQuestionIds = $session->served_question_ids ?? [];

            if (empty($servedQuestionIds)) {
                throw new RuntimeException(
                    'This diagnostic session has no served questions.'
                );
            }

            /*
             * Validate the basic answer structure.
             */
            foreach ($answers as $answer) {
                if (
                    ! is_array($answer)
                    || empty($answer['question_id'])
                    || empty($answer['selected_choice_id'])
                ) {
                    throw new InvalidArgumentException(
                        'Every answer must contain a question_id and selected_choice_id.'
                    );
                }
            }

            /*
             * Detect duplicate submitted question IDs.
             *
             * keyBy() alone would silently replace duplicate entries,
             * which we do not want.
             */
            $submittedQuestionIds = collect($answers)
                ->pluck('question_id');

            if (
                $submittedQuestionIds->unique()->count()
                !== $submittedQuestionIds->count()
            ) {
                throw new InvalidArgumentException(
                    'Duplicate question answers were submitted.'
                );
            }

            /*
             * Every served question must have exactly one answer.
             * Extra questions are also rejected.
             */
            $servedIds = collect($servedQuestionIds)
                ->map(fn ($id) => (string) $id)
                ->values();

            $submittedIds = $submittedQuestionIds
                ->map(fn ($id) => (string) $id)
                ->values();

            if (
                $servedIds->count() !== $submittedIds->count()
                || $servedIds->diff($submittedIds)->isNotEmpty()
                || $submittedIds->diff($servedIds)->isNotEmpty()
            ) {
                throw new InvalidArgumentException(
                    'Submitted answers do not match the questions served in this diagnostic.'
                );
            }

            /*
             * Turn submitted answers into:
             *
             * question UUID => answer data
             *
             * The loop below will STILL follow served_question_ids,
             * not browser submission order.
             */
            $answersByQuestion = collect($answers)
                ->keyBy('question_id');

            /*
             * Load only questions belonging to this diagnostic session.
             */
            $questions = Question::query()
                ->with('competency.domain')
                ->whereIn('question_id', $servedQuestionIds)
                ->get()
                ->keyBy('question_id');

            if ($questions->count() !== count($servedQuestionIds)) {
                throw new RuntimeException(
                    'One or more diagnostic questions could not be found.'
                );
            }

            $user = $session->user;

            $totalItems = 0;
            $correctItems = 0;

            $perDomain = [];
            $competencyStats = [];

            /*
             * Used only to create meaningful diagnostic telemetry.
             *
             * Diagnostic mastery is based on cumulative diagnostic
             * performance, not on P(T) learning transitions.
             */
            $runningCompetencyStats = [];

            /*
             * First pass:
             *
             * Validate each answer,
             * determine correctness,
             * record telemetry,
             * and collect competency/domain statistics.
             */
            foreach ($servedQuestionIds as $index => $questionId) {
                $question = $questions->get($questionId);

                if (! $question) {
                    throw new RuntimeException(
                        'A served diagnostic question could not be loaded.'
                    );
                }

                $answer = $answersByQuestion->get($questionId);

                if (! $answer) {
                    throw new InvalidArgumentException(
                        'An answer is missing for one of the served questions.'
                    );
                }

                /*
                 * IMPORTANT:
                 * The selected choice must belong to THIS question.
                 */
                $selectedChoice = QuestionChoice::query()
                    ->where(
                        'choice_id',
                        $answer['selected_choice_id']
                    )
                    ->where(
                        'question_id',
                        $question->question_id
                    )
                    ->first();

                if (! $selectedChoice) {
                    throw new InvalidArgumentException(
                        'The selected choice does not belong to its submitted question.'
                    );
                }

                $isCorrect = (bool) $selectedChoice->is_correct;

                $competencyId = $question->competency_id;

                /*
                 * Initialize competency counters.
                 */
                $competencyStats[$competencyId] ??= [
                    'total' => 0,
                    'correct' => 0,
                ];

                $runningCompetencyStats[$competencyId] ??= [
                    'total' => 0,
                    'correct' => 0,
                ];

                /*
                 * Diagnostic telemetry:
                 *
                 * prior = mastery estimate before this diagnostic item
                 * posterior = mastery estimate after including this item
                 *
                 * No P(T) transition is applied here.
                 */
                $running = $runningCompetencyStats[$competencyId];

                $prior = $this->bkt->initialPrior(
                    $running['correct'],
                    $running['total'],
                );

                $running['total'] += 1;

                if ($isCorrect) {
                    $running['correct'] += 1;
                }

                $posterior = $this->bkt->initialPrior(
                    $running['correct'],
                    $running['total'],
                );

                $runningCompetencyStats[$competencyId] = $running;

                ResponseTelemetryLog::create([
                    'session_id' => $session->session_id,
                    'user_id' => $user->user_id,
                    'question_id' => $question->question_id,
                    'competency_id' => $competencyId,
                    'selected_choice_id' => $selectedChoice->choice_id,
                    'is_correct' => $isCorrect,
                    'response_time_seconds' => 0,
                    'item_position' => $index + 1,
                    'is_speed_flagged' => false,
                    'prior_p_l' => $prior,
                    'posterior_p_l' => $posterior,
                ]);

                /*
                 * Final competency counters.
                 */
                $competencyStats[$competencyId]['total'] += 1;

                if ($isCorrect) {
                    $competencyStats[$competencyId]['correct'] += 1;
                }

                /*
                 * Per-domain results.
                 */
                $domain = $question->competency->domain;
                $domainId = $domain->domain_id;

                $perDomain[$domainId] ??= [
                    'domain_id' => $domainId,
                    'domain_name' => $domain->domain_name,
                    'total' => 0,
                    'correct' => 0,
                ];

                $perDomain[$domainId]['total'] += 1;

                if ($isCorrect) {
                    $perDomain[$domainId]['correct'] += 1;
                }

                $totalItems += 1;

                if ($isCorrect) {
                    $correctItems += 1;
                }
            }

            /*
             * Second pass:
             *
             * Establish initial mastery for each competency from
             * diagnostic correct / total.
             *
             * This is where initialPrior() is actually used for its
             * main purpose.
             */
            foreach ($competencyStats as $competencyId => $stats) {
                $initialMastery = $this->bkt->initialPrior(
                    $stats['correct'],
                    $stats['total'],
                );

                UserKnowledgeState::updateOrCreate(
                    [
                        'user_id' => $user->user_id,
                        'competency_id' => $competencyId,
                    ],
                    [
                        'current_mastery_p_l' => $initialMastery,
                        'total_attempts' => $stats['total'],
                        'total_correct' => $stats['correct'],
                        'last_evaluated_at' => now(),
                    ],
                );
            }

            /*
             * Finalize assessment session.
             */
            $session->total_items = $totalItems;
            $session->correct_items = $correctItems;
            $session->draft_answers = null;
            $session->completed_at = now();
            $session->save();

            /*
             * Mark diagnostic complete.
             */
            $user->predicted_readiness_pct = $this->readiness->compute($user);
            $user->is_diagnostic_completed = true;
            $user->save();
            $this->badges->evaluateAndAward(
                $user
            );
            $levelUp =
                $this->levels->sync($user);

            return [
                'total' => $totalItems,
                'correct' => $correctItems,
                'per_domain' => array_values($perDomain),
                'level_up' => $levelUp,
            ];
        });
    }

    public function saveDraftAnswer(
        User $user,
        AssessmentSession $session,
        string $questionId,
        string $choiceId,
    ): void {
        DB::transaction(function () use (
            $user,
            $session,
            $questionId,
            $choiceId
        ) {
            $session = AssessmentSession::query()
                ->where('session_id', $session->session_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($session->user_id !== $user->user_id) {
                throw new LogicException(
                    'This diagnostic session does not belong to you.'
                );
            }

            if ($session->session_type !== 'diagnostic') {
                throw new LogicException(
                    'This session is not a diagnostic session.'
                );
            }

            if ($session->completed_at !== null) {
                throw new LogicException(
                    'This diagnostic session is already complete.'
                );
            }

            $servedIds = $session->served_question_ids ?? [];

            if (! in_array($questionId, $servedIds, true)) {
                throw new InvalidArgumentException(
                    'This question is not part of the diagnostic session.'
                );
            }

            $choiceExists = QuestionChoice::query()
                ->where('choice_id', $choiceId)
                ->where('question_id', $questionId)
                ->exists();

            if (! $choiceExists) {
                throw new InvalidArgumentException(
                    'The selected choice does not belong to this question.'
                );
            }

            $answers = $session->draft_answers ?? [];

            $answers[$questionId] = $choiceId;

            $session->draft_answers = $answers;

            $session->save();
        });
    }
}
