<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Leaderboard" description="Informational XP rankings within your cohort." />
    </x-slot>

    <div class="pt-6 pb-8">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if ($cohort === null)
                <section
                    class="rounded-3xl border-2 border-line bg-surface p-8 text-center shadow-sm sm:p-12"
                    aria-labelledby="no-cohort-heading"
                >
                    <div
                        class="text-5xl"
                        aria-hidden="true"
                    >
                        🏆
                    </div>

                    <h3
                        id="no-cohort-heading"
                        class="mt-4 font-display text-xl font-bold text-ink"
                    >
                        No cohort assigned
                    </h3>

                    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-ink-muted">
                        You are not yet assigned to a cohort.
                        Contact your administrator to be linked
                        to your batch.
                    </p>

                    <a
                        href="{{ route('dashboard') }}"
                        class="mt-6 inline-flex rounded-2xl bg-primary px-5 py-3 font-display text-sm font-bold text-white transition hover:bg-primary-lip focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/25"
                    >
                        Back to Dashboard
                    </a>
                </section>
            @else
                <section
                    class="rounded-3xl border-2 border-line bg-surface p-6 shadow-sm"
                    aria-labelledby="cohort-heading"
                >
                    <p class="font-display text-xs font-bold uppercase tracking-wider text-ink-muted">
                        Your cohort
                    </p>

                    <h3
                        id="cohort-heading"
                        class="mt-1 font-display text-2xl font-bold text-ink"
                    >
                        {{ $cohort->cohort_name }}
                    </h3>

                    <p class="mt-1 text-sm text-ink-muted">
                        {{ number_format($totalStudents) }}
                        {{ \Illuminate\Support\Str::plural('verified student', $totalStudents) }}
                        ranked
                    </p>
                </section>

                @if ($currentRow !== null)
                    <section
                        class="rounded-3xl border-2 border-primary/25 bg-primary-tint/40 p-6 shadow-sm"
                        aria-labelledby="your-rank-heading"
                    >
                        <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p
                                    id="your-rank-heading"
                                    class="font-display text-xs font-bold uppercase tracking-wider text-ink-muted"
                                >
                                    Your rank
                                </p>

                                <div class="mt-1 flex items-baseline gap-3">
                                    <span class="font-display text-4xl font-extrabold text-primary">
                                        #{{ $currentRank }}
                                    </span>

                                    <span class="text-sm text-ink-muted">
                                        of {{ number_format($totalStudents) }}
                                    </span>
                                </div>
                            </div>

                            <div class="sm:text-right">
                                <p class="text-xs font-semibold uppercase tracking-wide text-ink-muted">
                                    Total XP
                                </p>

                                <p class="mt-1 font-display text-3xl font-extrabold text-ink">
                                    {{ number_format($currentRow['total_xp']) }}
                                </p>

                                @if ($currentRow['current_level'] !== null)
                                    <p class="mt-1 text-xs text-ink-muted">
                                        Level {{ $currentRow['current_level'] }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    </section>
                @endif

                @if ($rows->isEmpty())
                    <section
                        class="rounded-3xl border-2 border-line bg-surface p-8 text-center shadow-sm"
                    >
                        <h3 class="font-display text-lg font-bold text-ink">
                            No eligible students
                        </h3>

                        <p class="mt-2 text-sm text-ink-muted">
                            No verified student accounts are currently
                            available for this cohort.
                        </p>
                    </section>
                @else
                    @php
                        $medals = [
                            1 => '🥇',
                            2 => '🥈',
                            3 => '🥉',
                        ];
                    @endphp

                    <section
                        class="overflow-hidden rounded-3xl border-2 border-line bg-surface shadow-sm"
                        aria-labelledby="ranking-heading"
                    >
                        <div class="border-b border-line px-5 py-4">
                            <h3
                                id="ranking-heading"
                                class="font-display text-lg font-bold text-ink"
                            >
                                Cohort rankings
                            </h3>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <caption class="sr-only">
                                    Students ranked by total XP within
                                    {{ $cohort->cohort_name }}
                                </caption>

                                <thead class="bg-surface-2 text-left text-xs font-bold uppercase tracking-wide text-ink-muted">
                                    <tr>
                                        <th
                                            scope="col"
                                            class="w-20 px-4 py-3 text-center"
                                        >
                                            Rank
                                        </th>

                                        <th
                                            scope="col"
                                            class="min-w-44 px-4 py-3"
                                        >
                                            Student
                                        </th>

                                        <th
                                            scope="col"
                                            class="px-4 py-3 text-right"
                                        >
                                            Level
                                        </th>

                                        <th
                                            scope="col"
                                            class="px-4 py-3 text-right"
                                        >
                                            Total XP
                                        </th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-line">
                                    @foreach ($rows as $row)
                                        @php
                                            $isSelf = $currentRow !== null
                                                && $row['user_id']
                                                    === $currentRow['user_id'];
                                        @endphp

                                        <tr
                                            @class([
                                                'transition',
                                                'bg-primary-tint/50 font-semibold' => $isSelf,
                                                'hover:bg-surface-2' => ! $isSelf,
                                            ])
                                        >
                                            <td class="px-4 py-3 text-center">
                                                @if (isset($medals[$row['rank']]))
                                                    <span
                                                        class="text-2xl"
                                                        aria-hidden="true"
                                                    >
                                                        {{ $medals[$row['rank']] }}
                                                    </span>

                                                    <span class="sr-only">
                                                        Rank {{ $row['rank'] }}
                                                    </span>
                                                @else
                                                    <span class="font-display font-bold text-ink">
                                                        #{{ $row['rank'] }}
                                                    </span>
                                                @endif
                                            </td>

                                            <td class="px-4 py-3 text-ink">
                                                {{ $row['display_name'] }}

                                                @if ($isSelf)
                                                    <span class="ml-2 text-xs font-bold text-primary">
                                                        (you)
                                                    </span>
                                                @endif
                                            </td>

                                            <td class="px-4 py-3 text-right text-ink-muted">
                                                @if ($row['current_level'] !== null)
                                                    L{{ $row['current_level'] }}
                                                @else
                                                    <span aria-label="No level">—</span>
                                                @endif
                                            </td>

                                            <td class="px-4 py-3 text-right font-display font-bold text-ink">
                                                {{ number_format($row['total_xp']) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endif

                <p class="text-center text-xs leading-5 text-ink-muted">
                    Students with equal XP share the same rank.
                    Rankings are informational only; no XP, badge,
                    or other reward is awarded for leaderboard position.
                </p>

                <div class="text-center">
                    <a
                        href="{{ route('dashboard') }}"
                        class="inline-flex rounded-2xl border-2 border-line bg-surface px-5 py-3 font-display text-sm font-bold text-ink transition hover:bg-surface-2 focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/25"
                    >
                        Back to Dashboard
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
