<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="font-display text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">
                    Student Dashboard
                </h2>
                <p class="text-xs font-semibold uppercase tracking-wider text-muted-ink">
                    PhLE Adaptive Review Engine
                </p>
            </div>

            {{-- Tactile Streak & XP Counters --}}
            <div class="flex items-center gap-2.5">
                @php
                    $streak = $user->streak_days ?? $user->current_streak ?? 0;
                    $xp = $user->total_xp ?? 0;
                @endphp

                <div class="flex items-center gap-2 rounded-2xl border-2 border-b-4 border-[#F5A623]/30 bg-gold-tint px-4 py-2 font-display font-bold text-gold-ink shadow-sm"
                     title="{{ $streak }}-day study streak">
                    <svg class="h-5 w-5 text-gold" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12.963 2.286a.75.75 0 0 0-1.071-.136 9.742 9.742 0 0 0-3.539 6.176A7.547 7.547 0 0 1 6.648 6.61a.75.75 0 0 0-1.152-.082A9 9 0 1 0 15.68 4.534a7.46 7.46 0 0 1-2.717-2.248ZM15.75 14.25a3.75 3.75 0 1 1-7.313-1.172c.628.465 1.35.81 2.133 1a5.99 5.99 0 0 1 1.925-3.547 3.75 3.75 0 0 1 3.255 3.719Z"/>
                    </svg>
                    <span class="text-base font-extrabold">{{ $streak }}</span>
                    <span class="text-xs tracking-wider opacity-80">DAYS</span>
                </div>

                <div class="flex items-center gap-2 rounded-2xl border-2 border-b-4 border-[#F5A623]/30 bg-gold-tint px-4 py-2 font-display font-bold text-gold-ink shadow-sm"
                     title="Total experience points">
                    <svg class="h-5 w-5 text-gold" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M14.615 1.595a.75.75 0 0 1 .359.852L12.982 9.75h7.268a.75.75 0 0 1 .548 1.262l-10.5 11.25a.75.75 0 0 1-1.272-.71l1.992-7.302H3.75a.75.75 0 0 1-.548-1.262l10.5-11.25a.75.75 0 0 1 .913-.143Z"/>
                    </svg>
                    <span class="text-base font-extrabold">{{ number_format($xp) }}</span>
                    <span class="text-xs tracking-wider opacity-80">XP</span>
                </div>
            </div>
        </div>
    </x-slot>

    @php
        $user = Auth::user();

        // Tier & Rank Title
        $totalXp       = (int) ($user->total_xp ?? 0);
        $tierName      = $user->tier?->tier_name ?? $tierName ?? 'Bronze Tier';
        $nextTierName  = $nextTierName ?? 'Silver Tier';
        $tierFloorXp   = $tierFloorXp ?? 0;
        $tierCeilingXp = $tierCeilingXp ?? 2500;
        $tierProgress  = max(0, min(100, round((($totalXp - $tierFloorXp) / max(1, ($tierCeilingXp - $tierFloorXp))) * 100)));
        $xpToNextTier  = max(0, $tierCeilingXp - $totalXp);

        // Daily goal calculations
        $solvedToday   = $solvedToday ?? 0;
        $dailyGoal     = $dailyGoal ?? 20;
        $goalRatio     = $dailyGoal > 0 ? min(1, $solvedToday / $dailyGoal) : 0;
        $ringRadius    = 44;
        $ringLength    = 2 * M_PI * $ringRadius;
        $ringOffset    = $ringLength * (1 - $goalRatio);

        // Readiness badge styles
        $readinessStyles = match ($readinessBand ?? null) {
            'board_ready' => ['label' => 'Board Ready',           'bg' => 'bg-strong-tint',   'text' => 'text-strong-ink',   'border' => 'border-[#22C55E]/40'],
            'approaching' => ['label' => 'Approaching Readiness', 'bg' => 'bg-clinical-tint', 'text' => 'text-clinical-ink', 'border' => 'border-[#0EA5A4]/40'],
            'developing'  => ['label' => 'Developing',            'bg' => 'bg-gold-tint',     'text' => 'text-gold-ink',     'border' => 'border-[#F5A623]/40'],
            'at_risk'     => ['label' => 'At Risk',               'bg' => 'bg-weak-tint',     'text' => 'text-weak-ink',     'border' => 'border-[#F0524F]/40'],
            default       => ['label' => 'Pending Diagnostic',    'bg' => 'bg-slate-100',     'text' => 'text-muted-ink',    'border' => 'border-slate-200'],
        };

        // Fallback domain data
        $domains = $domains ?? [
            ['name' => 'Pharmacology',        'accuracy' => 78, 'answered' => 64],
            ['name' => 'Pharmaceutics',       'accuracy' => 54, 'answered' => 42],
            ['name' => 'Medicinal Chemistry', 'accuracy' => 71, 'answered' => 50],
            ['name' => 'Clinical Pharmacy',   'accuracy' => 84, 'answered' => 75],
        ];
        $weakest = collect($domains)->sortBy('accuracy')->first();
    @endphp

    <div class="py-6 font-sans text-slate-900 antialiased">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- Welcome Bar --}}
            <div class="flex items-center justify-between rounded-2xl border-2 border-b-4 border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-primary-tint font-display text-xl font-bold text-primary">
                        🎓
                    </div>
                    <div>
                        <h1 class="font-display text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
                            Welcome back, {{ method_exists($user, 'fullName') ? $user->fullName() : $user->name }}!
                        </h1>
                        <p class="text-xs font-medium text-muted-ink">
                            @if ($user->cohort)
                                <span class="font-semibold text-slate-800">{{ $user->cohort->cohort_name }}</span> · 
                            @endif
                            Maintain your daily practice to climb through the readiness ranks.
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">

                {{-- MAIN COLUMN --}}
                <div class="space-y-6 lg:col-span-2">

                    {{-- 1. HERO CARD: Focus / Diagnostic --}}
                    @if (!$user->is_diagnostic_completed)
                        <section class="relative overflow-hidden rounded-[1.5rem] border-2 border-b-4 border-primary/40 bg-gradient-to-br from-primary-tint via-[#EDE8FF] to-white p-6 sm:p-8">
                            <span class="inline-flex items-center rounded-full border border-primary/30 bg-white px-3 py-1 font-display text-xs font-bold text-primary shadow-sm">
                                Step 1: Baseline Assessment
                            </span>
                            <h2 class="mt-3 font-display text-2xl font-extrabold text-slate-900 sm:text-3xl">
                                Complete Your Diagnostic
                            </h2>
                            <p class="mt-2 max-w-lg text-sm leading-relaxed text-slate-700">
                                CitiRx uses Bayesian Knowledge Tracing (BKT) to pinpoint your exact competencies. Start your baseline test to unlock adaptive practice.
                            </p>
                            <div class="mt-6">
                                <a href="{{ $inProgressDiagnostic ? route('diagnostic.take', ['session' => $inProgressDiagnostic->session_id]) : route('diagnostic.intro') }}"
                                   style="--lip: #4A2FC4;"
                                   class="btn-press inline-flex items-center rounded-2xl bg-primary px-7 py-3.5 font-display text-base font-bold text-white shadow-md">
                                    {{ $inProgressDiagnostic ? 'Resume Diagnostic' : 'Start Diagnostic' }}
                                </a>
                            </div>
                        </section>
                    @else
                        <section class="relative overflow-hidden rounded-[1.5rem] border-2 border-b-4 border-primary/30 bg-gradient-to-br from-primary-tint via-[#FAF8FF] to-white p-6 sm:p-8">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center rounded-full bg-white px-3 py-1 font-display text-xs font-bold text-primary shadow-sm ring-1 ring-primary/20">
                                    Recommended Focus
                                </span>
                                @if ($inProgressPractice)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-gold-tint px-2.5 py-0.5 text-xs font-bold text-gold-ink">
                                        <span class="h-2 w-2 animate-pulse rounded-full bg-gold"></span>
                                        In progress
                                    </span>
                                @endif
                            </div>

                            <h2 class="mt-3 font-display text-2xl font-extrabold text-slate-900 sm:text-3xl">
                                {{ $weakest ? $weakest['name'] : 'Adaptive Practice' }}
                            </h2>
                            <p class="mt-2 max-w-xl text-sm leading-relaxed text-slate-700">
                                @if ($weakest)
                                    Current accuracy is <strong class="text-slate-900">{{ $weakest['accuracy'] }}%</strong>. Targeted practice questions will reinforce this domain.
                                @else
                                    Continue adaptive sessions to expand your mastery scores.
                                @endif
                            </p>

                            <div class="mt-6 flex flex-wrap items-center gap-4">
                                <a href="{{ $inProgressPractice ? route('practice.show', ['session' => $inProgressPractice->session_id]) : route('practice.intro') }}"
                                   style="--lip: #4A2FC4;"
                                   class="btn-press inline-flex items-center rounded-2xl bg-primary px-6 py-3 font-display text-base font-bold text-white shadow-md">
                                    {{ $inProgressPractice ? 'Resume Session' : 'Start Practice Session' }}
                                </a>

                                <a href="{{ route('readiness.show') }}"
                                   class="font-display text-sm font-bold text-primary transition hover:underline">
                                    Readiness Report →
                                </a>
                            </div>
                        </section>
                    @endif

                    {{-- 2. RX VAULT & ACHIEVEMENTS ROW (NOW ABOVE TOS) --}}
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="flex flex-col justify-between rounded-2xl border-2 border-b-4 border-slate-200 bg-white p-5 shadow-sm">
                            <div class="flex items-start gap-4">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border-2 border-b-4 border-[#F5A623]/30 bg-gold-tint text-xl shadow-sm">
                                    💊
                                </div>
                                <div>
                                    <h4 class="font-display text-lg font-bold text-slate-900">Rx Vault</h4>
                                    <p class="text-xs font-medium text-muted-ink mt-0.5">
                                        @if ($activeVaultCount > 0)
                                            <span class="font-bold text-slate-900">{{ $activeVaultCount }}</span> questions flagged for review.
                                        @else
                                            No pending remediation items.
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="mt-5">
                                <a href="{{ route('vault.index') }}"
                                   style="--lip: #CBD5E1;"
                                   class="btn-press block w-full rounded-xl border-2 border-slate-200 bg-slate-50 py-2 text-center font-display text-xs font-extrabold uppercase tracking-wider text-slate-700 hover:bg-slate-100">
                                    Open Vault
                                </a>
                            </div>
                        </div>

                        <div class="flex flex-col justify-between rounded-2xl border-2 border-b-4 border-slate-200 bg-white p-5 shadow-sm">
                            <div class="flex items-start gap-4">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border-2 border-b-4 border-[#F5A623]/30 bg-gold-tint text-xl shadow-sm">
                                    🏆
                                </div>
                                <div>
                                    <h4 class="font-display text-lg font-bold text-slate-900">Achievements</h4>
                                    <p class="text-xs font-medium text-muted-ink mt-0.5">
                                        <span class="font-bold text-slate-900">{{ $unlockedBadgeCount }}</span> of {{ $totalBadgeCount }} badges unlocked
                                    </p>
                                </div>
                            </div>
                            <div class="mt-5">
                                <a href="{{ route('badges.index') }}"
                                   style="--lip: #CBD5E1;"
                                   class="btn-press block w-full rounded-xl border-2 border-slate-200 bg-slate-50 py-2 text-center font-display text-xs font-extrabold uppercase tracking-wider text-slate-700 hover:bg-slate-100">
                                    View Badges
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- 3. DOMAIN MASTERY (TOS) SECTION --}}
                    @if ($user->is_diagnostic_completed)
                        <section>
                            <div class="flex items-center justify-between">
                                <h3 class="font-display text-xl font-extrabold text-slate-900">
                                    Domain Mastery (Table of Specifications)
                                </h3>
                                <span class="text-xs font-semibold uppercase tracking-wider text-muted-ink">
                                    {{ count($domains) }} Modules
                                </span>
                            </div>

                            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                @foreach ($domains as $domain)
                                    @php
                                        $accuracy = $domain['accuracy'];
                                        $isStrong = $accuracy >= 80;
                                        $isWeak   = $accuracy < 60;
                                        $barColor = $isStrong ? 'bg-strong' : ($isWeak ? 'bg-weak' : 'bg-clinical');
                                    @endphp

                                    <div class="flex flex-col justify-between rounded-2xl border-2 border-b-4 border-slate-200 bg-white p-5 transition duration-150 hover:-translate-y-0.5 hover:border-slate-300">
                                        <div>
                                            <div class="flex items-start justify-between gap-2">
                                                <span class="rounded-xl bg-clinical-tint px-2.5 py-1 font-display text-xs font-bold text-clinical-ink">
                                                    {{ $domain['name'] }}
                                                </span>

                                                @if ($isStrong)
                                                    <span class="rounded-full border border-[#22C55E]/30 bg-strong-tint px-2.5 py-0.5 font-display text-[11px] font-bold text-strong-ink">
                                                        Strong
                                                    </span>
                                                @elseif ($isWeak)
                                                    <span class="rounded-full border border-[#F0524F]/30 bg-weak-tint px-2.5 py-0.5 font-display text-[11px] font-bold text-weak-ink">
                                                        Needs Review
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="mt-4 flex items-baseline gap-2">
                                                <span class="font-display text-3xl font-extrabold text-slate-900">{{ $accuracy }}%</span>
                                                <span class="text-xs font-semibold text-muted-ink">{{ $domain['answered'] }} answered</span>
                                            </div>

                                            {{-- Chunky Progress Bar --}}
                                            <div class="mt-3 h-3 w-full overflow-hidden rounded-full bg-slate-100 p-0.5 ring-1 ring-inset ring-slate-200/60">
                                                <div class="h-full rounded-full {{ $barColor }} transition-all duration-500"
                                                     style="width: {{ $accuracy }}%"></div>
                                            </div>
                                        </div>

                                        {{-- 3D Secondary Button --}}
                                        <div class="mt-5">
                                            <a href="{{ route('practice.intro') }}"
                                               style="--lip: #CBD5E1;"
                                               class="btn-press block w-full rounded-xl border-2 border-slate-200 bg-white py-2 text-center font-display text-xs font-extrabold uppercase tracking-wider text-slate-700 transition hover:bg-slate-50">
                                                Practice Domain
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif

                </div>

                {{-- SIDEBAR: Gamification Widgets --}}
                <aside class="space-y-6">

                    {{-- Board Readiness Prediction Widget --}}
                    @if ($user->is_diagnostic_completed)
                        <section class="rounded-2xl border-2 border-b-4 border-slate-200 bg-white p-6 text-center shadow-sm">
                            <h3 class="font-display text-xs font-extrabold uppercase tracking-wider text-muted-ink">
                                Predicted PhLE Readiness
                            </h3>

                            <div class="mt-4 flex flex-col items-center">
                                <span class="font-display text-5xl font-extrabold tracking-tight text-slate-900">
                                    {{ number_format((float) ($user->predicted_readiness_pct ?? 0), 1) }}<span class="text-2xl font-bold text-muted-ink">%</span>
                                </span>
                                <span class="mt-3 inline-flex items-center rounded-full border px-3 py-1 font-display text-xs font-bold {{ $readinessStyles['bg'] }} {{ $readinessStyles['text'] }} {{ $readinessStyles['border'] }}">
                                    {{ $readinessStyles['label'] }}
                                </span>
                            </div>

                            <div class="mt-5 border-t border-slate-100 pt-4">
                                <a href="{{ route('readiness.show') }}" class="font-display text-xs font-bold text-primary hover:underline">
                                    Detailed Statistics Breakdown →
                                </a>
                            </div>
                        </section>
                    @endif

                    {{-- Daily Goal Ring --}}
                    <section class="rounded-2xl border-2 border-b-4 border-slate-200 bg-white p-6 text-center shadow-sm">
                        <div class="flex items-center justify-between">
                            <h3 class="font-display text-xs font-extrabold uppercase tracking-wider text-muted-ink">
                                Daily Goal
                            </h3>
                            <span class="rounded-md bg-gold-tint px-2 py-0.5 font-display text-[10px] font-bold text-gold-ink">
                                STREAK
                            </span>
                        </div>

                        <div class="relative mx-auto mt-4 h-32 w-32">
                            <svg class="h-full w-full -rotate-90" viewBox="0 0 120 120">
                                <circle cx="60" cy="60" r="{{ $ringRadius }}" fill="none" stroke="#F1F5F9" stroke-width="12"/>
                                <circle cx="60" cy="60" r="{{ $ringRadius }}" fill="none" stroke="#F5A623" stroke-width="12"
                                        stroke-linecap="round"
                                        stroke-dasharray="{{ round($ringLength, 2) }}"
                                        stroke-dashoffset="{{ round($ringOffset, 2) }}"
                                        class="transition-all duration-500 ease-out"/>
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                <span class="font-display text-3xl font-extrabold text-slate-900">{{ $solvedToday }}</span>
                                <span class="text-[11px] font-bold uppercase tracking-wider text-muted-ink">of {{ $dailyGoal }}</span>
                            </div>
                        </div>

                        <p class="mt-3 text-xs font-medium text-muted-ink">
                            @if ($solvedToday >= $dailyGoal)
                                🎉 Goal completed! Streak protected.
                            @else
                                {{ max(0, $dailyGoal - $solvedToday) }} questions left to maintain your streak.
                            @endif
                        </p>
                    </section>

                    {{-- Tier Widget --}}
                    <section class="rounded-2xl border-2 border-b-4 border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="text-xl">🛡️</span>
                                <h3 class="font-display text-base font-extrabold text-slate-900">{{ $tierName }}</h3>
                            </div>
                            <span class="rounded-md bg-gold-tint px-2 py-0.5 font-display text-[11px] font-bold text-gold-ink">
                                Rank
                            </span>
                        </div>

                        <p class="mt-1 text-xs font-medium text-muted-ink">
                            {{ number_format($xpToNextTier) }} XP until {{ $nextTierName }}
                        </p>

                        <div class="mt-4 h-3.5 overflow-hidden rounded-full bg-slate-100 p-0.5 ring-1 ring-inset ring-slate-200/60">
                            <div class="h-full rounded-full bg-gold transition-all duration-500"
                                 style="width: {{ $tierProgress }}%"></div>
                        </div>

                        <div class="mt-2 flex justify-between font-display text-[11px] font-bold text-muted-ink">
                            <span>{{ number_format($tierFloorXp) }} XP</span>
                            <span>{{ number_format($tierCeilingXp) }} XP</span>
                        </div>
                    </section>

                </aside>

            </div>
        </div>
    </div>
</x-app-layout>