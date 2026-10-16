<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminActionRequest;
use App\Http\Requests\Admin\StoreCohortRequest;
use App\Http\Requests\Admin\UpdateCohortRequest;
use App\Http\Requests\Admin\UpdatePostTestWindowRequest;
use App\Models\AdminAuditLog;
use App\Models\AssessmentSession;
use App\Models\Cohort;
use App\Models\User;
use App\Services\AdminCohortService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use LogicException;
use RuntimeException;

class CohortController extends Controller
{
    public function __construct(
        protected AdminCohortService $cohorts,
    ) {}

    public function index(): View
    {
        return view('admin.cohorts.index', [
            'cohorts' => Cohort::query()
                ->withCount([
                    'students',
                    'faculty',
                ])
                ->orderByRaw('archived_at IS NOT NULL')
                ->orderBy('cohort_name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.cohorts.create');
    }

    public function store(
        StoreCohortRequest $request,
    ): RedirectResponse {
        $cohort = $this->cohorts->create(
            $request->user(),
            $request->validated(),
        );

        return redirect()
            ->route(
                'admin.cohorts.show',
                ['cohort' => $cohort],
            )
            ->with(
                'status',
                'Cohort created.',
            );
    }

    public function show(
        Cohort $cohort,
    ): View {
        $studentIds = User::query()
            ->select('user_id')
            ->where(
                'cohort_id',
                $cohort->cohort_id,
            )
            ->where('role', 'student');

        $diagnosticStarted = AssessmentSession::query()
            ->whereIn(
                'user_id',
                clone $studentIds,
            )
            ->where(
                'research_phase',
                'pre_test',
            )
            ->distinct()
            ->count('user_id');

        $diagnosticCompleted = AssessmentSession::query()
            ->whereIn(
                'user_id',
                clone $studentIds,
            )
            ->where(
                'research_phase',
                'pre_test',
            )
            ->whereNotNull('completed_at')
            ->distinct()
            ->count('user_id');

        $mockStarted = AssessmentSession::query()
            ->whereIn(
                'user_id',
                clone $studentIds,
            )
            ->where(
                'research_phase',
                'post_test',
            )
            ->distinct()
            ->count('user_id');

        $mockCompleted = AssessmentSession::query()
            ->whereIn(
                'user_id',
                clone $studentIds,
            )
            ->where(
                'research_phase',
                'post_test',
            )
            ->whereNotNull('completed_at')
            ->distinct()
            ->count('user_id');

        return view('admin.cohorts.show', [
            'cohort' => $cohort,

            'students' => $cohort
                ->students()
                ->orderBy('last_name')
                ->paginate(20)
                ->withQueryString(),

            'faculty' => $cohort
                ->faculty()
                ->orderBy('last_name')
                ->get(),

            'counts' => [
                'students' => $studentIds->count(),
                'diagnostic_started' => $diagnosticStarted,
                'diagnostic_completed' => $diagnosticCompleted,
                'mock_started' => $mockStarted,
                'mock_completed' => $mockCompleted,
            ],

            'recentAudits' => AdminAuditLog::query()
                ->where(
                    'target_type',
                    Cohort::class,
                )
                ->where(
                    'target_id',
                    $cohort->cohort_id,
                )
                ->with('admin')
                ->latest('created_at')
                ->limit(20)
                ->get(),
        ]);
    }

    public function edit(
        Cohort $cohort,
    ): View {
        return view(
            'admin.cohorts.edit',
            compact('cohort'),
        );
    }

    public function update(
        UpdateCohortRequest $request,
        Cohort $cohort,
    ): RedirectResponse {
        try {
            $changed = $this->cohorts->update(
                $request->user(),
                $cohort,
                $request->validated(),
            );
        } catch (LogicException $exception) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    $exception->getMessage(),
                );
        }

        return redirect()
            ->route(
                'admin.cohorts.show',
                ['cohort' => $cohort],
            )
            ->with(
                'status',
                $changed
                    ? 'Cohort updated.'
                    : 'No changes were required.',
            );
    }

    public function updatePostTestWindow(
        UpdatePostTestWindowRequest $request,
        Cohort $cohort,
    ): RedirectResponse {
        try {
            $this->cohorts->updatePostTestWindow(
                $request->user(),
                $cohort,
                $request->validated(),
                $request->validated('reason'),
            );
        } catch (
            LogicException
            |RuntimeException
            $exception
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
            $request->boolean('clear_window')
                ? 'Post-test schedule cleared.'
                : 'Post-test schedule updated.',
        );
    }

    public function archive(
        AdminActionRequest $request,
        Cohort $cohort,
    ): RedirectResponse {
        try {
            $this->cohorts->archive(
                $request->user(),
                $cohort,
                $request->validated('reason'),
            );
        } catch (LogicException $exception) {
            return back()->with(
                'error',
                $exception->getMessage(),
            );
        }

        return back()->with(
            'status',
            'Cohort archived. Existing assignments were preserved.',
        );
    }

    public function unarchive(
        AdminActionRequest $request,
        Cohort $cohort,
    ): RedirectResponse {
        try {
            $this->cohorts->unarchive(
                $request->user(),
                $cohort,
                $request->validated('reason'),
            );
        } catch (LogicException $exception) {
            return back()->with(
                'error',
                $exception->getMessage(),
            );
        }

        return back()->with(
            'status',
            'Cohort unarchived. Its post-test window was not reopened.',
        );
    }
}
