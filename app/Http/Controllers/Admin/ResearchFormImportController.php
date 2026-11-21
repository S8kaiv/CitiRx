<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CommitResearchFormImportRequest;
use App\Http\Requests\Admin\PreviewResearchFormImportRequest;
use App\Models\Question;
use App\Models\ResearchFormImport;
use App\Models\ResearchFormVersion;
use App\Services\ResearchFormBlueprint;
use App\Services\ResearchFormImportWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;
use LogicException;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResearchFormImportController extends Controller
{
    public function __construct(
        protected ResearchFormImportWorkflowService $imports,
    ) {}

    public function formBTemplate(Request $request): StreamedResponse
    {
        $request->validate(['form' => ['nullable', 'in:pre_test_a,post_test_b']]);
        $form = $request->input('form', Question::FORM_POST_TEST_B);

        return response()->streamDownload(function () use ($form): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'research_form', 'form_position', 'domain_number', 'competency_code',
                'question_text', 'choice_a', 'choice_b', 'choice_c', 'choice_d',
                'correct_choice', 'rationale', 'cognitive_level',
                'source_reference', 'copyright_permission',
            ], ',', '"', '');

            $position = 1;

            foreach (ResearchFormBlueprint::quotas($form) as $domain => $competencies) {
                foreach ($competencies as $code => $count) {
                    for ($item = 0; $item < $count; $item++) {
                        fputcsv($handle, [
                            $form, $position++, $domain, $code,
                            '', '', '', '', '', '', '', '', '', '',
                        ], ',', '"', '');
                    }
                }
            }

            fclose($handle);
        }, 'citirx-'.$form.'-template.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store',
        ]);
    }

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
                'Preview ready. Select eligible rows before '
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
                reason: $validated['reason'] ?? 'Selected question rows imported into a draft.',
                selectedRows: $validated['selected_rows'],
            );
        } catch (
            LogicException|RuntimeException $exception
        ) {
            return back()->withErrors([
                'csv' => $exception->getMessage(),
            ]);
        }

        $action = 'Selected question rows were imported into the draft.';

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
