<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Practice questions" description="Author questions for adaptive practice. These items are separate from research Forms A and B.">
            <x-slot:actions>
                <x-ui.button :href="route($routePrefix.'.create')">Add practice question</x-ui.button>
                <x-ui.button variant="secondary" :href="route($routePrefix.'.imports.create')">Import practice CSV</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        @if (session('status'))<x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>@endif
        <form method="GET" action="{{ route($routePrefix.'.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <x-ui.field name="q" label="Search practice questions" :value="$search" maxlength="100" class="sm:min-w-80" />
            <x-ui.button type="submit" variant="secondary">Search</x-ui.button>
            <x-ui.button variant="ghost" :href="route($routePrefix.'.index')">Clear search</x-ui.button>
        </form>

        <x-ui.card :padded="false">
            <ul class="divide-y divide-line">
                @forelse ($questions as $question)
                    <li class="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                        <div class="min-w-0 space-y-2">
                            <h2 class="break-words font-semibold text-ink">{{ \Illuminate\Support\Str::limit($question->question_text, 180) }}</h2>
                            <p class="break-words text-sm text-ink-muted">{{ $question->competency?->domain?->domain_name }} · {{ $question->competency?->competency_code }}</p>
                            <div class="flex flex-wrap gap-2">
                                <x-ui.badge :variant="$question->is_active ? 'success' : 'neutral'">{{ $question->is_active ? 'Active in practice pool' : 'Inactive' }}</x-ui.badge>
                                @if ($question->contentIsFrozen())<x-ui.badge>Content locked</x-ui.badge>@endif
                            </div>
                        </div>
                        <x-ui.button variant="secondary" :href="route($routePrefix.'.show', $question)" class="shrink-0">View question</x-ui.button>
                    </li>
                @empty
                    <li class="space-y-2 p-6 text-sm">
                        <h2 class="font-bold text-ink">{{ $search !== '' ? 'No practice questions match this search.' : 'No practice questions yet.' }}</h2>
                        <p class="text-ink-muted">{{ $search !== '' ? 'Try another search or clear the filter.' : 'Add your first question with its answer, rationale, competency, and source.' }}</p>
                    </li>
                @endforelse
            </ul>
        </x-ui.card>

        {{ $questions->links() }}
    </div>
</x-app-layout>
