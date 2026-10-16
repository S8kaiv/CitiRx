<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-display text-xl font-bold text-slate-900">
                Create Cohort
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Create a new pharmacy student batch.
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <form
                method="POST"
                action="{{ route('admin.cohorts.store') }}"
                class="rounded-3xl border-2 border-slate-200
                    bg-white p-6 shadow-sm"
            >
                @csrf

                @include('admin.cohorts._form', [
                    'submitLabel' => 'Create Cohort',
                ])
            </form>
        </div>
    </div>
</x-app-layout>
