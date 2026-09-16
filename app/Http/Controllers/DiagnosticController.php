<?php

namespace App\Http\Controllers;

use App\Models\AssessmentSession;
use App\Models\Question;
use App\Services\DiagnosticService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Throwable;

class DiagnosticController extends Controller
{
    public function __construct(
        protected DiagnosticService $diagnostic,
    ) {}

    /**
     * Show the diagnostic intro page.
     */
    public function intro(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        /*
         * Already completed the diagnostic.
         */
        if ($user->is_diagnostic_completed) {
            return redirect()
                ->route('dashboard')
                ->with(
                    'status',
                    'You have already completed the diagnostic test.'
                );
        }

        /*
         * Resume an unfinished diagnostic if one exists.
         */
        $inProgress = AssessmentSession::query()
            ->where('user_id', $user->user_id)
            ->where('session_type', 'diagnostic')
            ->whereNull('completed_at')
            ->orderByDesc('started_at')
            ->first();

        if ($inProgress) {
            return redirect()->route(
                'diagnostic.take',
                ['session' => $inProgress->session_id]
            );
        }

        return view('diagnostic.intro');
    }

    /**
     * Create or resume a diagnostic session.
     */
    public function start(Request $request): RedirectResponse
    {
        $user = $request->user();

        try {
            $session = $this->diagnostic->startSession($user);
        } catch (LogicException $e) {
            return redirect()
                ->route('dashboard')
                ->with('status', $e->getMessage());
        } catch (RuntimeException $e) {
            return redirect()
                ->route('diagnostic.intro')
                ->with('error', $e->getMessage());
        }

        return redirect()->route(
            'diagnostic.take',
            ['session' => $session->session_id]
        );
    }

    /**
     * Display the diagnostic questions.
     */
    public function take(
        Request $request,
        AssessmentSession $session
    ): View|RedirectResponse {
        $this->authorizeSession(
            $request,
            $session
        );

        /*
     * Reload the latest session data from the database.
     * This makes sure draft_answers contains the most
     * recently autosaved answers.
     */
        $session->refresh();

        /*
     * Completed diagnostics go directly to results.
     */
        if ($session->completed_at !== null) {
            return redirect()->route(
                'diagnostic.results',
                [
                    'session' => $session->session_id,
                ]
            );
        }

        /*
     * Load the exact diagnostic question set
     * in its original served order.
     */
        $questions =
            $this->loadServedQuestions(
                $session
            );

        /*
     * Pass saved draft answers to the Blade view
     * so Alpine can restore the student's progress.
     */
        return view('diagnostic.take', [
            'session' => $session,

            'questions' => $questions,

            'savedAnswers' => $session->draft_answers ?? [],
        ]);
    }

    /**
     * Submit and score the diagnostic.
     */
    public function submit(
        Request $request,
        AssessmentSession $session
    ): RedirectResponse {
        $this->authorizeSession($request, $session);

        /*
         * Defensive protection against duplicate submissions.
         */
        if ($session->completed_at !== null) {
            return redirect()->route(
                'diagnostic.results',
                ['session' => $session->session_id]
            );
        }

        /*
         * Browser sends:
         *
         * answers[question_uuid] = choice_uuid
         */
        $validated = $request->validate([
            'answers' => ['required', 'array', 'min:1'],
            'answers.*' => ['required', 'uuid'],
        ]);

        /*
         * DiagnosticService expects:
         *
         * [
         *     [
         *         'question_id' => '...',
         *         'selected_choice_id' => '...',
         *     ],
         * ]
         */
        $answers = [];

        foreach ($validated['answers'] as $questionId => $choiceId) {
            $answers[] = [
                'question_id' => (string) $questionId,
                'selected_choice_id' => (string) $choiceId,
            ];
        }

        try {
            $this->diagnostic->score(
                $session,
                $answers
            );
        } catch (
            InvalidArgumentException
            |LogicException
            |RuntimeException $e
        ) {
            return redirect()
                ->route(
                    'diagnostic.take',
                    ['session' => $session->session_id]
                )
                ->withInput()
                ->with('error', $e->getMessage());
        } catch (Throwable $e) {
            /*
             * Unexpected framework / database / programming error.
             *
             * Log the real exception but do not expose internal
             * information to the student.
             */
            report($e);

            return redirect()
                ->route(
                    'diagnostic.take',
                    ['session' => $session->session_id]
                )
                ->withInput()
                ->with(
                    'error',
                    'Could not score your diagnostic. Please try again.'
                );
        }

        return redirect()->route(
            'diagnostic.results',
            ['session' => $session->session_id]
        );
    }

