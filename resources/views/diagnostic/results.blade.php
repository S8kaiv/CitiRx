<x-app-layout>
    {{-- Header --}}
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="font-display text-xl font-bold leading-tight text-slate-900">
                    {{ __('Diagnostic Results') }}
                </h1>
                <p class="font-display text-[10px] font-bold uppercase tracking-wider text-muted-ink">
                    PhLE Baseline Mastery Calibration
                </p>
            </div>

            <a href="{{ route('dashboard') }}" 
               class="font-display text-xs font-bold text-muted-ink transition hover:text-primary">
                Exit to Dashboard
            </a>
        </div>
    </x-slot>

    {{-- Main --}}
    <div class="py-8 sm:py-10 font-sans antialiased text-slate-900">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <x-level-up-alert />

            {{-- Overall Score Card --}}
            @php
                $overallPercentage = $session->total_items > 0
                    ? round((100 * $session->correct_items) / $session->total_items)
                    : 0;
            @endphp

            <div class="rounded-3xl border-2 border-b-4 border-slate-200/90 bg-white p-6 sm:p-8 shadow-xs">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 rounded-lg bg-primary-tint px-2.5 py-1 font-display text-[11px] font-bold uppercase tracking-wider text-primary">
                                <span class="h-1.5 w-1.5 rounded-full bg-primary"></span>
                                Baseline Established
                            </span>
                            @if ($session->completed_at)
                                <span class="font-bold text-slate-300">&bull;</span>
                                <span class="font-mono text-xs text-muted-ink">
                                    {{ $session->completed_at->diffForHumans() }}
                                </span>
                            @endif
                        </div>

                        <h2 class="mt-2 font-display text-2xl sm:text-3xl font-black tracking-tight text-slate-900">
                            Diagnostic Assessment Complete
                        </h2>
                        <p class="mt-1 text-sm text-slate-600 max-w-lg leading-relaxed">
                            Your performance has calibrated initial parameters across all six examination domains to personalize your adaptive practice loops.
                        </p>
                    </div>

                    <div class="flex sm:flex-col items-center sm:items-end justify-between border-t sm:border-t-0 sm:border-l border-slate-100 pt-4 sm:pt-0 sm:pl-8 shrink-0">
                        <div class="text-left sm:text-right">
                            <span class="block font-mono text-xs font-bold uppercase tracking-wider text-muted-ink">
                                Overall Accuracy
                            </span>
                            <div class="font-display text-4xl sm:text-5xl font-black text-primary tracking-tight">
                                {{ $overallPercentage }}%
                            </div>
                        </div>

                        <div class="mt-1 inline-flex items-center gap-1 rounded-full bg-slate-100 px-3 py-1 font-display text-xs font-extrabold text-slate-700">
                            {{ $session->correct_items }} / {{ $session->total_items }} Correct
                        </div>
                    </div>
                </div>

                <div class="mt-6 pt-5 border-t border-slate-100">
                    <div class="h-3 w-full overflow-hidden rounded-full bg-slate-100 p-0.5 ring-1 ring-inset ring-slate-200/60"
                         role="progressbar"
                         aria-label="Overall accuracy"
                         aria-valuemin="0"
                         aria-valuemax="100"
                         aria-valuenow="{{ $overallPercentage }}">
                        <div class="h-full rounded-full bg-primary transition-all duration-700"
                             style="width: {{ $overallPercentage }}%"></div>
                    </div>
                </div>
            </div>

            {{-- Domain Breakdown Table --}}
            <div class="rounded-3xl border-2 border-b-4 border-slate-200/90 bg-white p-6 sm:p-8 shadow-xs">
                <div class="flex items-center justify-between gap-4 mb-4">
                    <div>
                        <h3 class="font-display text-lg font-bold text-slate-900">
                            Performance by Domain
                        </h3>
                        <p class="text-xs text-muted-ink">
                            Breakdown of accuracy according to the PhLE Table of Specifications.
                        </p>
                    </div>
                    <span class="hidden sm:inline-flex items-center rounded-lg bg-clinical-tint px-2.5 py-1 font-display text-xs font-bold text-clinical-ink">
                        6 Domains
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left font-sans text-sm">
                        <thead>
                            <tr class="border-b-2 border-slate-100 font-display text-[11px] font-bold uppercase tracking-wider text-muted-ink">
                                <th class="pb-3 pr-4">Domain</th>
                                <th class="pb-3 px-4 text-center">Score</th>
                                <th class="pb-3 px-4 text-right">Accuracy</th>
                                <th class="pb-3 pl-4 text-right w-36 hidden sm:table-cell">Distribution</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            @foreach ($perDomain as $domain)
                                @php
                                    $domainPercentage = $domain['total'] > 0
                                        ? round((100 * $domain['correct']) / $domain['total'])
                                        : 0;
                                @endphp
                                <tr class="transition-colors hover:bg-slate-50/70">
                                    <td class="py-3.5 pr-4 font-semibold text-slate-900">
                                        {{ $domain['domain_name'] }}
                                    </td>

                                    <td class="py-3.5 px-4 text-center font-mono text-xs text-slate-600">
                                        {{ $domain['correct'] }} / {{ $domain['total'] }}
                                    </td>

                                    <td class="py-3.5 px-4 text-right">
                                        <span class="inline-flex items-center rounded-md px-2 py-0.5 font-display text-xs font-bold {{ $domainPercentage >= 75 ? 'bg-strong-tint text-strong-ink' : ($domainPercentage >= 50 ? 'bg-amber-50 text-amber-700' : 'bg-weak-tint text-weak-ink') }}">
                                            {{ $domainPercentage }}%
                                        </span>
                                    </td>

                                    <td class="py-3.5 pl-4 text-right hidden sm:table-cell">
                                        <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100 ring-1 ring-inset ring-slate-200/60">
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

            {{-- Initial BKT Mastery Estimates --}}
            <div class="rounded-3xl border-2 border-b-4 border-slate-200/90 bg-white p-6 sm:p-8 shadow-xs">
                <div class="mb-5">
                    <h3 class="font-display text-lg font-bold text-slate-900">
                        Initial Mastery Estimates (BKT)
                    </h3>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed mt-1">
                        These values anchor your Knowledge Tracing model. Posterior mastery estimates update dynamically following every response in daily practice.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                    @foreach ($masteryResults as $result)
                        @php
                            $masteryPercentage = round(100 * $result['mastery']);
                        @endphp
                        <div class="flex flex-col justify-between rounded-2xl border-2 border-slate-200/80 bg-slate-50/60 p-4 transition-all hover:bg-slate-50">
                            <div>
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-clinical-ink line-clamp-1">
                                        {{ $result['domain_name'] }}
                                    </span>
                                    <span class="font-mono text-[11px] text-muted-ink shrink-0">
                                        {{ $result['correct'] }}/{{ $result['total'] }} items
                                    </span>
                                </div>

                                <h4 class="mt-1 font-display text-sm font-bold text-slate-900 line-clamp-2">
                                    {{ $result['competency_title'] }}
                                </h4>
                            </div>

                            <div class="mt-3 pt-2 border-t border-slate-200/60 flex items-center justify-between gap-3">
                                <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-200/70">
                                    <div class="h-full rounded-full bg-primary"
                                         style="width: {{ $masteryPercentage }}%"></div>
                                </div>
                                <span class="font-display text-xs font-black text-slate-900">
                                    {{ $masteryPercentage }}%
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Action CTA --}}
            <div class="pt-2 pb-6 flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="{{ route('dashboard') }}"
                   style="--lip: #4A2FC4;"
                   class="btn-press w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-2xl bg-primary px-8 py-3.5 font-display text-sm font-bold text-white shadow-md transition hover:bg-primary/95">
                    <span>Return to Dashboard</span>
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </a>

                <a href="{{ route('practice.intro') }}"
                   class="w-full sm:w-auto inline-flex items-center justify-center rounded-2xl border-2 border-slate-200 bg-white px-6 py-3.5 font-display text-sm font-bold text-slate-700 transition hover:bg-slate-50 hover:text-slate-900">
                    Start Adaptive Practice
                </a>
            </div>

        </div>
    </div>
</x-app-layout>