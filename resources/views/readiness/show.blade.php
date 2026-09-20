<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-display text-xl font-bold text-slate-900 leading-tight">
                    {{ __('Board Readiness Report') }}
                </h2>
                <p class="text-xs font-semibold uppercase tracking-wider text-muted-ink">
                    PRC Table of Specifications (TOS) Telemetry
                </p>
            </div>

            <a href="{{ route('dashboard') }}" 
               class="font-display text-xs font-bold text-muted-ink hover:text-primary transition">
                Back to Dashboard
            </a>
        </div>
    </x-slot>

    @php
        $band = $breakdown['band'];
        $totalReadiness = (float) $breakdown['total'];

        // Readiness band styles
        $bandConfig = match ($band) {
            'board_ready' => [
                'label'  => 'Board Ready',
                'bg'     => 'bg-strong-tint',
                'text'   => 'text-strong-ink',
                'border' => 'border-[#22C55E]/40',
                'stroke' => '#22C55E',
            ],
            'approaching' => [
                'label'  => 'Approaching Readiness',
                'bg'     => 'bg-clinical-tint',
                'text'   => 'text-clinical-ink',
                'border' => 'border-[#0EA5A4]/40',
                'stroke' => '#0EA5A4',
            ],
            'developing' => [
                'label'  => 'Developing',
                'bg'     => 'bg-gold-tint',
                'text'   => 'text-gold-ink',
                'border' => 'border-[#F5A623]/40',
                'stroke' => '#F5A623',
            ],
            default => [
                'label'  => 'At Risk',
                'bg'     => 'bg-weak-tint',
                'text'   => 'text-weak-ink',
                'border' => 'border-[#F0524F]/40',
                'stroke' => '#F0524F',
            ],
        };

        // Donut Chart Math (160px box, radius 64)
        $radius = 64;
        $circumference = 2 * M_PI * $radius; // ~402.12
        $scoreRatio = min(1, max(0, $totalReadiness / 100));
        $ringOffset = $circumference * (1 - $scoreRatio);

        // CitiRx board-ready display threshold
        $boardReadyThreshold = 75.0;
        $pointsNeeded = max(0, $boardReadyThreshold - $totalReadiness);

        // Domains count & weakest domain
        $domains = collect($breakdown['domains']);
        $weakestDomain = $domains->sortBy('mastery')->first();
        $strongDomainsCount = $domains->filter(fn($d) => ($d['mastery'] * 100) >= $boardReadyThreshold)->count();
    @endphp

    <div class="pt-6 pb-16 font-sans text-slate-900 antialiased">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 space-y-6">

            {{-- TOP HERO CARD: GRADIENT CONTAINER WITH DIAL & TELEMETRY --}}
            <div class="relative overflow-hidden rounded-[1.5rem] border-2 border-b-4 border-primary/30 bg-gradient-to-br from-primary-tint via-[#FAF8FF] to-white p-6 sm:p-8 shadow-sm">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-center">
                    
                    {{-- Left: Donut Chart --}}
                    <div class="md:col-span-5 flex flex-col items-center text-center border-b md:border-b-0 md:border-r border-primary/15 pb-6 md:pb-0 md:pr-6">
                        <span class="inline-flex items-center rounded-full bg-white px-3 py-1 font-display text-xs font-bold text-primary shadow-sm ring-1 ring-primary/20">
                            Estimated Readiness
                        </span>

                        <div class="relative my-4" style="width: 160px; height: 160px;">
                            <svg style="width: 160px; height: 160px;" class="-rotate-90" viewBox="0 0 160 160">
                                <circle cx="80" cy="80" r="{{ $radius }}" fill="none" stroke="#FFFFFF" stroke-width="14" />
                                <circle cx="80" cy="80" r="{{ $radius }}" fill="none" stroke="{{ $bandConfig['stroke'] }}" stroke-width="14"
                                        stroke-linecap="round"
                                        stroke-dasharray="{{ round($circumference, 2) }}"
                                        stroke-dashoffset="{{ round($ringOffset, 2) }}"
                                        class="transition-all duration-1000 ease-out" />
                            </svg>

                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                <span class="font-display text-4xl font-extrabold text-slate-900 leading-none">
                                    {{ number_format($totalReadiness, 1) }}<span class="text-xl font-bold text-muted-ink">%</span>
                                </span>
                                <span class="font-display text-[10px] font-bold uppercase tracking-wider text-muted-ink mt-1">
                                    Weighted TOS
                                </span>
                            </div>
                        </div>

                        <span class="inline-flex items-center rounded-full border px-3.5 py-1 font-display text-xs font-bold shadow-sm {{ $bandConfig['bg'] }} {{ $bandConfig['text'] }} {{ $bandConfig['border'] }}">
                            {{ $bandConfig['label'] }}
                        </span>
                    </div>

                    {{-- Right: PhLE Diagnostic Telemetry Details --}}
                    <div class="md:col-span-7 space-y-4">
                        <div>
                            <h3 class="font-display text-xl font-extrabold text-slate-900">
                                Table of Specifications Analysis
                            </h3>
                            <p class="mt-1 text-xs leading-relaxed text-slate-700">
                                Calculated from your Bayesian Knowledge Tracing (BKT) competencies across each Table of Specifications area, weighted according to the official PRC blueprint.
                            </p>
                        </div>

                        {{-- Metric Chips --}}
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 pt-1">
                            <div class="rounded-xl border border-primary/20 bg-white/90 p-3 shadow-sm">
                                <span class="block font-display text-[10px] font-bold uppercase tracking-wider text-muted-ink">
                                    Board Ready Target
                                </span>
                                <span class="font-display text-lg font-bold text-slate-900">
                                    {{ number_format($boardReadyThreshold, 0) }}%
                                </span>
                            </div>

                            <div class="rounded-xl border border-primary/20 bg-white/90 p-3 shadow-sm">
                                <span class="block font-display text-[10px] font-bold uppercase tracking-wider text-muted-ink" title="Points needed to reach the CitiRx Board Ready category">
                                    Points to Target
                                </span>
                                <span class="font-display text-lg font-bold {{ $pointsNeeded > 0 ? 'text-weak-ink' : 'text-strong-ink' }}">
                                    {{ $pointsNeeded > 0 ? '+' . number_format($pointsNeeded, 1) . '%' : 'Board Ready' }}
                                </span>
                            </div>

                            <div class="col-span-2 sm:col-span-1 rounded-xl border border-primary/20 bg-white/90 p-3 shadow-sm">
                                <span class="block font-display text-[10px] font-bold uppercase tracking-wider text-muted-ink">
                                    Strong Modules
                                </span>
                                <span class="font-display text-lg font-bold text-slate-900">
                                    {{ $strongDomainsCount }} of {{ count($domains) }}
                                </span>
                            </div>
                        </div>

                        <p class="text-[11px] text-muted-ink font-medium">
                            {{ $pointsNeeded > 0 ? number_format($pointsNeeded, 1) . '% needed to reach the CitiRx Board Ready category.' : 'You have reached the CitiRx Board Ready category.' }}
                        </p>

                        @if ($weakestDomain)
                            <div class="rounded-xl border border-clinical/30 bg-white/85 p-3.5 text-xs text-clinical-ink leading-relaxed shadow-sm">
                                <span class="font-bold">Remediation Priority:</span>
                                Your lowest mastery is in <strong>{{ $weakestDomain['domain_name'] }}</strong> ({{ number_format($weakestDomain['mastery'] * 100, 1) }}%). Prioritizing practice questions here provides the fastest path toward closing your readiness gap.
                            </div>
                        @endif
                    </div>

                </div>
            </div>

            {{-- SECTION 2: DOMAIN PERFORMANCE (2-COLUMN GRID) --}}
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="font-display text-lg font-bold text-slate-900">
                            Domain Breakdown
                        </h3>
                        <p class="text-xs text-muted-ink font-medium">
                            Performance and contribution across all 6 PRC TOS areas
                        </p>
                    </div>

                    <span class="font-display text-xs font-semibold text-muted-ink">
                        {{ count($domains) }} Domains Tracked
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
                    @foreach ($breakdown['domains'] as $domain)
                        @php
                            $domainPct = $domain['mastery'] * 100;
                            $barColor = match (true) {
                                $domainPct >= $boardReadyThreshold => 'bg-strong',
                                $domainPct >= 50 => 'bg-clinical',
                                default          => 'bg-weak',
                            };
                            $badgeColor = match (true) {
                                $domainPct >= $boardReadyThreshold => 'border-[#22C55E]/40 bg-strong-tint text-strong-ink',
                                $domainPct >= 50 => 'border-[#0EA5A4]/40 bg-clinical-tint text-clinical-ink',
                                default          => 'border-[#F0524F]/40 bg-weak-tint text-weak-ink',
                            };
                        @endphp

                        <div class="rounded-2xl border-2 border-b-4 border-slate-200 bg-white p-5 shadow-sm space-y-4">
                            
                            {{-- Header & Mastery % --}}
                            <div>
                                <div class="flex items-start justify-between gap-2">
                                    <h4 class="font-display text-sm font-bold text-slate-900 leading-snug">
                                        {{ $domain['domain_name'] }}
                                    </h4>
                                    <span class="rounded-full border px-2 py-0.5 font-display text-[11px] font-bold {{ $badgeColor }} shrink-0">
                                        {{ number_format($domainPct, 1) }}%
                                    </span>
                                </div>

                                {{-- Chunky Mastery Bar --}}
                                <div class="mt-2.5 h-2.5 w-full overflow-hidden rounded-full bg-slate-100 ring-1 ring-inset ring-slate-200/60">
                                    <div class="h-full rounded-full {{ $barColor }} transition-all duration-700"
                                         style="width: {{ min(100, $domainPct) }}%"></div>
                                </div>

                                {{-- PRC Weight & Contribution Strip --}}
                                <div class="mt-2.5 flex items-center justify-between text-[11px] text-muted-ink border-b border-slate-100 pb-2">
                                    <span>PRC Weight: <strong class="text-slate-700">{{ number_format($domain['weight'], 1) }}%</strong></span>
                                    <span>Contribution: <strong class="text-slate-900">+{{ number_format($domain['contribution'], 2) }}%</strong></span>
                                </div>
                            </div>

                            {{-- Sub-Competencies Breakdown --}}
                            <div class="space-y-2">
                                <span class="block font-display text-[10px] font-bold uppercase tracking-wider text-muted-ink">
                                    Competency Mastery
                                </span>

                                <div class="space-y-1.5">
                                    @foreach ($domain['competencies'] as $competency)
                                        @php
                                            $compPct = $competency['mastery'] * 100;
                                        @endphp

                                        <div class="flex items-center justify-between gap-2 text-xs">
                                            <span class="truncate text-slate-700" title="{{ $competency['title'] }}">
                                                {{ $competency['title'] }}
                                            </span>
                                            <span class="font-mono text-[11px] font-bold text-slate-900 shrink-0">
                                                {{ number_format($compPct, 1) }}%
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                        </div>
                    @endforeach
                </div>
            </div>

            {{-- METHODOLOGY NOTE --}}
            <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4 text-xs text-muted-ink leading-relaxed">
                <span class="font-bold text-slate-700">Computation Note:</span>
                Each domain contribution is calculated as (Domain Mastery × PRC Weight) ÷ Total PRC Weight × 100. Readiness bands are internal CitiRx guidance categories designed to steer adaptive study paths and do not represent official PRC licensure determinations.
            </div>

            {{-- ACTION BUTTONS --}}
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
                <a href="{{ route('practice.intro') }}"
                   style="--lip: #4A2FC4;"
                   class="btn-press w-full sm:w-auto rounded-xl bg-primary px-8 py-3 text-center font-display text-sm font-bold text-white shadow-sm transition hover:bg-primary/95">
                    Start Adaptive Practice
                </a>

                <a href="{{ route('dashboard') }}"
                   style="--lip: #CBD5E1;"
                   class="btn-press w-full sm:w-auto rounded-xl border-2 border-slate-200 bg-white px-8 py-3 text-center font-display text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                    Back to Dashboard
                </a>
            </div>

        </div>
    </div>
</x-app-layout>