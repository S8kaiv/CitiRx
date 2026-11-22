<?php

namespace App\Http\Controllers;

use App\Models\AdminAuditLog;
use App\Models\Question;
use App\Models\ResearchFormVersion;
use App\Models\TosDomain;
use App\Services\ResearchFormAuditService;
use App\Services\ResearchFormBlueprint;
use App\Services\ResearchFormEditorService;
use App\Services\ResearchFormVersionManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use LogicException;
use RuntimeException;

class ResearchFormEditorController extends Controller
{
    public function index(): View
    {
        return view('research-editor.index', ['versions' => ResearchFormVersion::query()->withCount('questions')->orderBy('research_form')->orderByDesc('version_number')->get()]);
    }

    public function store(Request $request, ResearchFormEditorService $editor): RedirectResponse
    {
        $data = $request->validate(['research_form' => ['required', Rule::in(array_keys(ResearchFormBlueprint::QUOTAS))]]);
        try {
            $version = $editor->createVersion($request->user(), $data['research_form']);
        } catch (LogicException|RuntimeException $exception) {
            throw ValidationException::withMessages(['research_form' => $exception->getMessage()]);
        }

        return redirect()->route('research-editor.edit', $version);
    }

    public function edit(Request $request, ResearchFormVersion $version, ResearchFormAuditService $audit): View
    {
        $request->validate(['position' => ['nullable', 'integer', 'min:1', 'max:'.ResearchFormBlueprint::total($version->research_form)]]);
        $position = (int) $request->input('position', 1);

        return view('research-editor.edit', [
            'version' => $version, 'position' => $position,
            'question' => $version->questions()->with('choices')->where('form_position', $position)->first(),
            'domains' => TosDomain::query()->with('competencies')->orderBy('domain_number')->get(),
            'report' => $audit->auditVersion($version, requireValidation: false),
            'revisionNote' => AdminAuditLog::query()->where('target_type', ResearchFormVersion::class)
                ->where('target_id', $version->form_version_id)->where('action', 'research_form.dean_returned')
                ->latest()->value('reason'),
        ]);
    }

    public function save(Request $request, ResearchFormVersion $version, ResearchFormEditorService $editor): RedirectResponse
    {
        $data = $request->validate([
            'expected_lock_version' => ['required', 'integer', 'min:1'],
            'form_position' => ['required', 'integer', 'min:1', 'max:'.ResearchFormBlueprint::total($version->research_form)],
            'competency_id' => ['required', 'uuid', 'exists:tos_competencies,competency_id'],
            'question_text' => ['required', 'string', 'max:10000'], 'choices' => ['required', 'array:A,B,C,D', 'size:4'],
            'choices.*' => ['required', 'string', 'max:5000'], 'correct_choice' => ['required', 'in:A,B,C,D'],
            'hypercorrection_rationale' => ['nullable', 'string', 'max:10000'],
            'source_reference' => ['nullable', 'string', 'max:5000'],
            'copyright_permission' => ['nullable', Rule::in(['permission confirmed', 'public domain', 'licensed', 'pending'])],
            'cognitive_level' => ['required', Rule::in(Question::COGNITIVE_LEVELS)],
        ]);
        if (count(array_unique(array_map(fn ($v) => mb_strtolower(trim($v)), $data['choices']))) !== 4) {
            throw ValidationException::withMessages(['choices' => 'Each choice must have different text.']);
        }
        try {
            $editor->save($request->user(), $version, $data);
        } catch (LogicException|RuntimeException $exception) {
            throw ValidationException::withMessages(['version' => $exception->getMessage()]);
        }

        return redirect()->route('research-editor.edit', ['version' => $version, 'position' => $data['form_position']])->with('status', 'Question saved. This draft requires complete Dean approval before publication.');
    }

    public function submit(Request $request, ResearchFormVersion $version, ResearchFormVersionManagementService $versions): RedirectResponse
    {
        $request->validate(['expected_lock_version' => ['required', 'integer', 'min:1']]);
        try {
            $versions->submitForReview($request->user(), $version, $request->integer('expected_lock_version'), 'Submitted for Dean review in the app.');
        } catch (LogicException|RuntimeException $exception) {
            throw ValidationException::withMessages(['version' => $exception->getMessage()]);
        }

        return back()->with('status', 'Complete form sent to the Dean for review.');
    }

    public function copy(Request $request, ResearchFormVersion $version, ResearchFormVersionManagementService $versions): RedirectResponse
    {
        $request->validate(['expected_lock_version' => ['required', 'integer', 'min:1']]);
        try {
            $draft = $versions->copyToDraft($request->user(), $version, null,
                $request->integer('expected_lock_version'), 'Copied to a new draft in the form editor.');
        } catch (LogicException|RuntimeException $exception) {
            throw ValidationException::withMessages(['version' => $exception->getMessage()]);
        }

        return redirect()->route('research-editor.edit', $draft)->with('status', 'Revised draft created. The original version is unchanged.');
    }
}
