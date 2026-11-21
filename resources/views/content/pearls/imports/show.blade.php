<x-app-layout>
    <x-slot name="header"><x-ui.page-header title="Micro Pearl import preview" description="Choose eligible lessons to create as private drafts." /></x-slot>
    <div class="rx-pearl-import mx-auto max-w-6xl space-y-5 px-4 py-6 sm:px-6">
        <x-ui.button variant="secondary" :href="route('content.pearls.imports.create')">Back to imports</x-ui.button>
        @if(session('status'))<x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>@endif
        <p class="break-words text-sm text-ink">{{ $import->original_filename }} · {{ $import->row_count }} rows · {{ ucfirst($import->status) }}</p>
        @error('csv')<x-ui.alert variant="danger">{{ $message }}</x-ui.alert>@enderror
        <x-ui.card><form method="POST" action="{{ route('content.pearls.imports.commit', $import) }}" data-bulk data-confirm="Create private drafts from the selected lessons? They still need review before publication." class="space-y-4">@csrf
            <input type="hidden" name="expected_lock_version" value="{{ $import->lock_version }}">
            <x-ui.selection-controls />
            @error('selected_rows')<p class="text-sm text-danger-ink" role="alert">{{ $message }}</p>@enderror
            @error('expected_lock_version')<p class="text-sm text-danger-ink" role="alert">{{ $message }}</p>@enderror
            <ul class="divide-y divide-line">@foreach($import->preview_rows ?? [] as $row)<li class="space-y-2 py-4 text-sm text-ink"><label class="flex min-h-11 items-start gap-3"><input class="mt-1 rounded border-line" type="checkbox" data-row-select name="selected_rows[]" value="{{ $row['row_number'] }}" @disabled(!$import->canCommit() || !empty($row['errors']))><span class="min-w-0 break-words">Row {{ $row['row_number'] }} · {{ $row['competency_code'] }} · {{ $row['title'] }}<span class="mt-1 block text-ink-muted">Order {{ $row['order_index'] }} · Rights: {{ $row['rights_status'] }}</span></span></label>
                @foreach($row['errors'] ?? [] as $issue)<p class="pl-7 text-danger-ink">{{ $issue }}</p>@endforeach
            </li>@endforeach</ul>
            @if($import->canCommit())<x-ui.button type="submit" data-bulk-submit>Import selected lessons</x-ui.button>@else<p class="text-sm text-ink-muted">This preview is committed, expired, or unavailable. Upload a fresh file to continue.</p>@endif
        </form></x-ui.card>
        @if($import->canCommit())<form method="POST" action="{{ route('content.pearls.imports.destroy', $import) }}" data-confirm="Discard this preview?">@csrf @method('DELETE')<x-ui.button type="submit" variant="secondary">Discard preview</x-ui.button></form>@endif
    </div>
</x-app-layout>
