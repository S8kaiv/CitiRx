<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePilotControlRequest;
use App\Services\PilotControlService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use LogicException;
use RuntimeException;

class PilotControlController extends Controller
{
    public function __construct(
        protected PilotControlService $pilot,
    ) {}

    public function index(): View
    {
        return view(
            'admin.pilot.index',
            $this->pilot->dashboardData(),
        );
    }

    public function update(
        UpdatePilotControlRequest $request,
    ): RedirectResponse {
        try {
            $this->pilot->transition(
                $request->user(),
                $request->safe()->except([
                    'current_password',
                ]),
            );
        } catch (
            LogicException
            |RuntimeException $exception
        ) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    $exception->getMessage(),
                );
        }

        return back()->with(
            'status',
            'Pilot phase updated and recorded '
                .'in the audit history.',
        );
    }
}
