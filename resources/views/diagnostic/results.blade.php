<x-app-layout>
    {{-- Header --}}
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="font-display text-xl font-bold leading-tight text-ink">
                    {{ __('Diagnostic Results') }}
                </h1>
                <p class="font-display text-[10px] font-bold uppercase tracking-wider text-ink-muted">
                    PhLE Baseline Mastery Calibration
                </p>
            </div>

            <a href="{{ route('dashboard') }}"
                class="font-display text-xs font-bold text-ink-muted transition hover:text-primary">
                Exit to Dashboard
            </a>
        </div>
    </x-slot>

    {{-- Main --}}
    <div class="py-8 sm:py-10 font-sans antialiased text-ink">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            {{-- Level Up Notification Banner --}}
            <x-level-up-alert />

            {{-- Overall Score Card --}}
            @php
                $overallPercentage =
                    $session->total_items > 0 ? round((100 * $session->correct_items) / $session->total_items) : 0;
            @endphp

            <div class="rounded-3xl border-2 border-b-4 border-line/90 bg-surface p-6 sm:p-8 shadow-xs">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
                    <div>
                        <div class="flex items-center gap-2">
                            <span
                                class="inline-flex items-center gap-1.5 rounded-lg bg-primary-tint px-2.5 py-1 font-display text-[11px] font-bold uppercase tracking-wider text-primary">
                                <span class="h-1.5 w-1.5 rounded-full bg-primary"></span>
                                Baseline Established
                            </span>
                            @if ($session->completed_at)
                                <span class="font-bold text-slate-300">&bull;</span>
                                <span class="font-mono text-xs text-ink-muted">
                                    {{ $session->completed_at->diffForHumans() }}
                                </span>
                            @endif
                        </div>

                        <h2 class="mt-2 font-display text-2xl sm:text-3xl font-black tracking-tight text-ink">
                            Diagnostic Assessment Complete
                        </h2>
                        <p class="mt-1 text-sm text-ink-muted max-w-lg leading-relaxed">
                            Your performance has calibrated initial parameters across all six examination subjects
                        </p>
                    </div>

                    <div
                        class="grid grid-cols-2 gap-4 border-t border-line pt-4 sm:border-l sm:border-t-0 sm:pl-8 sm:pt-0">
                        {{-- Raw score --}}
                        <div class="text-center sm:text-right">
                            <span class="block font-mono text-[10px] font-bold uppercase tracking-wider text-ink-muted">
                                Raw Accuracy
                            </span>

                            <div class="font-display text-3xl font-black tracking-tight text-primary sm:text-4xl">
                                {{ $overallPercentage }}%
                            </div>
                        </div>

                        {{-- Official TOS-weighted score --}}
                        <div class="text-center sm:text-right">
                            <span class="block font-mono text-[10px] font-bold uppercase tracking-wider text-ink-muted">
                                TOS-Weighted Score
                            </span>

                            <div class="font-display text-3xl font-black tracking-tight text-clinical sm:text-4xl">
                                {{ number_format($tosWeightedScore, 2) }}%
                            </div>
                        </div>

                        <div class="col-span-2 text-center sm:text-right">
                            <span
                                class="inline-flex items-center gap-1 rounded-full bg-surface-2 px-3 py-1 font-display text-xs font-extrabold text-ink">
                                {{ $session->correct_items }}
                                /
                                {{ $session->total_items }}
                                Correct
                            </span>
                        </div>
                    </div>
                </div>

                <div class="mt-6 pt-5 border-t border-line">
                    <div class="h-3 w-full overflow-hidden rounded-full bg-surface-2 p-0.5 ring-1 ring-inset ring-slate-200/60"
                        role="progressbar" aria-label="Raw diagnostic accuracy" aria-valuemin="0" aria-valuemax="100"
                        aria-valuenow="{{ $overallPercentage }}">
                        <div class="h-full rounded-full bg-primary transition-all duration-700"
                            style="width: {{ $overallPercentage }}%"></div>
                    </div>
                </div>
            </div>

            {{-- Domain Breakdown Table --}}
            <div class="rounded-3xl border-2 border-b-4 border-line/90 bg-surface p-6 sm:p-8 shadow-xs">
                <div class="flex items-center justify-between gap-4 mb-4">
                    <div>
                        <h3 class="font-display text-lg font-bold text-ink">
                            Performance by Subject
                        </h3>
                        <p class="text-xs text-ink-muted">
                            Breakdown of accuracy according to the PhLE Table of Specifications.
                        </p>
                    </div>
                    <span
                        class="hidden sm:inline-flex items-center rounded-lg bg-clinical-tint px-2.5 py-1 font-display text-xs font-bold text-clinical-ink">
                        6 Subjects
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left font-sans text-sm">
                        <thead>
                            <tr
                                class="border-b-2 border-line font-display text-[11px] font-bold uppercase tracking-wider text-ink-muted">
                                <th class="pb-3 pr-4">Subject</th>
                                <th class="pb-3 px-4 text-center">Score</th>
                                <th class="pb-3 px-4 text-right">Accuracy</th>
                                <th class="pb-3 pl-4 text-right w-36 hidden sm:table-cell">Distribution</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-line">
                            @foreach ($perDomain as $domain)
                                @php
                                    $domainPercentage =
                                        $domain['total'] > 0 ? round((100 * $domain['correct']) / $domain['total']) : 0;
                                @endphp
                                <tr class="transition-colors hover:bg-surface-2/70">
                                    <td class="py-3.5 pr-4 font-semibold text-ink">
                                        {{ $domain['domain_name'] }}
                                    </td>

                                    <td class="py-3.5 px-4 text-center font-mono text-xs text-ink-muted">
                                        {{ $domain['correct'] }} / {{ $domain['total'] }}
                                    </td>

                                    <td class="py-3.5 px-4 text-right">
                                        <span
                                            class="inline-flex items-center rounded-md px-2 py-0.5 font-display text-xs font-bold {{ $domainPercentage >= 75 ? 'bg-strong-tint text-strong-ink' : ($domainPercentage >= 50 ? 'bg-amber-50 text-amber-700' : 'bg-weak-tint text-weak-ink') }}">
                                            {{ $domainPercentage }}%
                                        </span>
                                    </td>

                                    <td class="py-3.5 pl-4 text-right hidden sm:table-cell">
                                        <div
                                            class="h-2 w-full overflow-hidden rounded-full bg-surface-2 ring-1 ring-inset ring-slate-200/60">
                                            <div class="h-full rounded-full {{ $domainPercentage >= 75 ? 'bg-strong' : ($domainPercentage >= 50 ? 'bg-amber-500' : 'bg-weak') }}"
                                                style="width: {{ $domainPercentage }}%"></div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Initial BKT Mastery Estimates (NEW COMPACT ACCORDION DESIGN) --}}
            <div class="rounded-3xl border-2 border-b-4 border-line/90 bg-surface p-6 sm:p-8 shadow-xs">
                <div class="mb-5">
                    <h3 class="font-display text-lg font-bold text-ink">
                        Initial Mastery Estimates (BKT)
                    </h3>
                    <p class="text-xs sm:text-sm text-ink-muted leading-relaxed mt-1">
                        These values anchor your Knowledge Tracing model. Posterior mastery estimates update dynamically
                        following every response in daily practice.
                    </p>
                </div>

                {{-- Accordion Container --}}
                <div class="space-y-3">
                    @foreach ($masteryResults->groupBy('domain_name') as $domainName => $domainResults)
                        <section x-data="{
                            expanded: @js($loop->first)
                        }" x-id="['mastery-panel']"
                            class="overflow-hidden rounded-2xl border-2 border-line bg-surface transition duration-200"
                            :class="expanded
                                ?
                                'border-primary/20 shadow-sm' :
                                'hover:border-line'">
                            <button type="button" @click="expanded = !expanded" :aria-expanded="expanded.toString()"
                                :aria-controls="$id('mastery-panel')"
                                class="flex w-full items-center justify-between p-4 text-left transition-colors hover:bg-surface-2"
                                :class="expanded
                                    ?
                                    'bg-primary-tint/30' :
                                    ''">
                                <span class="flex items-center gap-3">
                                    <span
                                        class="font-display text-sm font-bold uppercase tracking-wider text-ink">
                                        {{ $domainName }}
                                    </span>

                                    <span
                                        class="rounded-full bg-slate-200 px-2 py-0.5 font-mono text-[10px] font-bold text-ink-muted">
                                        {{ $domainResults->count() }}
                                        Competencies
                                    </span>
                                </span>

                                <svg :class="expanded
                                    ?
                                    'rotate-180 text-primary' :
                                    'text-ink-muted'"
                                    class="h-5 w-5 transform transition-transform duration-200" fill="none"
                                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                </svg>
                            </button>

                            <div x-cloak x-show="expanded" :id="$id('mastery-panel')"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 -translate-y-2"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                class="border-t-2 border-line bg-surface-2/50 p-4">
                                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                    @foreach ($domainResults as $result)
                                        @php
                                            $masteryPercentage = round(100 * $result['mastery']);
                                        @endphp

                                        <div
                                            class="flex items-center justify-between gap-4 rounded-xl border-2 border-line/60 bg-surface p-3 shadow-2xs">
                                            <div class="min-w-0 flex-1">
                                                <h5 class="truncate font-display text-xs font-bold text-ink"
                                                    title="{{ $result['competency_title'] }}">
                                                    {{ $result['competency_title'] }}
                                                </h5>

                                                <div class="mt-1.5 flex items-center gap-2.5">
                                                    <div
                                                        class="h-1.5 flex-1 overflow-hidden rounded-full bg-surface-2 ring-1 ring-inset ring-slate-200/50">
                                                        <div class="h-full rounded-full bg-primary"
                                                            style="width: {{ $masteryPercentage }}%"></div>
                                                    </div>

                                                    <span
                                                        class="w-8 text-right font-mono text-[10px] font-bold text-ink-muted">
                                                        {{ $masteryPercentage }}%
                                                    </span>
                                                </div>
                                            </div>

                                            <div
                                                class="flex shrink-0 items-center justify-center rounded-lg border border-line bg-surface-2 px-2 py-1">
                                                <span class="font-mono text-[9px] font-bold uppercase text-ink-muted">
                                                    {{ $result['correct'] }}
                                                    /
                                                    {{ $result['total'] }}
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </section>
                    @endforeach
                </div>
            </div>

            {{-- Action CTA --}}
            <div class="flex flex-col items-center justify-center gap-3 pb-6 pt-2 sm:flex-row">
                <x-ui.button
                    :href="route('dashboard')"
                    class="w-full sm:w-auto"
                >
                    Return to Dashboard

                    <svg
                        class="h-4 w-4"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.5"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="m8.25 4.5 7.5 7.5-7.5 7.5"
                        />
                    </svg>
                </x-ui.button>

                <x-ui.button
                    :href="route('practice.intro')"
                    variant="secondary"
                    class="w-full sm:w-auto"
                >
                    Start Adaptive Practice
                </x-ui.button>
            </div>

        </div>
    </div>
</x-app-layout>
