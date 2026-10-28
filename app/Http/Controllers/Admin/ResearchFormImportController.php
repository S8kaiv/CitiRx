<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CommitResearchFormImportRequest;
use App\Http\Requests\Admin\PreviewResearchFormImportRequest;
use App\Models\ResearchFormImport;
use App\Models\ResearchFormVersion;
use App\Models\User;
use App\Services\ResearchFormImportWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;
use LogicException;
use RuntimeException;

class ResearchFormImportController extends Controller
{
    public function __construct(
        protected ResearchFormImportWorkflowService $imports,
    ) {}

    public function index(
        Request $request,
    ): View {
        $versionQuery = ResearchFormVersion::query()
            ->whereNull('first_served_at')
            ->whereDoesntHave('sessions')
            ->withCount([
                'questions' => fn ($query) => $query
                    ->where('is_active', true),
            ])
            ->orderBy('research_form')
            ->orderByDesc('version_number');

        return view(
            'admin.research-form-imports.index',
            [
                'contentVersions' => (
                    clone $versionQuery
                )
                    ->where(
                        'status',
                        ResearchFormVersion::STATUS_DRAFT,
                    )
                    ->get(),

                'validationVersions' => (
                    clone $versionQuery
                )
                    ->where(
                        'status',
                        ResearchFormVersion::STATUS_IN_REVIEW,
                    )
                    ->get(),

                'validators' => User::query()
                    ->whereIn(
                        'role',
                        [
                            'faculty',
                            'admin',
                        ],
                    )
                    ->whereNotNull(
                        'email_verified_at',
                    )
                    ->orderBy('last_name')
                    ->orderBy('first_name')
                    ->get([
                        'user_id',
                        'first_name',
                        'last_name',
                        'email',
                        'role',
                    ]),

                'imports' => ResearchFormImport::query()
                    ->where(
                        'admin_id',
                        $request->user()->user_id,
                    )
                    ->with([
                        'version',
                        'validator',
                    ])
                    ->latest()
                    ->limit(20)
                    ->get(),
            ],
        );
    }

    public function preview(
        PreviewResearchFormImportRequest $request,
    ): RedirectResponse {
        $validated = $request->validated();

        /** @var UploadedFile $file */
        $file = $request->file('csv');

        try {
            $import = $this->imports
                ->createPreview(
                    administrator: $request->user(),
                    file: $file,
                    type: $validated['import_type'],
                    versionId: $validated[
                        'form_version_id'
                    ],
                    validatorId: $validated[
                        'validator_id'
                    ] ?? null,
                );
        } catch (
            LogicException|RuntimeException $exception
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'csv' => $exception->getMessage(),
                ]);
        }

        return redirect()
            ->route(
                'admin.research-form-imports.index',
            )
            ->with(
                'status',
                'Dry run passed. Review the result '
                    .'and confirm the import before '
                    .$import->expires_at->format(
                        'M j, Y g:i A',
                    ).'.',
            );
    }

    public function commit(
        CommitResearchFormImportRequest $request,
        ResearchFormImport $researchFormImport,
    ): RedirectResponse {
        abort_unless(
            $researchFormImport->admin_id
                === $request->user()->user_id,
            403,
        );

        $validated = $request->validated();

        try {
            $import = $this->imports->commit(
                administrator: $request->user(),
                import: $researchFormImport,
                expectedLockVersion: (int) $validated[
                    'expected_lock_version'
                ],
                reason: $validated['reason'],
            );
        } catch (
            LogicException|RuntimeException $exception
        ) {
            return back()->withErrors([
                'csv' => $exception->getMessage(),
            ]);
        }

        $action = $import->import_type
            === ResearchFormImport::TYPE_CONTENT
            ? 'Question content was imported.'
            : 'Faculty validation was imported.';

        return redirect()
            ->route(
                'admin.research-forms.index',
            )
            ->with(
                'status',
                $action.' Continue the normal '
                    .'version workflow on this page.',
            );
    }

    public function destroy(
        Request $request,
        ResearchFormImport $researchFormImport,
    ): RedirectResponse {
        abort_unless(
            $researchFormImport->admin_id
                === $request->user()->user_id,
            403,
        );

        try {
            $this->imports->cancel(
                $request->user(),
                $researchFormImport,
            );
        } catch (
            LogicException|RuntimeException $exception
        ) {
            return back()->withErrors([
                'csv' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route(
                'admin.research-form-imports.index',
            )
            ->with(
                'status',
                'The private import preview was cancelled.',
            );
    }
}
