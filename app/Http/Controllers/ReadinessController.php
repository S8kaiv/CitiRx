<?php

namespace App\Http\Controllers;

use App\Services\ReadinessService;
use App\Services\StudyRecommendationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReadinessController extends Controller
{
    public function __construct(
        protected ReadinessService $readiness,
        protected StudyRecommendationService $studyRecommendations,
    ) {}

    public function show(
        Request $request,
    ): View|RedirectResponse {
        $user = $request->user();

        if (! $user->is_diagnostic_completed) {
            return redirect()
                ->route('diagnostic.intro')
                ->with(
                    'status',
                    'Complete the diagnostic test first to establish your readiness estimate.',
                );
        }

        $breakdown =
            $this->readiness->breakdown(
                $user,
            );

        if (empty($breakdown['domains'])) {
            abort(
                500,
                'Readiness data could not be calculated for this completed diagnostic.',
            );
        }

        return view('readiness.show', [
            'user' => $user,

            'breakdown' => $breakdown,

            /*
             * Reuse the breakdown calculated above.
             * This avoids repeating its two database queries.
             */
            'studyRecommendation' => $this->studyRecommendations
                ->forUser(
                    $user,
                    $breakdown,
                ),
        ]);
    }
}
