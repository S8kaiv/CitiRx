<?php

namespace App\Http\Controllers;

use App\Services\ReadinessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReadinessController extends Controller
{
    public function __construct(
        protected ReadinessService $readiness,
    ) {}

    public function show(
        Request $request
    ): View|RedirectResponse {
        $user = $request->user();

        /*
         * Do not label an untested student as 0% At Risk.
         */
        if (! $user->is_diagnostic_completed) {
            return redirect()
                ->route('diagnostic.intro')
                ->with(
                    'status',
                    'Complete the diagnostic test first to establish your readiness estimate.'
                );
        }

        $breakdown =
            $this->readiness->breakdown($user);

        /*
         * A completed diagnostic should have knowledge states.
         * If it does not, something is inconsistent.
         */
        if (empty($breakdown['domains'])) {
            abort(
                500,
                'Readiness data could not be calculated for this completed diagnostic.'
            );
        }

        return view('readiness.show', [
            'user' => $user,
            'breakdown' => $breakdown,
        ]);
    }
}
