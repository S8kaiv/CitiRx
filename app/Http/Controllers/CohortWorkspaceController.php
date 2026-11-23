<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dean\AssignCohortRequest;
use App\Http\Requests\Dean\StoreCohortRequest;
use App\Models\Cohort;
use App\Models\User;
use App\Services\AdminCohortService;
use App\Services\AdminUserService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CohortWorkspaceController extends Controller
{
    public function create(): View
    {
        return view('cohorts.create');
    }

    public function store(StoreCohortRequest $request, AdminCohortService $cohorts): RedirectResponse
    {
        $cohort = $cohorts->create($request->user(), $request->validated());

        return redirect()->route('dean.cohorts.show', $cohort)->with('status', 'Cohort created.');
    }

    public function assignments(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'cohort_id' => ['nullable', 'uuid'],
        ]);
        $search = trim($filters['q'] ?? '');

        return view('cohorts.assign-students', [
            'students' => User::query()->where('role', 'student')->with('cohort')
                ->withExists(['assessmentSessions as research_locked' => fn (Builder $query) => $query->whereIn('research_phase', ['pre_test', 'post_test'])])
                ->when($search !== '', function (Builder $query) use ($search): void {
                    $term = '%'.addcslashes($search, '\\%_').'%';
                    $query->where(fn (Builder $query) => $query->where('first_name', 'like', $term)
                        ->orWhere('middle_name', 'like', $term)->orWhere('last_name', 'like', $term)
                        ->orWhere('student_id', 'like', $term));
                })
                ->orderBy('last_name')->orderBy('first_name')->orderBy('user_id')
                ->paginate(25)->withQueryString(),
            'cohorts' => Cohort::query()->active()->orderBy('cohort_name')->get(),
            'search' => $search,
            'selectedCohortId' => $filters['cohort_id'] ?? null,
        ]);
    }

    public function assign(AssignCohortRequest $request, AdminUserService $users): RedirectResponse
    {
        $data = $request->validated();
        $count = $users->assignCohort($request->user(), $data['student_ids'], $data['cohort_id'], $data['original_cohorts']);

        return back()->with('status', "{$count} students moved to the selected cohort.");
    }

    public function index(Request $request): View
    {
        return view('cohorts.index', [
            'cohorts' => $this->visibleCohorts($request->user())
                ->withCount(['students', 'faculty'])
                ->orderByRaw('archived_at IS NOT NULL')
                ->orderBy('cohort_name')
                ->paginate(15),
            'routePrefix' => $request->user()->role.'.cohorts',
        ]);
    }

    public function show(Request $request, Cohort $cohort): View
    {
        $cohort = $this->visibleCohorts($request->user())
            ->whereKey($cohort->cohort_id)
            ->withCount([
                'students',
                'students as diagnostic_completed_count' => fn (Builder $query) => $query->where('is_diagnostic_completed', true),
            ])
            ->firstOrFail();

        return view('cohorts.show', [
            'cohort' => $cohort,
            'students' => $cohort->students()->orderBy('last_name')->orderBy('first_name')->paginate(20),
            'faculty' => $cohort->faculty()->orderBy('last_name')->orderBy('first_name')->get(),
            'routePrefix' => $request->user()->role.'.cohorts',
        ]);
    }

    private function visibleCohorts(User $user): Builder
    {
        abort_unless(in_array($user->role, ['dean', 'faculty'], true), 403);

        return Cohort::query()->when(
            $user->role === 'faculty',
            fn (Builder $query) => $query->where('cohort_id', $user->cohort_id),
        );
    }
}
