<?php

namespace App\Http\Controllers\Dean;

use App\Http\Controllers\Controller;
use App\Models\Cohort;
use App\Models\FacultyIntervention;
use App\Models\ResearchFormVersion;
use App\Models\StudentNameRequest;
use App\Models\User;
use App\Services\ReadinessService;
use App\Services\StudentNameService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class WorkspaceController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'cohort_id' => ['nullable', 'uuid', 'exists:cohorts,cohort_id'],
            'status' => ['nullable', 'in:open,completed,all'], 'q' => ['nullable', 'string', 'max:100'],
        ]);
        $cohortId = $filters['cohort_id'] ?? null;
        $students = User::query()->where('role', 'student')->with('cohort')->withCount('knowledgeStates')
            ->when($cohortId, fn ($q) => $q->where('cohort_id', $cohortId))
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($q) => $q
                ->where('first_name', 'like', '%'.addcslashes($term, '\\%_').'%')
                ->orWhere('last_name', 'like', '%'.addcslashes($term, '\\%_').'%')))
            ->orderBy('last_name')->paginate(15, ['*'], 'students_page')->withQueryString();
        $status = $filters['status'] ?? 'open';
        $interventions = FacultyIntervention::query()->with(['targetUser.cohort', 'faculty', 'domain'])
            ->when($cohortId, fn ($q) => $q->whereHas('targetUser', fn ($q) => $q->where('cohort_id', $cohortId)))
            ->when($status !== 'all', fn ($q) => $q->where('is_completed', $status === 'completed'))
            ->latest('assigned_at')->paginate(10, ['*'], 'interventions_page')->withQueryString();

        return view('dean.dashboard', [
            'students' => $students, 'interventions' => $interventions, 'filters' => $filters,
            'cohorts' => Cohort::query()->orderBy('cohort_name')->get(),
            'pendingNames' => StudentNameRequest::query()->where('status', 'pending')->count(),
            'pendingReviews' => ResearchFormVersion::query()->where('status', 'in_review')->get(),
        ]);
    }

    public function student(User $student, ReadinessService $readiness): View
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

        return view('dean.student', compact('student', 'breakdown'));
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
