<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CopyResearchFormVersionRequest;
use App\Http\Requests\Admin\PublishResearchFormVersionRequest;
use App\Http\Requests\Admin\TransitionResearchFormVersionRequest;
use App\Http\Requests\Admin\ViewResearchFormsRequest;
use App\Models\ResearchFormVersion;
use App\Services\ResearchFormAuditService;
use App\Services\ResearchFormVersionManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ResearchFormController extends Controller
{
    public function __construct(
        protected ResearchFormAuditService $audit,
        protected ResearchFormVersionManagementService $versions,
    ) {}

    public function index(
        ViewResearchFormsRequest $request,
    ): View {
        $request->validated();

        return view(
            'admin.research-forms.index',
            [
                'reports' => $this->audit->auditAll(
                    requireValidation: true,
                ),

                'versions' => ResearchFormVersion::query()
                    ->withCount([
                        'questions' => fn ($query) => $query->where(
                            'is_active',
                            true,
                        ),
                    ])
                    ->with([
                        'creator:user_id,first_name,last_name',
                        'publisher:user_id,first_name,last_name',
                    ])
                    ->orderBy('research_form')
                    ->orderByDesc('version_number')
                    ->get()
                    ->groupBy('research_form'),

                'developmentOverrideConfigured' => (bool) config(
                    'citirx.research.'
                        .'allow_unvalidated_forms',
                    false,
                ),
            ],
        );
    }

    public function copy(
        CopyResearchFormVersionRequest $request,
        ResearchFormVersion $researchFormVersion,
    ): RedirectResponse {
        $validated = $request->validated();

        $draft = $this->versions->copyToDraft(
            administrator: $request->user(),
            source: $researchFormVersion,
            versionCode: $validated['version_code'],
            expectedLockVersion: $validated[
                    'expected_lock_version'
                ],
            reason: $validated['reason'],
        );

        return redirect()
            ->route(
                'admin.research-forms.index',
            )
            ->with(
                'status',
                "{$draft->version_code} was created as a draft.",
            );
    }

    public function submitReview(
        TransitionResearchFormVersionRequest $request,
        ResearchFormVersion $researchFormVersion,
    ): RedirectResponse {
        $validated = $request->validated();

        $this->versions->submitForReview(
            administrator: $request->user(),
            version: $researchFormVersion,
            expectedLockVersion: $validated[
                    'expected_lock_version'
                ],
            reason: $validated['reason'],
        );

        return back()->with(
            'status',
            'The version was submitted for review.',
        );
    }

    public function markValidated(
        TransitionResearchFormVersionRequest $request,
        ResearchFormVersion $researchFormVersion,
    ): RedirectResponse {
        $validated = $request->validated();

        $this->versions->markValidated(
            administrator: $request->user(),
            version: $researchFormVersion,
            expectedLockVersion: $validated[
                    'expected_lock_version'
                ],
            reason: $validated['reason'],
        );

        return back()->with(
            'status',
            'The version passed validation.',
        );
    }

    public function publish(
        PublishResearchFormVersionRequest $request,
        ResearchFormVersion $researchFormVersion,
    ): RedirectResponse {
        $validated = $request->validated();

        $version = $this->versions->publish(
            administrator: $request->user(),
            version: $researchFormVersion,
            expectedLockVersion: $validated[
                    'expected_lock_version'
                ],
            reason: $validated['reason'],
        );

        return back()->with(
            'status',
            "{$version->version_code} is now active.",
        );
    }
}
