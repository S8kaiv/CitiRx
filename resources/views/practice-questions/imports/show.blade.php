<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Practice CSV preview" description="Review the content and select eligible questions before importing.">
            <x-slot:actions>
                <x-ui.button variant="secondary" :href="route($routePrefix.'.imports.create')">Upload another CSV</x-ui.button>
                <x-ui.button variant="ghost" :href="route($routePrefix.'.index')">Back to practice questions</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <div class="mx-auto max-w-4xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        <p class="break-words text-sm text-ink">{{ $import->original_filename }} · {{ $import->row_count }} rows</p>
        @error('csv')<x-ui.alert variant="danger">{{ $message }}</x-ui.alert>@enderror
        @if ($import->canCommit())
            @php
                $eligibleCount = collect($import->preview_rows)->filter(fn ($row) => empty($row['errors']))->count();
                $oldSelection = old('selected_rows', []);
                $oldSelection = is_array($oldSelection) ? $oldSelection : [];
            @endphp
            <x-ui.alert>{{ $eligibleCount }} of {{ $import->row_count }} rows are eligible. Selected questions become available for adaptive practice immediately and lock after they are served. This preview expires {{ $import->expires_at->diffForHumans() }}.</x-ui.alert>
            <x-ui.card>
                <form method="POST" action="{{ route($routePrefix.'.imports.commit', $import) }}" data-bulk
                    data-confirm="Import the selected questions into the active practice pool? Unselected rows will not be imported." class="space-y-4">
                    @csrf
                    <x-ui.selection-controls />
                    @if ($errors->has('selected_rows') || $errors->has('selected_rows.*'))
                        <p class="text-sm font-semibold text-danger-ink" role="alert">{{ $errors->first('selected_rows') ?: $errors->first('selected_rows.*') }}</p>
                    @endif
                    <ul class="divide-y divide-line">
                        @foreach ($import->preview_rows as $row)
                            <li class="space-y-3 py-4 text-sm text-ink">
                                <label class="flex min-h-11 items-start gap-3">
                                    <input type="checkbox" data-row-select name="selected_rows[]" value="{{ $row['row_number'] }}"
                                        class="mt-1 shrink-0 rounded border-line" @disabled(!empty($row['errors']))
                                        @checked(in_array($row['row_number'], $oldSelection) && empty($row['errors']))>
                                    <span class="min-w-0 break-words"><span class="block font-bold">Row {{ $row['row_number'] }} · {{ $row['competency_code'] ?: 'No competency' }}</span><span class="mt-1 block">{{ $row['title'] }}</span></span>
                                </label>
                                @if ($row['errors'])
                                    <ul class="space-y-1 pl-7 text-danger-ink">
                                        @foreach ($row['errors'] as $issue)<li class="break-words">{{ $issue }}</li>@endforeach
                                    </ul>
                                @endif
                                @if ($row['payload'])
                                    <details class="ml-7 min-w-0">
                                        <summary class="flex min-h-11 cursor-pointer items-center font-semibold text-primary-ink">Review choices, answer, rationale, and source</summary>
                                        <div class="mt-2 space-y-3 break-words leading-relaxed">
                                            @foreach ($row['payload']['choices'] as $letter => $choice)
                                                <p><span class="font-bold">{{ $letter }}{{ $letter === $row['payload']['correct_choice'] ? ' (correct)' : '' }}.</span> {{ $choice['text'] }}</p>
                                            @endforeach
                                            <p><span class="font-bold">Rationale:</span> {{ $row['payload']['hypercorrection_rationale'] }}</p>
                                            <p><span class="font-bold">Cognitive level:</span> {{ $row['payload']['cognitive_level'] ?: 'Not assigned' }}</p>
                                            <p><span class="font-bold">Source:</span> {{ $row['payload']['source_reference'] }}</p>
                                            <p><span class="font-bold">Rights:</span> {{ $row['payload']['copyright_permission'] }}</p>
                                        </div>
                                    </details>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    @if ($eligibleCount > 0)
                        <x-ui.button type="submit" data-bulk-submit class="w-full sm:w-auto">Import selected questions</x-ui.button>
                    @else
                        <p class="text-sm text-ink-muted">No eligible rows. Correct the errors in your CSV and upload it again.</p>
                    @endif
                </form>
            </x-ui.card>
        @else
            <x-ui.alert :variant="$import->committed_at ? 'success' : 'warning'">{{ $import->committed_at ? 'This preview has already been imported. It cannot be imported again.' : 'This preview has expired. Upload your CSV again to continue.' }}</x-ui.alert>
        @endif
    </div>
</x-app-layout>
