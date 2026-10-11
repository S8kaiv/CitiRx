<?php

namespace App\Http\Controllers;

use App\Models\AssessmentSession;
use App\Services\MockBoardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use LogicException;
use RuntimeException;

class MockBoardController extends Controller
{
    public function __construct(
        protected MockBoardService $mockBoard,
    ) {}

    /**
     * Display Mock Board availability and attempt status.
     */
    public function intro(Request $request): View
    {
        return view('mock_board.intro', [
            'mockBoardStatus' => $this->mockBoard->status(
                $request->user(),
            ),
        ]);
    }

    /**
     * Start or resume the official research post-test.
     */
    public function start(
        Request $request,
    ): RedirectResponse {
        try {
            $session = $this->mockBoard->startPostTest(
                $request->user(),
            );
        } catch (
            LogicException|RuntimeException $exception
        ) {
            return redirect()
                ->route('mock-board.intro')
                ->with(
                    'error',
                    $exception->getMessage(),
                );
        }

        return redirect()->route(
            'mock-board.take',
            [
                'session' => $session->session_id,
            ],
        );
    }

    /**
     * Display the active post-test.
     */
    public function take(
        Request $request,
        AssessmentSession $session,
    ): View|RedirectResponse {
        $this->authorizeSession(
            $request,
            $session,
        );

        $session->refresh();

        if ($session->completed_at !== null) {
            return redirect()->route(
                'mock-board.results',
                [
                    'session' => $session->session_id,
                ],
            );
        }

        /*
         * The browser timer is only a display aid.
         * The server makes the final expiration decision.
         */
        if (
            $session->expires_at !== null
            && now()->greaterThanOrEqualTo(
                $session->expires_at,
            )
        ) {
            try {
                $this->mockBoard->submit(
                    $session,
                    'expired',
                );
            } catch (
                InvalidArgumentException
                |LogicException
                |RuntimeException
                $exception
            ) {
                return redirect()
                    ->route('mock-board.intro')
                    ->with(
                        'error',
                        $exception->getMessage(),
                    );
            }

            return redirect()
                ->route(
                    'mock-board.results',
                    [
                        'session' => $session->session_id,
                    ],
                )
                ->with(
                    'status',
                    'Time expired. Your saved answers were submitted.',
                );
        }

        try {
            $data = $this->mockBoard->takeData(
                $session,
            );
        } catch (
            InvalidArgumentException
            |LogicException
            |RuntimeException
            $exception
        ) {
            return redirect()
                ->route('mock-board.intro')
                ->with(
                    'error',
                    $exception->getMessage(),
                );
        }

        return view('mock_board.take', [
            'session' => $session,
            'responses' => $data['responses'],
            'secondsRemaining' => $data['seconds_remaining'],
        ]);
    }

    /**
     * Autosave or clear one answer.
     */
    public function answer(
        Request $request,
        AssessmentSession $session,
        string $question,
    ): JsonResponse {
        $this->authorizeSession(
            $request,
            $session,
        );

        $validated = $request->validate([
            'selected_choice_id' => [
                'nullable',
                'uuid',
            ],
        ]);

        try {
            $this->mockBoard->saveAnswer(
                $request->user(),
                $session,
                $question,
                $validated[
                    'selected_choice_id'
                ] ?? null,
            );
        } catch (
            InvalidArgumentException $exception
        ) {
            return response()->json(
                [
                    'message' => $exception->getMessage(),
                ],
                422,
            );
        } catch (
            LogicException $exception
        ) {
            return response()->json(
                [
                    'message' => $exception->getMessage(),
                ],
                409,
            );
        }

        return response()->json(
            status: 204,
        );
    }

    /**
     * Manually submit the saved post-test responses.
     */
    public function submit(
        Request $request,
        AssessmentSession $session,
    ): RedirectResponse {
        $this->authorizeSession(
            $request,
            $session,
        );

        $session->refresh();

        /*
         * Repeated form submission should go to the existing result.
         */
        if ($session->completed_at !== null) {
            return redirect()->route(
                'mock-board.results',
                [
                    'session' => $session->session_id,
                ],
            );
        }

        try {
            /*
             * The service scores only persisted assessment_responses.
             * No browser answer array is accepted here.
             */
            $this->mockBoard->submit(
                $session,
                'manual',
            );
        } catch (
            InvalidArgumentException
            |LogicException
            |RuntimeException
            $exception
        ) {
            return back()->with(
                'error',
                $exception->getMessage(),
            );
        }

        return redirect()->route(
            'mock-board.results',
            [
                'session' => $session->session_id,
            ],
        );
    }

    /**
     * Display aggregate results without exposing answer keys.
     */
    public function results(
        Request $request,
        AssessmentSession $session,
    ): View|RedirectResponse {
        $this->authorizeSession(
            $request,
            $session,
        );

        $session->refresh();

        if ($session->completed_at === null) {
            return redirect()->route(
                'mock-board.take',
                [
                    'session' => $session->session_id,
                ],
            );
        }

        try {
            $result = $this->mockBoard->results(
                $session,
            );
        } catch (
            InvalidArgumentException
            |LogicException
            |RuntimeException
            $exception
        ) {
            return redirect()
                ->route('mock-board.intro')
                ->with(
                    'error',
                    $exception->getMessage(),
                );
        }

        return view('mock_board.results', [
            'session' => $session,
            'result' => $result,
        ]);
    }

    /**
     * Confirm ownership and ensure this is an official post-test.
     */
    private function authorizeSession(
        Request $request,
        AssessmentSession $session,
    ): void {
        abort_unless(
            $session->user_id
                === $request->user()->user_id,
            403,
        );

        abort_unless(
            $session->session_type
                === 'mock_board'
            && $session->research_phase
                === 'post_test',
            404,
        );
    }
}
