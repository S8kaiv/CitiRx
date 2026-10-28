<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CommitCohortAssignmentImportRequest;
use App\Http\Requests\Admin\PreviewCohortAssignmentImportRequest;
use App\Models\CohortAssignmentImport;
use App\Services\CohortAssignmentImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use LogicException;

class CohortAssignmentImportController extends Controller
{
    public function __construct(
        protected CohortAssignmentImportService $imports,
    ) {}

    public function create(): View
    {
        return view(
            'admin.cohort-imports.create',
        );
    }

    public function preview(
        PreviewCohortAssignmentImportRequest $request,
    ): RedirectResponse {
        $import = $this->imports->createPreview(
            $request->user(),
            $request->file('csv'),
        );

        return redirect()->route(
            'admin.cohort-imports.show',
            ['import' => $import],
        );
    }

    public function show(
        Request $request,
        CohortAssignmentImport $import,
    ): View {
        abort_unless(
            $import->admin_id
                === $request->user()->user_id,
            403,
        );

        return view(
            'admin.cohort-imports.show',
            [
                'import' => $import,
                'rows' => $this->imports
                    ->displayRows($import),
            ],
        );
    }

    public function commit(
        CommitCohortAssignmentImportRequest $request,
        CohortAssignmentImport $import,
    ): RedirectResponse {
        try {
            $result = $this->imports->commit(
                $request->user(),
                $import,
                $request->integer('lock_version'),
                $request->validated('reason'),
            );
        } catch (LogicException $exception) {
            return back()->with(
                'error',
                $exception->getMessage(),
            );
        }

        return redirect()
            ->route('admin.users.index')
            ->with(
                'status',
                "{$result['changed']} cohort assignment(s) updated; "
                    ."{$result['unchanged']} unchanged.",
            );
    }

    public function destroy(
        Request $request,
        CohortAssignmentImport $import,
    ): RedirectResponse {
        try {
            $this->imports->cancel(
                $request->user(),
                $import,
            );
        } catch (LogicException $exception) {
            return back()->with(
                'error',
                $exception->getMessage(),
            );
        }

        return redirect()
            ->route(
                'admin.cohort-imports.create',
            )
            ->with(
                'status',
                'Import preview cancelled.',
            );
    }
}
