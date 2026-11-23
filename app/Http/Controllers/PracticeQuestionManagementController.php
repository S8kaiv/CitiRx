<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePracticeQuestionRequest;
use App\Models\Question;
use App\Models\TosDomain;
use App\Services\QuestionEditService;
use App\Services\QuestionLockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class PracticeQuestionManagementController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $search = trim($filters['q'] ?? '');

        return view('practice-questions.index', [
            'questions' => Question::query()->whereNull('research_form')->whereNull('form_version_id')
                ->with('competency.domain')
                ->when($search !== '', fn ($query) => $query->where('question_text', 'like', '%'.addcslashes($search, '\\%_').'%'))
                ->orderByDesc('created_at')->orderBy('question_id')->paginate(25)->withQueryString(),
            'search' => $search,
            'routePrefix' => $request->user()->role.'.practice-questions',
        ]);
    }

    public function create(Request $request): View
    {
        return view('practice-questions.create', [
            'domains' => TosDomain::query()->with(['competencies' => fn ($query) => $query->orderBy('order_index')])->orderBy('domain_number')->get(),
            'routePrefix' => $request->user()->role.'.practice-questions',
        ]);
    }

    public function store(StorePracticeQuestionRequest $request, QuestionEditService $questions): RedirectResponse
    {
        try {
            $question = $questions->createPractice($request->user(), $request->validated());
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['choices' => $exception->getMessage()]);
        }

        return redirect()->route($request->user()->role.'.practice-questions.show', $question)
            ->with('status', 'Practice question added. It is now eligible for adaptive practice.');
    }

    public function show(Request $request, Question $question, QuestionLockService $locks): View
    {
        abort_unless($question->research_form === null && $question->form_version_id === null, 404);
        $question->load(['choices' => fn ($query) => $query->orderBy('choice_letter'), 'competency.domain']);

        return view('practice-questions.show', [
            'question' => $question,
            'lockReason' => $locks->lockReason($question),
            'routePrefix' => $request->user()->role.'.practice-questions',
        ]);
    }
}
