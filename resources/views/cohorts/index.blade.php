<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            :title="auth()->user()->role === 'faculty' ? 'My cohort' : 'Cohorts'"
            :description="auth()->user()->role === 'faculty'
                ? 'View your assigned cohort, its students, and assessment schedule.'
                : 'Create cohorts, assign students, and browse cohort rosters.'"
        >
            @if (auth()->user()->role === 'dean')
                <x-slot:actions>
                    <x-ui.button variant="secondary" :href="route('dean.cohorts.assignments')">Assign students</x-ui.button>
                    <x-ui.button :href="route('dean.cohorts.create')">Create cohort</x-ui.button>
                </x-slot:actions>
            @endif
        </x-ui.page-header>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        @if (auth()->user()->role === 'dean')
            @if (session('status'))<x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>@endif
            <p class="text-sm text-ink-muted">Create cohorts and bulk-assign eligible students. Assessment-window controls remain administrator-only.</p>
        @else
            <p class="text-sm text-ink-muted">Cohort details are read-only here. Contact an administrator for assignment or schedule changes.</p>
        @endif

        <x-ui.card :padded="false">
            <ul class="divide-y divide-line">
                @forelse ($cohorts as $cohort)
                    <li class="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                        <div class="min-w-0 space-y-2">
                            <h2 class="break-words text-lg font-bold text-ink">{{ $cohort->cohort_name }}</h2>
                            <p class="break-words text-sm text-ink-muted">{{ $cohort->academic_year }}</p>
                            <div class="flex flex-wrap gap-x-5 gap-y-2 text-sm text-ink">
                                <span>{{ $cohort->students_count }} students</span>
                                <span>{{ $cohort->faculty_count }} faculty</span>
                                <x-ui.badge :variant="$cohort->archived_at ? 'neutral' : 'success'">
                                    {{ $cohort->archived_at ? 'Archived' : 'Active' }}
                                </x-ui.badge>
                            </div>
                        </div>
                        <x-ui.button variant="secondary" :href="route($routePrefix.'.show', $cohort)"
                            :aria-label="'View cohort '.$cohort->cohort_name" class="shrink-0">
                            View cohort
                        </x-ui.button>
                    </li>
                @empty
                    <li class="space-y-2 p-6 text-sm">
                        @if (auth()->user()->role === 'faculty')
                            <h2 class="font-bold text-ink">No cohort assigned</h2>
                            <p class="text-ink-muted">Ask an administrator to assign your faculty account to a cohort.</p>
                        @else
                            <h2 class="font-bold text-ink">No cohorts available</h2>
                            <p class="text-ink-muted">Create your first cohort, then assign eligible students.</p>
                        @endif
                    </li>
                @endforelse
            </ul>
        </x-ui.card>

        {{ $cohorts->links() }}
    </div>
</x-app-layout>
