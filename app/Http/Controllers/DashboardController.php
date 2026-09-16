<?php

namespace App\Http\Controllers;

use App\Models\AssessmentSession;
use App\Services\ReadinessService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected ReadinessService $readiness,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        return match ($user->role) {
            'admin' => view('dashboard.admin'),

            'faculty' => view('dashboard.faculty'),

            'student' => view('dashboard.student', [
                'inProgressDiagnostic' => AssessmentSession::query()
                    ->where('user_id', $user->user_id)
                    ->where('session_type', 'diagnostic')
                    ->whereNull('completed_at')
                    ->latest('started_at')
                    ->first(),

                'inProgressPractice' => AssessmentSession::query()
                    ->where('user_id', $user->user_id)
                    ->where('session_type', 'practice')
                    ->whereNull('completed_at')
                    ->latest('started_at')
                    ->first(),

                'readinessBand' => $user->predicted_readiness_pct !== null
                    ? $this->readiness->band(
                        (float) $user->predicted_readiness_pct
                    )
                    : null,
            ]),

            default => abort(403, 'Unknown role.'),
        };
    }
}
