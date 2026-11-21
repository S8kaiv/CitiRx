<x-app-layout>
    <x-slot name="header"><x-ui.page-header title="Research form editor" description="Write Form A and Form B in the app. Use CSV only for bulk entry." /></x-slot>
    <div class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6">
        @error('research_form')<x-ui.alert variant="danger">{{ $message }}</x-ui.alert>@enderror
        <form method="POST" action="{{ route('research-editor.store') }}" class="flex flex-wrap items-end gap-3">@csrf
            <x-ui.field name="research_form" label="New draft" type="select"><option value="pre_test_a">Form A · 60 questions</option><option value="post_test_b">Form B · 100 questions</option></x-ui.field><x-ui.button type="submit">Create draft</x-ui.button>
        </form>
        <x-ui.card><ul class="divide-y divide-line">@forelse($versions as $version)<li class="flex flex-wrap items-center justify-between gap-3 py-4 text-ink"><div class="min-w-0 break-words"><strong>{{ $version->version_code }}</strong><p class="text-sm text-ink-muted">{{ str($version->status)->replace('_', ' ')->title() }} · {{ $version->questions_count }}/{{ \App\Services\ResearchFormBlueprint::total($version->research_form) }} questions</p></div><x-ui.button variant="secondary" :href="route('research-editor.edit', $version)">Open version</x-ui.button></li>@empty<li class="py-6 text-ink-muted">Create a draft to begin entering questions.</li>@endforelse</ul></x-ui.card>
    </div>
</x-app-layout>
