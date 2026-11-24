<x-app-layout surface="admin">
    <x-slot name="header">
        <x-admin.page-header title="Cohorts" />
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        <x-admin.flash />

        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-display text-xl font-extrabold text-ink">Cohort directory</h2>
            <x-ui.button :href="route('admin.cohorts.create')">Create cohort</x-ui.button>
        </div>

        @foreach ([
            'Active cohorts' => $cohorts->whereNull('archived_at'),
            'Archived cohorts' => $cohorts->whereNotNull('archived_at'),
        ] as $heading => $group)
            @continue($heading === 'Archived cohorts' && $group->isEmpty())

            <section class="space-y-3" aria-labelledby="cohort-group-{{ $loop->index }}">
                <h3 id="cohort-group-{{ $loop->index }}" class="font-display font-extrabold text-ink">
                    {{ $heading }}
                </h3>

                <ul class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @forelse ($group as $cohort)
                        @php($window = $cohort->postTestWindowStatus())

                        <li class="flex min-w-0 flex-col rounded-2xl border border-line bg-surface">
                            <div class="flex-1 space-y-4 p-4 sm:p-5">
                                <div>
                                    <p class="text-sm text-ink-muted">{{ $cohort->academic_year }}</p>
                                    <h4 class="mt-1 break-words font-display text-lg font-extrabold text-ink">
                                        {{ $cohort->cohort_name }}
                                    </h4>
                                </div>

                                <dl class="grid grid-cols-2 gap-3 rounded-xl bg-surface-2 p-3">
                                    <div class="min-w-0">
                                        <dt class="text-sm text-ink-muted">Students</dt>
                                        <dd class="text-xl font-extrabold tabular-nums text-ink">
                                            {{ number_format($cohort->students_count) }}
                                        </dd>
                                    </div>
                                    <div class="min-w-0">
                                        <dt class="text-sm text-ink-muted">Faculty</dt>
                                        <dd class="text-xl font-extrabold tabular-nums text-ink">
                                            {{ number_format($cohort->faculty_count) }}
                                        </dd>
                                    </div>
                                </dl>

                                <dl class="grid grid-cols-2 gap-3 text-sm">
                                    <div class="min-w-0">
                                        <dt class="text-ink-muted">Target PhLE</dt>
                                        <dd class="mt-1 font-semibold text-ink">
                                            {{ $cohort->target_phle_date?->format('M j, Y') ?? 'Not set' }}
                                        </dd>
                                    </div>
                                    <div class="min-w-0">
                                        <dt class="text-ink-muted">Form B window</dt>
                                        <dd class="mt-1">
                                            <x-ui.badge :variant="match ($window) {
                                                'open' => 'success',
                                                'scheduled' => 'info',
                                                default => 'neutral',
                                            }">
                                                {{ $window === 'closed' ? 'Unscheduled' : ucfirst($window) }}
                                            </x-ui.badge>
                                        </dd>
                                    </div>
                                </dl>
                            </div>

                            <div class="border-t border-line p-3 sm:px-5">
                                <x-ui.button variant="ghost" :href="route('admin.cohorts.show', $cohort)"
                                    :aria-label="'View cohort '.$cohort->cohort_name">
                                    View cohort
                                </x-ui.button>
                            </div>
                        </li>
                    @empty
                        <li class="col-span-full rounded-2xl border border-line bg-surface p-6 text-sm text-ink-muted">
                            No active cohorts.
                        </li>
                    @endforelse
                </ul>
            </section>
        @endforeach
    </div>
</x-app-layout>
