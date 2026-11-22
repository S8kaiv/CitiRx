<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Http\Requests\Content\CommitMicroPearlImportRequest;
use App\Http\Requests\Content\PreviewMicroPearlImportRequest;
use App\Models\MicroPearl;
use App\Models\MicroPearlImport;
use App\Services\MicroPearlImportService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MicroPearlImportController extends Controller
{
    public function __construct(
        protected MicroPearlImportService $imports,
    ) {}

    public function create(
        Request $request,
    ): View {
        return view(
            'content.pearls.imports.create',
            [
                'imports' => MicroPearlImport::query()
                    ->where(
                        'uploader_id',
                        $request->user()->user_id,
                    )
                    ->latest()
                    ->limit(20)
                    ->get(),
            ],
        );
    }

    public function workbook(): BinaryFileResponse
    {
        return response()->download(
            resource_path('templates/micro-pearl-import-template.xlsx'),
            'micro-pearl-import-template.xlsx',
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'no-store, private',
            ],
        );
    }

    public function template(): StreamedResponse
    {
        return response()->streamDownload(
            function (): void {
                $output = fopen(
                    'php://output',
                    'wb',
                );

                if ($output === false) {
                    return;
                }

                /*
                 * UTF-8 BOM helps Excel recognize the file
                 * without changing its content.
                 */
                fwrite(
                    $output,
                    "\xEF\xBB\xBF",
                );

                fputcsv(
                    $output,
                    MicroPearlImportService::HEADERS,
                    ',',
                    '"',
                    '',
                );

                fclose($output);
            },
            'micro-pearl-import-template.csv',
            [
                'Content-Type' => 'text/csv; charset=UTF-8',

                'Cache-Control' => 'no-store, private',
            ],
        );
    }

    public function preview(
        PreviewMicroPearlImportRequest $request,
    ): RedirectResponse {
        /** @var UploadedFile $file */
        $file = $request->file('csv');

        try {
            $import = $this->imports
                ->createPreview(
                    $request->user(),
                    $file,
                );
        } catch (
            InvalidArgumentException
            |LogicException
            |RuntimeException
            $exception
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'csv' => $exception->getMessage(),
                ]);
        }

        return redirect()
            ->route(
                'content.pearls.imports.show',
                ['import' => $import],
            )
            ->with(
                'status',
                'Preview ready. Select eligible lessons and review any row errors.',
            );
    }

    public function show(
        Request $request,
        MicroPearlImport $import,
    ): View {
        abort_unless(
            $import->uploader_id
                === $request->user()->user_id,
            403,
        );

        return view(
            'content.pearls.imports.show',
            compact('import'),
        );
    }

    public function commit(
        CommitMicroPearlImportRequest $request,
        MicroPearlImport $import,
    ): RedirectResponse {
        abort_unless(
            $import->uploader_id
                === $request->user()->user_id,
            403,
        );

        $validated = $request->validated();
        $submitForReview = $request->boolean('submit_for_review');

        try {
            $committed = $this->imports->commit(
                uploader: $request->user(),

                import: $import,

                expectedLockVersion: (int) $validated[
                    'expected_lock_version'
                ],
                selectedRows: $validated['selected_rows'],
                submitForReview: $submitForReview,
            );
        } catch (QueryException $exception) {
            report($exception);

            return back()->withErrors([
                'csv' => 'The import could not be saved. Upload and preview the CSV again.',
            ]);
        } catch (
            InvalidArgumentException
            |LogicException
            |RuntimeException
            $exception
        ) {
            return back()->withErrors([
                'csv' => $exception->getMessage(),
            ]);
        }

        $submitted = collect($committed->preview_rows)
            ->where('imported_status', MicroPearl::STATUS_IN_REVIEW)
            ->count();
        $drafts = $committed->row_count - $submitted;

        return redirect()
            ->route('content.pearls.index', $submitted > 0 ? ['status' => 'in_review'] : [])
            ->with(
                'status',
                $submitted > 0
                    ? "{$submitted} sent to review and {$drafts} kept as drafts."
                    : "{$drafts} Micro Pearl draft(s) imported.",
            );
    }

    public function destroy(
        Request $request,
        MicroPearlImport $import,
    ): RedirectResponse {
        abort_unless(
            $import->uploader_id
                === $request->user()->user_id,
            403,
        );

        try {
            $this->imports->cancel(
                $request->user(),
                $import,
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
                'content.pearls.imports.create',
            )
            ->with(
                'status',
                'The private CSV preview was cancelled.',
            );
    }
}
