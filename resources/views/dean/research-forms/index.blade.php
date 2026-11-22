<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Dean review" description="Complete research versions · Form A and Form B" />
    </x-slot>

    <div class="mx-auto max-w-5xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        @if (session('status'))
            <x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>
        @endif

        <x-ui.card>
            <h2 class="font-display text-xl font-extrabold text-ink">Research versions awaiting review or publication</h2>
            <p class="mt-2 text-sm text-ink-muted">
                Review the imported questions, answer key, rationale, sources, and rights before approving the complete form.
            </p>

            <div class="mt-5 space-y-3">
                @forelse ($versions as $version)
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-line bg-surface-2 p-4">
                        <div>
                            <p class="font-bold text-ink">{{ $version->version_code }}</p>
                            <p class="mt-1 text-sm text-ink-muted">
                                {{ $version->questions_count }}/{{ \App\Services\ResearchFormBlueprint::total($version->research_form) }} questions ·
                                {{ str($version->status)->replace('_', ' ')->title() }}
                            </p>
                        </div>

                        <x-ui.button variant="secondary" :href="route('dean.research-forms.show', $version)">
                            Review form
                        </x-ui.button>
                    </div>
                @empty
                    <p class="rounded-2xl bg-surface-2 p-5 text-sm text-ink-muted">
                        No research version is awaiting Dean review. Submit a complete draft from the Form editor.
                    </p>
                @endforelse
            </div>
        </x-ui.card>
    </div>
</x-app-layout>
