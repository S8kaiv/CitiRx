<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-display text-xl font-bold text-slate-900">
                    Cohort Management
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Manage batches, faculty assignments, and post-test schedules.
                </p>
            </div>

            <a
                href="{{ route('admin.cohorts.create') }}"
                class="rounded-xl bg-primary px-4 py-2 text-sm
                    font-bold text-white transition hover:opacity-90"
            >
                Create Cohort
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div
                    role="status"
                    class="rounded-2xl border border-green-200
                        bg-green-50 p-4 text-green-800"
                >
                    {{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div
                    role="alert"
                    class="rounded-2xl border border-red-200
                        bg-red-50 p-4 text-red-800"
                >
                    {{ session('error') }}
                </div>
            @endif

            {{-- Desktop table --}}
            <div
                class="hidden overflow-hidden rounded-3xl border-2
                    border-slate-200 bg-white shadow-sm md:block"
            >
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                                <th class="px-5 py-4">Cohort</th>
                                <th class="px-5 py-4">Target PhLE</th>
                                <th class="px-5 py-4">Students</th>
                                <th class="px-5 py-4">Faculty</th>
                                <th class="px-5 py-4">Post-test</th>
                                <th class="px-5 py-4">Status</th>
                                <th class="px-5 py-4"></th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            @forelse ($cohorts as $cohort)
                                @php
                                    $windowStatus = $cohort->postTestWindowStatus();

                                    $windowClasses = match ($windowStatus) {
                                        'open' => 'bg-green-100 text-green-800',
                                        'scheduled' => 'bg-blue-100 text-blue-800',
                                        'ended' => 'bg-slate-100 text-slate-700',
                                        'archived' => 'bg-red-100 text-red-800',
                                        default => 'bg-amber-100 text-amber-800',
                                    };
                                @endphp

                                <tr class="hover:bg-slate-50/70">
                                    <td class="px-5 py-4">
                                        <div class="font-bold text-slate-900">
                                            {{ $cohort->cohort_name }}
                                        </div>

                                        <div class="text-xs text-slate-500">
                                            {{ $cohort->academic_year }}
                                        </div>
                                    </td>

                                    <td class="px-5 py-4 text-slate-700">
                                        {{ $cohort->target_phle_date?->format('M j, Y') ?? 'Not set' }}
                                    </td>

                                    <td class="px-5 py-4 font-semibold text-slate-800">
                                        {{ $cohort->students_count }}
                                    </td>

                                    <td class="px-5 py-4 font-semibold text-slate-800">
                                        {{ $cohort->faculty_count }}
                                    </td>

                                    <td class="px-5 py-4">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-1
                                                text-xs font-bold {{ $windowClasses }}"
                                        >
                                            {{ ucfirst($windowStatus) }}
                                        </span>
                                    </td>

                                    <td class="px-5 py-4">
                                        @if ($cohort->archived_at)
                                            <span class="font-bold text-red-700">
                                                Archived
                                            </span>
                                        @else
                                            <span class="font-bold text-green-700">
                                                Active
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-5 py-4 text-right">
                                        <a
                                            href="{{ route(
                                                'admin.cohorts.show',
                                                ['cohort' => $cohort],
                                            ) }}"
                                            class="font-bold text-primary"
                                        >
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="7"
                                        class="px-6 py-12 text-center text-slate-500"
                                    >
                                        No cohorts have been created.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Mobile cards --}}
            <div class="grid gap-4 md:hidden">
                @forelse ($cohorts as $cohort)
                    @php
                        $windowStatus = $cohort->postTestWindowStatus();
                    @endphp

                    <article
                        class="rounded-3xl border-2 border-slate-200
                            bg-white p-5 shadow-sm"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="font-display font-bold text-slate-900">
                                    {{ $cohort->cohort_name }}
                                </h3>

                                <p class="text-sm text-slate-500">
                                    {{ $cohort->academic_year }}
                                </p>
                            </div>

                            <span
                                class="rounded-full px-2.5 py-1 text-xs font-bold
                                    {{ $cohort->archived_at
                                        ? 'bg-red-100 text-red-800'
                                        : 'bg-green-100 text-green-800' }}"
                            >
                                {{ $cohort->archived_at ? 'Archived' : 'Active' }}
                            </span>
                        </div>

                        <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <dt class="text-xs font-bold uppercase text-slate-500">
                                    Students
                                </dt>
                                <dd class="font-bold text-slate-900">
                                    {{ $cohort->students_count }}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-xs font-bold uppercase text-slate-500">
                                    Faculty
                                </dt>
                                <dd class="font-bold text-slate-900">
                                    {{ $cohort->faculty_count }}
                                </dd>
                            </div>

                            <div class="col-span-2">
                                <dt class="text-xs font-bold uppercase text-slate-500">
                                    Post-test
                                </dt>
                                <dd class="font-bold capitalize text-slate-900">
                                    {{ $windowStatus }}
                                </dd>
                            </div>
                        </dl>

                        <a
                            href="{{ route(
                                'admin.cohorts.show',
                                ['cohort' => $cohort],
                            ) }}"
                            class="mt-4 inline-flex font-bold text-primary"
                        >
                            View Cohort
                        </a>
                    </article>
                @empty
                    <div
                        class="rounded-3xl border-2 border-dashed
                            border-slate-300 bg-white p-10 text-center
                            text-slate-500"
                    >
                        No cohorts have been created.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
