<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Create cohort" description="Create a pharmacy student batch. Students can be assigned after saving.">
            <x-slot:actions>
                <x-ui.button variant="secondary" :href="route('dean.cohorts.index')">Back to cohorts</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <div class="mx-auto max-w-2xl px-4 py-6 sm:px-6">
        <x-ui.card>
            <form method="POST" action="{{ route('dean.cohorts.store') }}" class="space-y-5">
                @csrf
                <x-ui.field name="cohort_name" label="Cohort name" maxlength="100" required />
                <x-ui.field name="academic_year" label="Academic year" hint="Use YYYY-YYYY, for example 2026-2027." placeholder="2026-2027" maxlength="20" pattern="[0-9]{4}-[0-9]{4}" required />
                <x-ui.field name="target_phle_date" label="Target PhLE date (optional)" type="date" />
                <p class="text-sm text-ink-muted">Creating a cohort does not open a post-test window or change any existing student or Faculty assignment.</p>
                <div class="flex flex-wrap gap-3">
                    <x-ui.button type="submit">Create cohort</x-ui.button>
                    <x-ui.button variant="secondary" :href="route('dean.cohorts.index')">Cancel</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-app-layout>
