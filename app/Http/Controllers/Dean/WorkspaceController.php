<?php

namespace App\Http\Controllers\Dean;

use App\Http\Controllers\Controller;
use App\Models\Cohort;
use App\Models\FacultyIntervention;
use App\Models\ResearchFormVersion;
use App\Models\StudentNameRequest;
use App\Models\User;
use App\Services\FacultyDashboardService;
use App\Services\FacultyInterventionService;
use App\Services\ReadinessService;
use App\Services\StudentNameService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class WorkspaceController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'view' => ['nullable', 'in:students,interventions'],
            'cohort_id' => ['nullable', 'uuid', 'exists:cohorts,cohort_id'],
            'status' => ['nullable', 'in:open,completed,all'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $view = $filters['view'] ?? 'students';
        $cohortId = $filters['cohort_id'] ?? null;
        $status = $filters['status'] ?? 'open';

        if ($view === 'interventions') {
            $results = FacultyIntervention::query()->with(['targetUser.cohort', 'faculty', 'domain', 'competency'])
                ->when($cohortId, fn ($query) => $query->whereHas('targetUser', fn ($query) => $query->where('cohort_id', $cohortId)))
                ->when($status !== 'all', fn ($query) => $query->where('is_completed', $status === 'completed'))
                ->latest('assigned_at')->paginate(10, ['*'], 'interventions_page');
        } else {
            $term = $filters['q'] ?? null;

            $results = User::query()->where('role', 'student')->with('cohort')->withCount('knowledgeStates')
                ->when($cohortId, fn ($query) => $query->where('cohort_id', $cohortId))
                ->when(filled($term), fn ($query) => $query->where(fn ($query) => $query
                    ->where('first_name', 'like', '%'.addcslashes($term, '\\%_').'%')
                    ->orWhere('last_name', 'like', '%'.addcslashes($term, '\\%_').'%')))
                ->orderBy('last_name')->paginate(15, ['*'], 'students_page');
        }

        return view('dean.dashboard', [
            'view' => $view,
            'results' => $results->appends(array_filter($filters, 'filled')),
            'filters' => $filters,
            'cohorts' => Cohort::query()->orderBy('cohort_name')->get(),
            'pendingNames' => StudentNameRequest::query()->where('status', 'pending')->count(),
            'pendingReviews' => ResearchFormVersion::query()->where('status', ResearchFormVersion::STATUS_IN_REVIEW)->count(),
        ]);
    }

    public function student(User $student, ReadinessService $readiness, FacultyDashboardService $facultyDashboard): View
    {
        abort_unless($student->role === 'student', 404);
        $breakdown = null;
        if ($student->is_diagnostic_completed && $student->knowledgeStates()->exists()) {
            try {
                $breakdown = $readiness->breakdown($student);
            } catch (RuntimeException $exception) {
                report($exception);
            }
        }

        $faculty = $student->cohort?->faculty()->orderBy('last_name')->orderBy('first_name')->get() ?? collect();
        $formOptions = $faculty->isNotEmpty() ? $facultyDashboard->formOptions() : collect();

        return view('dean.student', compact('student', 'breakdown', 'faculty', 'formOptions'));
    }

    public function assignIntervention(Request $request, User $student, FacultyInterventionService $interventions): RedirectResponse
    {
        abort_unless($student->role === 'student', 404);

        $data = $request->validate([
            'faculty_id' => ['required', 'uuid'],
            'intervention_type' => ['required', Rule::in(array_keys(FacultyIntervention::TYPE_LABELS))],
            'domain_id' => ['required', 'integer'],
            'competency_id' => ['nullable', 'uuid'],
            'custom_message' => ['nullable', 'string', 'max:1000'],
        ]);

        $interventions->assign(
            $request->user(),
            [...$data, 'target_user_id' => $student->user_id],
            $data['faculty_id'],
        );

        return redirect()->route('dean.dashboard', ['view' => 'interventions'])
            ->with('status', 'Intervention assigned.');
    }

    public function completeIntervention(Request $request, string $intervention, FacultyInterventionService $interventions): RedirectResponse
    {
        $completed = $interventions->complete($request->user(), $intervention);

        return back()->with('status', $completed ? 'Intervention marked complete.' : 'Intervention was already completed.');
    }

    public function updateName(Request $request, User $student, StudentNameService $names): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
        ]);
        $names->edit($request->user(), $student, $data);

        return back()->with('status', 'Official student name updated.');
    }

    public function names(Request $request): View
    {
        $request->validate(['status' => ['nullable', 'in:pending,approved,declined,superseded']]);

        return view('dean.names', ['requests' => StudentNameRequest::query()->with(['student', 'reviewer'])
            ->where('status', $request->input('status', 'pending'))->latest()->paginate(20)->withQueryString()]);
    }

    public function decideName(Request $request, StudentNameRequest $nameRequest, StudentNameService $names): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approve,decline'],
            'decision_note' => ['required_if:decision,decline', 'nullable', 'string', 'max:1000'],
        ]);
        $names->decide($request->user(), $nameRequest, $data['decision'] === 'approve', $data['decision_note'] ?? null);

        return back()->with('status', 'Name request decision recorded.');
    }
}
