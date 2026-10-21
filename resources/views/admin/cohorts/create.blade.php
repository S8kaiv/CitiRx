<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-display text-xl sm:text-2xl lg:text-3xl font-extrabold tracking-tight text-slate-900">
                Create Cohort
            </h2>
            <p class="hidden sm:block text-xs font-semibold uppercase tracking-wider text-muted-ink">
                Create a new pharmacy student batch
            </p>
        </div>
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
