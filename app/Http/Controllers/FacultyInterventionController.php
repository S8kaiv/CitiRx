<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFacultyInterventionRequest;
use App\Services\FacultyDashboardService;
use App\Services\FacultyInterventionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FacultyInterventionController extends Controller
{
    public function __construct(
        protected FacultyDashboardService $dashboard,
        protected FacultyInterventionService $interventions,
    ) {}

    public function index(
        Request $request,
    ): View|RedirectResponse {
        $faculty = $request->user();

        if (
            $this->dashboard->cohortFor(
                $faculty,
            ) === null
        ) {
            return redirect()
                ->route(
                    'faculty.dashboard',
                )
                ->with(
                    'error',
                    'Your faculty account is not assigned to a cohort.',
                );
        }

        return view(
            'faculty.interventions',
            [
                'interventions' => $this->dashboard
                    ->paginatedInterventions(
                        $faculty,
                    ),
            ],
        );
    }

    public function store(
        StoreFacultyInterventionRequest $request,
    ): RedirectResponse {
        $this->interventions->assign(
            $request->user(),
            $request->validated(),
        );

        return redirect()
            ->route(
                'faculty.dashboard',
            )
            ->with(
                'status',
                'Intervention assigned.',
            );
    }

    public function complete(
        Request $request,
        string $intervention,
    ): RedirectResponse {
        $completed =
            $this->interventions->complete(
                $request->user(),
                $intervention,
            );

        return redirect()
            ->route(
                'faculty.interventions.index',
            )
            ->with(
                'status',
                $completed
                    ? 'Intervention marked complete.'
                    : 'Intervention was already completed.',
            );
    }
}
