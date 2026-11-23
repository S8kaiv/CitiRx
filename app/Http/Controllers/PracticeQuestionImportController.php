<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommitPracticeQuestionImportRequest;
use App\Http\Requests\PreviewPracticeQuestionImportRequest;
use App\Models\PracticeQuestionImport;
use App\Models\TosDomain;
use App\Services\PracticeQuestionImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PracticeQuestionImportController extends Controller
{
    public function create(Request $request): View
    {
        return view('practice-questions.imports.create', [
            'routePrefix' => $request->user()->role.'.practice-questions',
            'domains' => TosDomain::query()->with(['competencies' => fn ($query) => $query->orderBy('order_index')])->orderBy('domain_number')->get(),
        ]);
    }

    public function template(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, PracticeQuestionImportService::HEADERS, ',', '"', '');
            fclose($output);
        }, 'practice-question-import-template.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'no-store, private']);
    }

    public function preview(PreviewPracticeQuestionImportRequest $request, PracticeQuestionImportService $imports): RedirectResponse
    {
        $import = $imports->preview($request->user(), $request->file('csv'));

        return redirect()->route($request->user()->role.'.practice-questions.imports.show', $import);
    }

    public function show(Request $request, PracticeQuestionImport $import): View
    {
        abort_unless($import->uploader_id === $request->user()->user_id, 403);

        return view('practice-questions.imports.show', [
            'import' => $import,
            'routePrefix' => $request->user()->role.'.practice-questions',
        ]);
    }

    public function commit(CommitPracticeQuestionImportRequest $request, PracticeQuestionImport $import, PracticeQuestionImportService $imports): RedirectResponse
    {
        $count = $imports->commit($request->user(), $import, $request->validated('selected_rows'));

        return redirect()->route($request->user()->role.'.practice-questions.index')
            ->with('status', $count.' practice question(s) imported and available for adaptive practice. Unselected rows were not imported.');
    }
}
