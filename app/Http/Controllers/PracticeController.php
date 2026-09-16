<?php

namespace App\Http\Controllers;

use App\Models\AssessmentSession;
use App\Models\Question;
use App\Models\TosDomain;
use App\Services\PracticeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Throwable;

class PracticeController extends Controller
{
    public function __construct(
        protected PracticeService $practice,
    ) {}

    /**
     * Show the Practice Mode setup page.
     */
    public function intro(
        Request $request
    ): View|RedirectResponse {
        $user = $request->user();

        /*
         * Practice Mode requires a completed diagnostic
         * because the adaptive engine depends on the
         * student's initial mastery states.
         */
        if (! $user->is_diagnostic_completed) {
            return redirect()
                ->route('diagnostic.intro')
                ->with(
                    'status',
                    'Complete the diagnostic test before starting practice.'
                );
        }

        $domains = TosDomain::query()
            ->orderBy('domain_number')
            ->get();

        return view('practice.intro', [
            'domains' => $domains,

            'allowedLengths' => PracticeService::ALLOWED_LENGTHS,
        ]);
    }

    /**
     * Create or resume a Practice session.
     */
    public function start(
        Request $request
    ): RedirectResponse {
        $validated = $request->validate([
            'length' => [
                'required',
                'integer',
                'in:'.implode(
                    ',',
                    PracticeService::ALLOWED_LENGTHS
                ),
            ],

            'domain_filter_id' => [
                'nullable',
                'integer',
                'exists:tos_domains,domain_id',
            ],
        ]);

        try {
            $session =
                $this->practice->startSession(
                    $request->user(),
                    (int) $validated['length'],
                    isset(
                        $validated['domain_filter_id']
                    )
                        ? (int) $validated['domain_filter_id']
                        : null,
                );
        } catch (
            LogicException|
            RuntimeException|
            InvalidArgumentException $e
        ) {
            return redirect()
                ->route('practice.intro')
                ->withInput()
                ->with(
                    'error',
                    $e->getMessage()
                );
        }

        return redirect()->route(
            'practice.show',
            [
                'session' => $session->session_id,
            ]
        );
    }

    /**
     * Display either:
     *
     * 1. feedback from the previous answer, or
     * 2. the current Practice question.
     */
    public function show(
        Request $request,
        AssessmentSession $session
    ): View|RedirectResponse {
        $this->authorizeSession(
            $request,
            $session
        );

        /*
         * Always reload the latest session state.
         */
        $session = $session->fresh();

        /*
         * Completed sessions belong on the summary page.
         */
        if ($session->completed_at !== null) {
            return redirect()->route(
                'practice.summary',
                [
                    'session' => $session->session_id,
                ]
            );
        }

        /*
         * Check for feedback flashed by answer().
         */
        $feedback =
            session('practice_feedback');

        $answeredQuestionId =
            session(
                'practice_answered_question_id'
            );

        if (
            $feedback &&
            $answeredQuestionId
        ) {
            $answeredQuestion =
                Question::query()
                    ->with([
                        'choices' => fn ($query) => $query->orderBy(
                            'choice_letter'
                        ),

                        'competency.domain',
                    ])
                    ->findOrFail(
                        $answeredQuestionId
                    );

            return view(
                'practice.show',
                [
                    'session' => $session,

                    'feedback' => $feedback,

                    'answeredQuestion' => $answeredQuestion,

                    'question' => null,
                ]
            );
        }

        try {
            /*
             * Resume an already assigned unanswered
             * question whenever one exists.
             */
            if (
                $session->current_question_id
                !== null
            ) {
                $question =
                    Question::query()
                        ->with([
                            'choices' => fn ($query) => $query->orderBy(
                                'choice_letter'
                            ),

                            'competency.domain',
                        ])
                        ->find(
                            $session
                                ->current_question_id
                        );

                if (! $question) {
                    throw new RuntimeException(
                        'The current Practice question could not be loaded.'
                    );
                }
            } else {
                /*
                 * No unanswered question exists,
                 * so let PracticeService adaptively
                 * select the next one.
                 */
                $question =
                    $this->practice
                        ->pickNextQuestion(
                            $session
                        );

                if ($question) {
                    $question->load([
                        'choices' => fn ($query) => $query->orderBy(
                            'choice_letter'
                        ),

                        'competency.domain',
                    ]);
                }
            }

            /*
             * A null result normally means the target
             * was reached or the usable pool was
             * genuinely exhausted.
             */
            if (! $question) {
                $session =
                    $session->fresh();

                if (
                    $session->completed_at
                    === null
                ) {
                    $this->practice
                        ->finalizeSession(
                            $session
                        );
                }

                return redirect()->route(
                    'practice.summary',
                    [
                        'session' => $session->session_id,
                    ]
                );
            }
        } catch (
            LogicException|
            RuntimeException|
            InvalidArgumentException $e
        ) {
            return redirect()
                ->route('practice.intro')
                ->with(
                    'error',
                    $e->getMessage()
                );
        }

        return view(
            'practice.show',
            [
                'session' => $session->fresh(),

                'feedback' => null,

                'answeredQuestion' => null,

                'question' => $question,
            ]
        );
    }

