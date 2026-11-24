<?php

namespace App\Http\Controllers;

use App\Models\AssessmentSession;
use App\Models\Badge;
use App\Models\Level;
use App\Models\QuestionBookmark;
use App\Models\RxVault;
use App\Models\UserBadge;
use App\Services\AdminDashboardService;
use App\Services\ReadinessService;
use App\Services\StudyRecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected ReadinessService $readiness,
        protected StudyRecommendationService $studyRecommendations,
        protected AdminDashboardService $adminDashboard,
    ) {}

    public function index(
        Request $request,
    ): View|RedirectResponse {
        $user = $request->user();

        return match ($user->role) {
            'dean' => redirect()->route('dean.dashboard'),
            'admin' => view(
                'dashboard.admin',
                array_merge(
                    [
                        'user' => $user,
                    ],
                    $this->adminDashboard->data(),
                ),
            ),

            'faculty' => redirect()->route(
                'faculty.dashboard',
            ),

            'student' => view('dashboard.student', [
                'user' => $user->loadMissing([
                    'cohort',
                    'level.tier',
                ]),

                'themeXpThresholds' => Level::query()->pluck('min_xp', 'level_number'),

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
                        (float) $user
                            ->predicted_readiness_pct,
                    )
                    : null,

                'studyRecommendation' => $user->is_diagnostic_completed
                    ? $this->studyRecommendations
                        ->forUser($user)
                    : null,

                'unlockedBadgeCount' => UserBadge::query()
                    ->where('user_id', $user->user_id)
                    ->count(),

                'totalBadgeCount' => Badge::query()
                    ->count(),

                'activeVaultCount' => RxVault::query()
                    ->where('user_id', $user->user_id)
                    ->where('is_cleared', false)
                    ->count(),

                'bookmarkCount' => QuestionBookmark::query()
                    ->where('user_id', $user->user_id)
                    ->count(),
            ]),

            default => abort(
                403,
                'Unknown role.',
            ),
        };
    }

    public function studyCoach(Request $request): JsonResponse
    {
        $recommendation = $this->studyRecommendations->forUser($request->user());
        $pearl = $recommendation['recommended_pearls'][0] ?? null;
        $practice = $recommendation['recommended_action'];

        [$label, $url] = match (true) {
            $recommendation['requires_diagnostic'] => [
                'Start diagnostic',
                route('diagnostic.intro'),
            ],
            $pearl !== null => [
                'Read '.$pearl['title'],
                route('pearls.show', ['pearl' => $pearl['slug']]),
            ],
            $practice !== null => [
                'Set up focused practice',
                route('practice.intro', [
                    'domain_filter_id' => $practice['domain_id'],
                    'length' => $practice['suggested_length'],
                ]),
            ],
            default => [
                'Browse Micro Pearls',
                route('pearls.index'),
            ],
        };

        return response()->json([
            'message' => $recommendation['reason'],
            'label' => $label,
            'url' => $url,
        ], 200, ['Cache-Control' => 'private, no-store']);
    }
}
