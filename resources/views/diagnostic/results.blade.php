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
            {{-- Level Up Notification Banner --}}
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
                            <span
                                class="inline-flex items-center gap-1.5 rounded-lg bg-primary-tint px-2.5 py-1 font-display text-[11px] font-bold uppercase tracking-wider text-primary">
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
                            Your performance has calibrated initial parameters across all six examination subjects
                        </p>
                    </div>

                    <div
                        class="grid grid-cols-2 gap-4 border-t border-slate-100 pt-4 sm:border-l sm:border-t-0 sm:pl-8 sm:pt-0">
                        {{-- Raw score --}}
                        <div class="text-center sm:text-right">
                            <span class="block font-mono text-[10px] font-bold uppercase tracking-wider text-muted-ink">
                                Raw Accuracy
                            </span>

                            <div class="font-display text-3xl font-black tracking-tight text-primary sm:text-4xl">
                                {{ $overallPercentage }}%
                            </div>
                        </div>

                        {{-- Official TOS-weighted score --}}
                        <div class="text-center sm:text-right">
                            <span class="block font-mono text-[10px] font-bold uppercase tracking-wider text-muted-ink">
                                TOS-Weighted Score
                            </span>

                            <div class="font-display text-3xl font-black tracking-tight text-clinical sm:text-4xl">
                                {{ number_format($tosWeightedScore ?? 0, 2) }}%
                            </div>
                        </div>

                        <div class="col-span-2 text-center sm:text-right">
                            <span
                                class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-3 py-1 font-display text-xs font-extrabold text-slate-700">
                                {{ $session->correct_items }}
                                /
                                {{ $session->total_items }}
                                Correct
                            </span>
                        </div>
                    </div>
                </div>

                <div class="mt-6 pt-5 border-t border-slate-100">
                    <div class="h-3 w-full overflow-hidden rounded-full bg-slate-100 p-0.5 ring-1 ring-inset ring-slate-200/60"
                        role="progressbar" aria-label="Raw diagnostic accuracy" aria-valuemin="0" aria-valuemax="100"
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
                            Performance by Subject
                        </h3>
                        <p class="text-xs text-muted-ink">
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
                                class="border-b-2 border-slate-100 font-display text-[11px] font-bold uppercase tracking-wider text-muted-ink">
                                <th class="pb-3 pr-4">Subject</th>
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
                                        <span
                                            class="inline-flex items-center rounded-md px-2 py-0.5 font-display text-xs font-bold {{ $domainPercentage >= 75 ? 'bg-strong-tint text-strong-ink' : ($domainPercentage >= 50 ? 'bg-amber-50 text-amber-700' : 'bg-weak-tint text-weak-ink') }}">
                                            {{ $domainPercentage }}%
                                        </span>
                                    </td>

                                    <td class="py-3.5 pl-4 text-right hidden sm:table-cell">
                                        <div
                                            class="h-2 w-full overflow-hidden rounded-full bg-slate-100 ring-1 ring-inset ring-slate-200/60">
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
            <div class="rounded-3xl border-2 border-b-4 border-slate-200/90 bg-white p-6 sm:p-8 shadow-xs">
                <div class="mb-5">
                    <h3 class="font-display text-lg font-bold text-slate-900">
                        Initial Mastery Estimates (BKT)
                    </h3>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed mt-1">
                        These values anchor your Knowledge Tracing model. Posterior mastery estimates update dynamically
                        following every response in daily practice.
                    </p>
                </div>

                {{-- Accordion Container --}}
                <div class="space-y-3">
                    @foreach ($masteryResults->groupBy('domain_name') as $domainName => $domainResults)
                        {{-- Make the first accordion open by default using $loop->first --}}
                        <section x-data="{ expanded: {{ $loop->first ? 'true' : 'false' }} }" 
                                 class="rounded-2xl border-2 border-slate-100 bg-white overflow-hidden transition-all duration-200"
                                 :class="expanded ? 'border-primary/20 shadow-sm' : 'hover:border-slate-200'">
                            
                            {{-- Accordion Header / Button --}}
                            <button @click="expanded = !expanded" 
                                    class="w-full flex items-center justify-between p-4 text-left transition-colors hover:bg-slate-50"
                                    :class="expanded ? 'bg-primary-tint/30' : ''">
                                <div class="flex items-center gap-3">
                                    <h4 class="font-display text-sm font-bold uppercase tracking-wider text-slate-800">
                                        {{ $domainName }}
                                    </h4>
                                    <span class="rounded-full bg-slate-200 px-2 py-0.5 font-mono text-[10px] font-bold text-slate-600">
                                        {{ $domainResults->count() }} Competencies
                                    </span>
                                </div>
                                <svg :class="expanded ? 'rotate-180 text-primary' : 'text-slate-400'" 
                                     class="h-5 w-5 transform transition-transform duration-200" 
                                     fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                </svg>
                            </button>

                            {{-- Accordion Body (Sleek Grid) --}}
                            <div x-show="expanded" 
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 -translate-y-2"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 class="border-t-2 border-slate-100 p-4 bg-slate-50/50">
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    @foreach ($domainResults as $result)
                                        @php
                                            $masteryPercentage = round(100 * $result['mastery']);
                                        @endphp
                                        <div class="flex items-center justify-between gap-4 rounded-xl border-2 border-slate-200/60 bg-white p-3 shadow-2xs">
                                            <div class="min-w-0 flex-1">
                                                <h5 class="font-display text-xs font-bold text-slate-900 truncate" title="{{ $result['competency_title'] }}">
                                                    {{ $result['competency_title'] }}
                                                </h5>
                                                <div class="mt-1.5 flex items-center gap-2.5">
                                                    <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-100 ring-1 ring-inset ring-slate-200/50">
                                                        <div class="h-full rounded-full bg-primary" style="width: {{ $masteryPercentage }}%"></div>
                                                    </div>
                                                    <span class="w-8 text-right font-mono text-[10px] font-bold text-slate-600">
                                                        {{ $masteryPercentage }}%
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <div class="shrink-0 flex items-center justify-center rounded-lg bg-slate-50 px-2 py-1 border border-slate-100">
                                                <span class="font-mono text-[9px] font-bold text-muted-ink uppercase">
                                                    {{ $result['correct'] }}/{{ $result['total'] }}
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
            <div class="pt-2 pb-6 flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="{{ route('dashboard') }}" style="--lip: #4A2FC4;"
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