    /**
     * Submit one Practice answer.
     */
    public function answer(
        Request $request,
        AssessmentSession $session
    ): RedirectResponse {
        $this->authorizeSession(
            $request,
            $session
        );

        $session =
            $session->fresh();

        if ($session->completed_at !== null) {
            return redirect()->route(
                'practice.summary',
                [
                    'session' => $session->session_id,
                ]
            );
        }

        $validated =
            $request->validate([
                'question_id' => [
                    'required',
                    'uuid',
                ],

                'selected_choice_id' => [
                    'required',
                    'uuid',
                ],
            ]);

        try {
            $feedback =
                $this->practice
                    ->submitAnswer(
                        $request->user(),
                        $session,
                        $validated['question_id'],
                        $validated['selected_choice_id'],
                    );
        } catch (
            LogicException|
            InvalidArgumentException|
            RuntimeException $e
        ) {
            return redirect()
                ->route(
                    'practice.show',
                    [
                        'session' => $session->session_id,
                    ]
                )
                ->withInput()
                ->with(
                    'error',
                    $e->getMessage()
                );
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route(
                    'practice.show',
                    [
                        'session' => $session->session_id,
                    ]
                )
                ->withInput()
                ->with(
                    'error',
                    'Could not record your answer. Please try again.'
                );
        }

        /*
         * submitAnswer() finalizes the session when
         * the target question count has been reached.
         *
         * For the last answer, go directly to summary.
         */
        if ($feedback['is_final']) {
            return redirect()->route(
                'practice.summary',
                [
                    'session' => $session->session_id,
                ]
            );
        }

        /*
         * For non-final answers, show feedback before
         * selecting the next question.
         */
        return redirect()
            ->route(
                'practice.show',
                [
                    'session' => $session->session_id,
                ]
            )
            ->with(
                'practice_feedback',
                $feedback
            )
            ->with(
                'practice_answered_question_id',
                $validated['question_id']
            );
    }

    /**
     * Move forward after viewing answer feedback.
     */
    public function next(
        Request $request,
        AssessmentSession $session
    ): RedirectResponse {
        $this->authorizeSession(
            $request,
            $session
        );

        $session =
            $session->fresh();

        if ($session->completed_at !== null) {
            return redirect()->route(
                'practice.summary',
                [
                    'session' => $session->session_id,
                ]
            );
        }

        /*
         * Defensive protection:
         * never replace an unanswered question.
         */
        if (
            $session->current_question_id
            !== null
        ) {
            return redirect()->route(
                'practice.show',
                [
                    'session' => $session->session_id,
                ]
            );
        }

        try {
            $question =
                $this->practice
                    ->pickNextQuestion(
                        $session
                    );

            if (! $question) {
                $session =
                    $session->fresh();

                if (
                    $session->completed_at
                    === null
                ) {
                    $this->practice
                        ->finalizeSession(
                            $session
                        );
                }

                return redirect()->route(
                    'practice.summary',
                    [
                        'session' => $session->session_id,
                    ]
                );
            }
        } catch (
            LogicException|
            RuntimeException|
            InvalidArgumentException $e
        ) {
            return redirect()
                ->route(
                    'practice.show',
                    [
                        'session' => $session->session_id,
                    ]
                )
                ->with(
                    'error',
                    $e->getMessage()
                );
        }

        return redirect()->route(
            'practice.show',
            [
                'session' => $session->session_id,
            ]
        );
    }

