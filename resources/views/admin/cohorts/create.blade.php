<x-app-layout surface="admin">
    <x-slot name="header">
        <x-admin.page-header
            title="Create cohort"
            description="Create a pharmacy student batch and configure its academic year and assessment schedule."
        >
            <x-slot:actions>
                <a
                    href="{{ route('admin.cohorts.index') }}"
                    class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-bold text-slate-700 transition-colors hover:bg-slate-50 focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/25"
                >
                    All cohorts
                </a>
            </x-slot:actions>
        </x-admin.page-header>
    </x-slot>

    <div class="py-6 font-sans text-slate-900 antialiased">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('admin.cohorts.store') }}"
                class="rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-5 sm:p-6 shadow-sm">
                @csrf

                @include('admin.cohorts._form', [
                    'submitLabel' => 'Create Cohort',
                ])
            </form>
        </div>
    </div>
</x-app-layout>
