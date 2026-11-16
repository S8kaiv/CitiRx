<?php

namespace App\Http\Controllers\Dean;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dean\ApproveResearchFormRequest;
use App\Http\Requests\Dean\ReturnResearchFormRequest;
use App\Models\Question;
use App\Models\ResearchFormVersion;
use App\Services\DeanResearchFormReviewService;
use App\Services\ResearchFormAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use LogicException;

class ResearchFormReviewController extends Controller
{
    public function __construct(
        protected DeanResearchFormReviewService $reviews,
        protected ResearchFormAuditService $audit,
    ) {}

    public function index(): View
    {
        return view('dean.research-forms.index', [
            'versions' => ResearchFormVersion::query()
                ->where('research_form', Question::FORM_POST_TEST_B)
                ->whereIn('status', [
                    ResearchFormVersion::STATUS_IN_REVIEW,
                    ResearchFormVersion::STATUS_VALIDATED,
                ])
                ->withCount(['questions' => fn ($query) => $query->where('is_active', true)])
                ->orderByDesc('version_number')
                ->get(),
        ]);
    }

    public function show(ResearchFormVersion $researchFormVersion): View
    {
        abort_unless($researchFormVersion->research_form === Question::FORM_POST_TEST_B, 404);

        $questions = $this->reviews->questions($researchFormVersion);

        return view('dean.research-forms.show', [
            'version' => $researchFormVersion,
            'questions' => $questions,
            'digest' => $this->reviews->digest($questions),
            'report' => $this->audit->auditVersion($researchFormVersion, requireValidation: false),
        ]);
    }

    public function approve(
        ApproveResearchFormRequest $request,
        ResearchFormVersion $researchFormVersion,
    ): RedirectResponse {
        try {
            $this->reviews->approveAll(
                $request->user(),
                $researchFormVersion,
                $request->integer('expected_lock_version'),
                $request->validated('expected_digest'),
            );
        } catch (LogicException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', 'All 100 Form B questions were approved. You can now validate and publish the version in Research Forms.');
    }

    public function returnForRevision(
        ReturnResearchFormRequest $request,
        ResearchFormVersion $researchFormVersion,
    ): RedirectResponse {
        try {
            $this->reviews->returnForRevision(
                $request->user(),
                $researchFormVersion,
                $request->integer('expected_lock_version'),
                $request->validated('reason'),
            );
        } catch (LogicException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('dean.research-forms.index')
            ->with('status', 'Form B was returned to draft with your revision notes.');
    }
}