    /**
     * Display the completed Practice summary.
     */
    public function summary(
        Request $request,
        AssessmentSession $session
    ): View|RedirectResponse {
        $this->authorizeSession(
            $request,
            $session
        );

        $session =
            $session->fresh();

        if ($session->completed_at === null) {
            return redirect()->route(
                'practice.show',
                [
                    'session' => $session->session_id,
                ]
            );
        }

        $logs =
            $session
                ->telemetryLogs()
                ->with(
                    'question.competency.domain'
                )
                ->orderBy('item_position')
                ->get();

        /*
         * Raw accuracy includes every submitted answer.
         */
        $rawAccuracy =
            $session->total_items > 0
            ? round(
                100
                    * $session->correct_items
                    / $session->total_items,
                2
            )
            : 0.0;

        /*
         * Eligible accuracy excludes responses that
         * were speed-flagged.
         *
         * This is the accuracy used by the Practice
         * XP / streak rules.
         */
        $eligible =
            $logs->filter(
                fn ($log) => ! (bool)
                $log->is_speed_flagged
            );

        $eligibleCount =
            $eligible->count();

        $eligibleCorrect =
            $eligible->filter(
                fn ($log) => (bool)
                $log->is_correct
            )->count();

        $eligibleAccuracy =
            $eligibleCount > 0
            ? round(
                100
                    * $eligibleCorrect
                    / $eligibleCount,
                2
            )
            : 0.0;

        /*
         * Raw domain breakdown.
         */
        $perDomain =
            $logs
                ->groupBy(
                    fn ($log) => $log
                        ->question
                        ->competency
                        ->domain
                        ->domain_id
                )
                ->map(
                    function ($group) {
                        $domain =
                            $group
                                ->first()
                                ->question
                                ->competency
                                ->domain;

                        return [
                            'domain_id' => $domain->domain_id,

                            'domain_number' => $domain->domain_number,

                            'domain_name' => $domain->domain_name,

                            'total' => $group->count(),

                            'correct' => $group
                                ->filter(
                                    fn ($log) => (bool)
                                    $log
                                        ->is_correct
                                )
                                ->count(),
                        ];
                    }
                )
                ->sortBy(
                    'domain_number'
                )
                ->values()
                ->all();

        /*
         * Use the student's current cached readiness
         * rather than depending on an AssessmentSession
         * ->user relationship in the Blade view.
         */
        $currentReadiness =
            (float)
            $request
                ->user()
                ->fresh()
                ->predicted_readiness_pct;

        return view(
            'practice.summary',
            [
                'session' => $session,

                'logs' => $logs,

                'rawAccuracy' => $rawAccuracy,

                'eligibleCount' => $eligibleCount,

                'eligibleCorrect' => $eligibleCorrect,

                'eligibleAccuracy' => $eligibleAccuracy,

                'perDomain' => $perDomain,

                'currentReadiness' => $currentReadiness,
            ]
        );
    }

    /**
     * Make sure the logged-in student owns the
     * requested Practice session.
     */
    private function authorizeSession(
        Request $request,
        AssessmentSession $session
    ): void {
        if (
            $session->user_id
            !== $request->user()->user_id
        ) {
            abort(
                403,
                'This practice session does not belong to you.'
            );
        }

        if (
            $session->session_type
            !== 'practice'
        ) {
            abort(
                404,
                'Not a practice session.'
            );
        }
    }
}
