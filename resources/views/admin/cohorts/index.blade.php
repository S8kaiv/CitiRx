<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-display text-xl sm:text-2xl lg:text-3xl font-extrabold tracking-tight text-slate-900">
                    Cohort Management
                </h2>
                <p class="hidden sm:block text-xs font-semibold uppercase tracking-wider text-muted-ink">
                    Batches, faculty assignments and post-test schedules
                </p>
            </div>

            <a href="{{ route('admin.cohorts.create') }}" style="--lip: rgb(var(--color-primary-lip));"
                class="btn-press rounded-xl bg-primary px-4 py-2 font-display text-sm font-bold text-white shadow-md">
                Create Cohort
            </a>
        </div>
    </x-slot>

    <div class="py-6 font-sans text-slate-900 antialiased">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div role="status" class="rounded-2xl border-2 border-b-4 border-emerald-300 bg-emerald-50 p-4 font-display text-sm font-bold text-emerald-800 shadow-2xs">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div role="alert" class="rounded-2xl border-2 border-b-4 border-rose-300 bg-rose-50 p-4 font-display text-sm font-bold text-rose-800 shadow-2xs">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Desktop table --}}
            <div class="hidden overflow-hidden rounded-3xl border-2 border-b-4 border-slate-200 bg-white shadow-sm md:block">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-xs font-extrabold uppercase tracking-wider text-muted-ink">
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
                                        'open' => 'bg-emerald-100 text-emerald-800',
                                        'scheduled' => 'bg-sky-100 text-sky-800',
                                        'ended' => 'bg-slate-100 text-slate-700',
                                        'archived' => 'bg-rose-100 text-rose-800',
                                        default => 'bg-amber-100 text-amber-800',
                                    };
                                @endphp

                                <tr class="hover:bg-slate-50/70">
                                    <td class="px-5 py-4">
                                        <div class="font-display font-black text-slate-900">{{ $cohort->cohort_name }}</div>
                                        <div class="text-xs text-muted-ink">{{ $cohort->academic_year }}</div>
                                    </td>

                                    <td class="px-5 py-4 text-slate-700">
                                        {{ $cohort->target_phle_date?->format('M j, Y') ?? 'Not set' }}
                                    </td>

                                    <td class="px-5 py-4 font-display font-black text-slate-900">{{ $cohort->students_count }}</td>

                                    <td class="px-5 py-4 font-display font-black text-slate-900">{{ $cohort->faculty_count }}</td>

                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded-full px-2.5 py-1 font-display text-xs font-bold {{ $windowClasses }}">
                                            {{ ucfirst($windowStatus) }}
                                        </span>
                                    </td>

                                    <td class="px-5 py-4">
                                        @if ($cohort->archived_at)
                                            <span class="inline-flex rounded-full bg-rose-100 px-2.5 py-1 font-display text-xs font-bold text-rose-700">Archived</span>
                                        @else
                                            <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 font-display text-xs font-bold text-emerald-700">Active</span>
                                        @endif
                                    </td>

                                    <td class="px-5 py-4 text-right">
                                        <a href="{{ route('admin.cohorts.show', ['cohort' => $cohort]) }}" style="--lip: #CBD5E1;"
                                            class="btn-press inline-flex rounded-xl border-2 border-b-4 border-slate-300 bg-white px-3 py-1.5 font-display text-xs font-bold text-primary shadow-2xs transition hover:bg-primary-tint">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-xs font-semibold text-muted-ink">
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

                    <article class="flex flex-col justify-between rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-5 shadow-sm">
                        <div>
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="font-display text-lg font-black text-slate-900">{{ $cohort->cohort_name }}</h3>
                                    <p class="text-xs font-semibold text-muted-ink">{{ $cohort->academic_year }}</p>
                                </div>

                                <span class="rounded-full px-2.5 py-1 font-display text-xs font-bold {{ $cohort->archived_at ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700' }}">
                                    {{ $cohort->archived_at ? 'Archived' : 'Active' }}
                                </span>
                            </div>

                            <dl class="mt-4 grid grid-cols-2 gap-3">
                                <div class="rounded-2xl border-2 border-slate-100 bg-slate-50 p-3">
                                    <dt class="text-[10px] font-extrabold uppercase tracking-wider text-muted-ink">Students</dt>
                                    <dd class="mt-1 font-display text-xl font-black text-slate-900">{{ $cohort->students_count }}</dd>
                                </div>

                                <div class="rounded-2xl border-2 border-slate-100 bg-slate-50 p-3">
                                    <dt class="text-[10px] font-extrabold uppercase tracking-wider text-muted-ink">Faculty</dt>
                                    <dd class="mt-1 font-display text-xl font-black text-slate-900">{{ $cohort->faculty_count }}</dd>
                                </div>

                                <div class="col-span-2 rounded-2xl border-2 border-slate-100 bg-slate-50 p-3">
                                    <dt class="text-[10px] font-extrabold uppercase tracking-wider text-muted-ink">Post-test</dt>
                                    <dd class="mt-1 font-display text-sm font-black capitalize text-slate-900">{{ $windowStatus }}</dd>
                                </div>
                            </dl>
                        </div>

                        <div class="mt-5">
                            <a href="{{ route('admin.cohorts.show', ['cohort' => $cohort]) }}" style="--lip: rgb(var(--color-primary-lip));"
                                class="btn-press block w-full rounded-2xl bg-primary py-3 text-center font-display text-xs font-black uppercase tracking-wider text-white shadow-md">
                                View Cohort
                            </a>
                        </div>
                    </article>
                @empty
                    <div class="rounded-3xl border-2 border-dashed border-slate-300 bg-white p-10 text-center text-xs font-semibold text-muted-ink">
                        No cohorts have been created.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
