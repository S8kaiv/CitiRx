<x-app-layout surface="admin">
    <x-slot name="header"><x-admin.page-header title="Research question import" description="Bulk entry into an unserved draft. Use the form editor for routine authoring." /></x-slot>
    <div class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6">
        @if(session('status'))<x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>@endif
        <x-ui.button variant="secondary" :href="route('research-editor.index')">Open in-app form editor</x-ui.button>
        <x-ui.card><form method="POST" action="{{ route('admin.research-form-imports.preview') }}" enctype="multipart/form-data" class="space-y-4">@csrf
            <input type="hidden" name="import_type" value="content">
            <x-ui.field name="form_version_id" label="Draft version" type="select" required><option value="">Choose a draft</option>@foreach($contentVersions as $version)<option value="{{ $version->form_version_id }}" @selected(old('form_version_id') === $version->form_version_id)>{{ $version->version_code }}</option>@endforeach</x-ui.field>
            <x-ui.field name="csv" label="Question CSV" type="file" accept=".csv,.txt" required hint="UTF-8 CSV, up to 2 MB. Include answers, rationales, sources, and rights." />
            <x-ui.button type="submit">Preview rows</x-ui.button>
            <x-ui.button variant="secondary" :href="route('admin.research-form-imports.form-b-template')">Download Form B template</x-ui.button>
            <x-ui.button variant="secondary" :href="route('admin.research-form-imports.form-b-template', ['form' => 'pre_test_a'])">Download Form A template</x-ui.button>
        </form></x-ui.card>
        <p class="text-sm text-ink-muted">Both forms require Dean approval in the app. Faculty-validation CSV files are no longer accepted.</p>
        @error('csv')<x-ui.alert variant="danger">{{ $message }}</x-ui.alert>@enderror
        @foreach($imports as $import)<x-ui.card>
            <h2 class="break-words font-bold text-ink">{{ $import->version?->version_code }} · {{ $import->original_filename }}</h2>
            <p class="mt-2 text-sm text-ink-muted">{{ ucfirst($import->status) }} · {{ $import->preview_output }}</p>
            @if($import->canCommit())<form method="POST" action="{{ route('admin.research-form-imports.commit', $import) }}" data-bulk data-confirm="Import selected rows into this draft? This does not approve or publish the form." class="mt-4 space-y-4">@csrf
                <input type="hidden" name="expected_lock_version" value="{{ $import->lock_version }}">
                <x-ui.selection-controls />
                @error('selected_rows')<p class="text-sm text-danger-ink" role="alert">{{ $message }}</p>@enderror
                @error('expected_lock_version')<p class="text-sm text-danger-ink" role="alert">{{ $message }}</p>@enderror
                <ul class="divide-y divide-line">@foreach($import->preview_rows ?? [] as $row)<li class="space-y-2 py-4 text-sm text-ink">
                    <label class="flex min-h-11 items-start gap-3"><input class="mt-1 rounded border-line" type="checkbox" data-row-select name="selected_rows[]" value="{{ $row['row_number'] }}" @disabled(!empty($row['errors']))><span class="min-w-0 break-words">Row {{ $row['row_number'] }} · Position {{ $row['form_position'] }}: {{ $row['title'] }}</span></label>
                    @foreach($row['errors'] as $issue)<p class="pl-7 text-danger-ink">{{ $issue }}</p>@endforeach
                    @if(empty($row['errors']))<details class="pl-7"><summary class="cursor-pointer text-primary-ink">Choices, rationale, and source</summary><div class="mt-2 space-y-2">@foreach($row['payload']['choices'] as $letter => $choice)<p>{{ $letter }}. {{ $choice }} {{ $row['payload']['correct_choice'] === $letter ? '(Correct)' : '' }}</p>@endforeach<p>{{ $row['payload']['hypercorrection_rationale'] }}</p><p>{{ $row['payload']['source_reference'] }} · {{ $row['payload']['copyright_permission'] }}</p></div></details>@endif
                </li>@endforeach</ul>
                <x-ui.button type="submit" data-bulk-submit>Import selected questions</x-ui.button>
            </form>
            <form method="POST" action="{{ route('admin.research-form-imports.destroy', $import) }}" data-confirm="Discard this import preview?" class="mt-3">@csrf @method('DELETE')<x-ui.button type="submit" variant="secondary">Discard preview</x-ui.button></form>@endif
            @if($import->failure_message)<p class="mt-3 text-sm text-danger-ink">{{ $import->failure_message }}</p>@endif
        </x-ui.card>@endforeach
    </div>
</x-app-layout>