    /**
     * Display historical diagnostic results.
     */
    public function results(
        Request $request,
        AssessmentSession $session
    ): View|RedirectResponse {
        $this->authorizeSession($request, $session);

        /*
         * Cannot view results until the diagnostic is completed.
         */
        if ($session->completed_at === null) {
            return redirect()->route(
                'diagnostic.take',
                ['session' => $session->session_id]
            );
        }

        /*
         * Use telemetry from THIS diagnostic session.
         *
         * We intentionally do NOT read the student's current
         * UserKnowledgeState here because practice mode will later
         * change those values.
         *
         * Diagnostic results should remain historical.
         */
        $logs = $session->telemetryLogs()
            ->with('question.competency.domain')
            ->orderBy('item_position')
            ->get();

        if ($logs->isEmpty()) {
            abort(
                500,
                'No response data was found for this diagnostic session.'
            );
        }

        /*
         * Domain results.
         */
        $perDomain = $logs
            ->groupBy(
                fn ($log) => $log->question->competency->domain->domain_id
            )
            ->map(function ($group) {
                $domain =
                    $group->first()
                        ->question
                        ->competency
                        ->domain;

                return [
                    'domain_id' => $domain->domain_id,
                    'domain_name' => $domain->domain_name,
                    'domain_number' => $domain->domain_number,
                    'total' => $group->count(),
                    'correct' => $group
                        ->filter(
                            fn ($log) => (bool) $log->is_correct
                        )
                        ->count(),
                ];
            })
            ->sortBy('domain_number')
            ->values()
            ->all();

        /*
         * Historical initial mastery results.
         *
         * The last diagnostic telemetry entry for each competency
         * contains that competency's final initialPrior() estimate.
         */
        $masteryResults = $logs
            ->groupBy('competency_id')
            ->map(function ($group) {
                $ordered = $group
                    ->sortBy('item_position')
                    ->values();

                $last = $ordered->last();

                $competency =
                    $last->question->competency;

                return [
                    'competency_id' => $competency->competency_id,

                    'competency_title' => $competency->title,

                    'domain_name' => $competency->domain->domain_name,

                    'domain_number' => $competency->domain->domain_number,

                    'order_index' => $competency->order_index,

                    'mastery' => (float) $last->posterior_p_l,

                    'total' => $group->count(),

                    'correct' => $group
                        ->filter(
                            fn ($log) => (bool) $log->is_correct
                        )
                        ->count(),
                ];
            })
            ->sortBy(
                fn ($row) => sprintf(
                    '%02d-%02d',
                    $row['domain_number'],
                    $row['order_index']
                )
            )
            ->values();

        return view('diagnostic.results', [
            'session' => $session,
            'perDomain' => $perDomain,
            'masteryResults' => $masteryResults,
        ]);
    }

    // -------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------

    /**
     * Make sure the logged-in student owns this diagnostic.
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
                'This diagnostic session does not belong to you.'
            );
        }

        if ($session->session_type !== 'diagnostic') {
            abort(
                404,
                'Not a diagnostic session.'
            );
        }
    }

    /**
     * Load the exact questions stored in served_question_ids
     * while preserving their original order.
     */
    private function loadServedQuestions(
        AssessmentSession $session
    ): Collection {
        $servedIds =
            $session->served_question_ids ?? [];

        if (empty($servedIds)) {
            abort(
                500,
                'This diagnostic session has no served questions.'
            );
        }

        $questions = Question::query()
            ->with([
                'choices' => fn ($query) => $query->orderBy('choice_letter'),

                'competency.domain',
            ])
            ->whereIn(
                'question_id',
                $servedIds
            )
            ->get()
            ->keyBy('question_id');

        /*
         * Do not silently hide a deleted/missing question.
         */
        if (
            $questions->count()
            !== count($servedIds)
        ) {
            abort(
                500,
                'One or more questions from this diagnostic session could not be loaded.'
            );
        }

        /*
         * SQL does not guarantee the same order as whereIn().
         *
         * Rebuild using served_question_ids.
         */
        return collect($servedIds)
            ->map(
                fn ($id) => $questions->get($id)
            )
            ->values();
    }

    public function saveAnswer(
        Request $request,
        AssessmentSession $session
    ): JsonResponse {
        $this->authorizeSession(
            $request,
            $session
        );

        $validated = $request->validate([
            'question_id' => [
                'required',
                'uuid',
            ],

            'choice_id' => [
                'required',
                'uuid',
            ],
        ]);

        try {
            $this->diagnostic->saveDraftAnswer(
                $request->user(),
                $session,
                $validated['question_id'],
                $validated['choice_id'],
            );

            return response()->json([
                'saved' => true,
            ]);
        } catch (
            InvalidArgumentException|
            LogicException|
            RuntimeException $e
        ) {
            return response()->json([
                'saved' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
