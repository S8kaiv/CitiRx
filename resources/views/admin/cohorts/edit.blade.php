<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-display text-xl sm:text-2xl lg:text-3xl font-extrabold tracking-tight text-slate-900">
                Edit Cohort
            </h2>
            <p class="hidden sm:block text-xs font-semibold uppercase tracking-wider text-muted-ink">
                {{ $cohort->cohort_name }} · {{ $cohort->academic_year }}
            </p>
        </div>
    </x-slot>

    <div class="py-6 font-sans text-slate-900 antialiased">
        <div class="mx-auto max-w-2xl space-y-5 px-4 sm:px-6 lg:px-8">
            @if (session('error'))
                <div role="alert" class="rounded-2xl border-2 border-b-4 border-rose-300 bg-rose-50 p-4 font-display text-sm font-bold text-rose-800 shadow-2xs">
                    {{ session('error') }}
                </div>
            @endif

            @if ($cohort->archived_at)
                <div role="alert" class="rounded-2xl border-2 border-b-4 border-amber-300 bg-amber-50 p-4 font-display text-sm font-bold text-amber-800 shadow-2xs">
                    This cohort is archived. Unarchive it before editing.
                </div>
            @endif

            <form method="POST" action="{{ route('admin.cohorts.update', ['cohort' => $cohort]) }}"
                class="rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-5 sm:p-6 shadow-sm">
                @csrf
                @method('PUT')

                @include('admin.cohorts._form', [
                    'submitLabel' => 'Save Changes',
                ])
            </form>
        </div>
    </div>
</x-app-layout>
