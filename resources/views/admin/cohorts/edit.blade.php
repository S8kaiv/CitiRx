<x-app-layout>
    <x-slot name="header">
        <x-admin.page-header
            title="Edit cohort"
            :description="$cohort->cohort_name
                .' · '
                .$cohort->academic_year"
        >
            <x-slot:actions>
                <a
                    href="{{ route(
                        'admin.cohorts.show',
                        [
                            'cohort' => $cohort,
                        ],
                    ) }}"
                    class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-bold text-slate-700 transition-colors hover:bg-slate-50 focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/25"
                >
                    View cohort
                </a>
            </x-slot:actions>
        </x-admin.page-header>
    </x-slot>

    <div class="py-6 font-sans text-slate-900 antialiased">
        <div class="mx-auto max-w-2xl space-y-5 px-4 sm:px-6 lg:px-8">

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
