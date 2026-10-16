<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-display text-xl font-bold text-slate-900">
                Edit Cohort
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                {{ $cohort->cohort_name }} · {{ $cohort->academic_year }}
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-2xl space-y-5 px-4 sm:px-6 lg:px-8">
            @if (session('error'))
                <div
                    role="alert"
                    class="rounded-2xl border border-red-200
                        bg-red-50 p-4 text-red-800"
                >
                    {{ session('error') }}
                </div>
            @endif

            @if ($cohort->archived_at)
                <div
                    role="alert"
                    class="rounded-2xl border border-amber-200
                        bg-amber-50 p-4 text-amber-800"
                >
                    This cohort is archived. Unarchive it before editing.
                </div>
            @endif

            <form
                method="POST"
                action="{{ route(
                    'admin.cohorts.update',
                    ['cohort' => $cohort],
                ) }}"
                class="rounded-3xl border-2 border-slate-200
                    bg-white p-6 shadow-sm"
            >
                @csrf
                @method('PUT')

                @include('admin.cohorts._form', [
                    'submitLabel' => 'Save Changes',
                ])
            </form>
        </div>
    </div>
</x-app-layout>
