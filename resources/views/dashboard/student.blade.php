<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-2">
            <div>
                {{-- Responsive text size so it stays compact on mobile --}}
                <h2 class="font-display text-xl sm:text-2xl lg:text-3xl font-extrabold tracking-tight text-slate-900">
                    <span class="block sm:hidden">Dashboard</span>
                    <span class="hidden sm:block">Student Dashboard</span>
                </h2>
                <p class="hidden sm:block text-xs font-semibold uppercase tracking-wider text-muted-ink">
                    PhLE Adaptive Review Engine
                </p>
            </div>

            {{-- Compact badges with smaller mobile padding to guarantee 1-row fit --}}
            <div class="flex items-center gap-1.5 shrink-0">
                @php
                    $streak = (int) ($user->streak_count ?? 0);
                    $xp = (int) ($user->total_xp ?? 0);
                @endphp

                {{-- Streak Pill --}}
                <div class="flex items-center gap-1 rounded-xl border-2 border-b-2 sm:border-b-4 border-[#F5A623]/30 bg-gold-tint px-2.5 py-1 sm:px-3.5 sm:py-1.5 font-display font-bold text-gold-ink shadow-2xs"
                     title="{{ $streak }}-day study streak">
                    <span class="text-xs sm:text-sm">🔥</span>
                    <span class="text-xs sm:text-sm font-black">{{ $streak }}</span>
                    <span class="text-[9px] tracking-wider opacity-80 hidden xs:inline">DAYS</span>
                </div>

                {{-- XP Pill --}}
                <div class="flex items-center gap-1 rounded-xl border-2 border-b-2 sm:border-b-4 border-[#F5A623]/30 bg-gold-tint px-2.5 py-1 sm:px-3.5 sm:py-1.5 font-display font-bold text-gold-ink shadow-2xs"
                     title="Total experience points">
                    <span class="text-xs sm:text-sm">⚡</span>
                    <span class="text-xs sm:text-sm font-black">{{ number_format($xp) }}</span>
                    <span class="text-[9px] tracking-wider opacity-80 hidden xs:inline">XP</span>
                </div>
            </div>
        </div>
    </x-slot>

    @php
        // Readiness badge styles with CitiRx design tokens
        $readinessStyles = match ($readinessBand ?? null) {
            'board_ready' => ['label' => 'Board Ready',           'bg' => 'bg-strong-tint',   'text' => 'text-strong-ink',   'border' => 'border-[#22C55E]/40', 'hex' => '#22C55E'],
            'approaching' => ['label' => 'Approaching Readiness', 'bg' => 'bg-clinical-tint', 'text' => 'text-clinical-ink', 'border' => 'border-[#0EA5A4]/40', 'hex' => '#0EA5A4'],
            'developing'  => ['label' => 'Developing',            'bg' => 'bg-gold-tint',     'text' => 'text-gold-ink',     'border' => 'border-[#F5A623]/40', 'hex' => '#F5A623'],
            'at_risk'     => ['label' => 'At Risk',               'bg' => 'bg-weak-tint',     'text' => 'text-weak-ink',     'border' => 'border-[#F0524F]/40', 'hex' => '#F0524F'],
            default       => ['label' => 'Pending Diagnostic',    'bg' => 'bg-slate-100',     'text' => 'text-muted-ink',    'border' => 'border-slate-200',    'hex' => '#94A3B8'],
        };
    @endphp

    <div class="py-6 font-sans text-slate-900 antialiased">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- Welcome Bar --}}
            <div class="flex items-center justify-between rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-5 sm:p-6 shadow-sm">
                <div class="flex items-center gap-4">
                    <div>
                        <h1 class="font-display text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
                            Welcome back, {{ method_exists($user, 'fullName') ? $user->fullName() : $user->name }}!
                        </h1>
                        <p class="text-xs font-medium text-muted-ink mt-0.5">
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
                        <section class="relative overflow-hidden rounded-[1.5rem] border-2 border-b-4 border-primary/40 bg-gradient-to-br from-primary-tint via-[#EDE8FF] to-white p-6 sm:p-8 shadow-sm">
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
                        <section class="relative overflow-hidden rounded-[1.5rem] border-2 border-b-4 border-primary/30 bg-gradient-to-br from-primary-tint via-[#FAF8FF] to-white p-6 sm:p-8 shadow-sm">
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
                                Adaptive Practice
                            </h2>
                            <p class="mt-2 max-w-xl text-sm leading-relaxed text-slate-700">
                                Continue targeted practice sessions to expand your mastery scores across pharmacy domains.
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

                    {{-- MOBILE-ONLY LEVEL WIDGET (Appears first on mobile screens before Hub cards) --}}
                    <div class="block lg:hidden">
                        <section class="rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-6 shadow-sm">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gold-tint text-xl shadow-2xs">
                                        🛡️
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="font-display text-base font-extrabold text-slate-900 leading-tight">
                                                Level {{ $user->current_level ?? 1 }}
                                            </h3>
                                            @if ($user->level?->tier)
                                                <span class="inline-flex items-center rounded-lg bg-primary-tint px-2 py-0.5 font-display text-[10px] font-bold text-primary">
                                                    {{ $user->level->tier->tier_name }}
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-xs font-semibold text-muted-ink mt-0.5">
                                            {{ number_format((int) $user->total_xp) }} Total XP
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-5 border-t border-slate-100 pt-4">
                                <a href="{{ route('progress.index') }}" class="font-display text-xs font-bold text-primary hover:underline">
                                    View Full Progress Breakdown →
                                </a>
                            </div>
                        </section>
                    </div>

                    {{-- 2. RX VAULT, BOOKMARKS & ACHIEVEMENTS ROW (Duolingo Tactile Button Vibe) --}}
                    <div class="grid gap-5 sm:grid-cols-3">
                        
                        {{-- Rx Vault Tile (Rose Theme) --}}
                        <div class="flex flex-col justify-between rounded-3xl border-2 border-b-4 border-rose-300 bg-gradient-to-br from-rose-50/80 via-white to-white p-5 sm:p-6 shadow-sm">
                            <div>
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl border-2 border-b-3 border-rose-300 bg-rose-100 text-2xl shadow-2xs">
                                    💊
                                </div>
                                <h4 class="mt-4 font-display text-lg font-black text-slate-900">Rx Vault</h4>
                                <p class="text-xs font-semibold text-muted-ink mt-0.5">
                                    @if ($activeVaultCount > 0)
                                        <span class="font-bold text-slate-900">{{ $activeVaultCount }}</span> {{ \Illuminate\Support\Str::plural('item', $activeVaultCount) }} to review.
                                    @else
                                        No pending items.
                                    @endif
                                </p>
                            </div>
                            <div class="mt-6">
                                <a href="{{ route('vault.index') }}"
                                   style="--lip: #BE123C;"
                                   class="btn-press block w-full rounded-2xl bg-rose-500 py-3 text-center font-display text-xs font-black uppercase tracking-wider text-white shadow-md transition hover:bg-rose-600">
                                    Open Vault
                                </a>
                            </div>
                        </div>

                        {{-- Bookmarks Tile (Amber Theme) --}}
                        <div class="flex flex-col justify-between rounded-3xl border-2 border-b-4 border-amber-300 bg-gradient-to-br from-amber-50/80 via-white to-white p-5 sm:p-6 shadow-sm">
                            <div>
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl border-2 border-b-3 border-amber-300 bg-amber-100 text-2xl shadow-2xs">
                                    ⭐
                                </div>
                                <h4 class="mt-4 font-display text-lg font-black text-slate-900">Bookmarks</h4>
                                <p class="text-xs font-semibold text-muted-ink mt-0.5">
                                    @if (($bookmarkCount ?? 0) > 0)
                                        <span class="font-bold text-slate-900">{{ $bookmarkCount }}</span> {{ \Illuminate\Support\Str::plural('question', $bookmarkCount) }} starred.
                                    @else
                                        No starred questions.
                                    @endif
                                </p>
                            </div>
                            <div class="mt-6">
                                <a href="{{ route('vault.index', ['tab' => 'bookmarks']) }}"
                                   style="--lip: #B45309;"
                                   class="btn-press block w-full rounded-2xl bg-amber-500 py-3 text-center font-display text-xs font-black uppercase tracking-wider text-white shadow-md transition hover:bg-amber-600">
                                    View Bookmarks
                                </a>
                            </div>
                        </div>

                        {{-- Achievements Tile (Indigo Theme) --}}
                        <div class="flex flex-col justify-between rounded-3xl border-2 border-b-4 border-indigo-300 bg-gradient-to-br from-indigo-50/80 via-white to-white p-5 sm:p-6 shadow-sm">
                            <div>
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl border-2 border-b-3 border-indigo-300 bg-indigo-100 text-2xl shadow-2xs">
                                    🏆
                                </div>
                                <h4 class="mt-4 font-display text-lg font-black text-slate-900">Badges</h4>
                                <p class="text-xs font-semibold text-muted-ink mt-0.5">
                                    <span class="font-bold text-slate-900">{{ $unlockedBadgeCount }}</span> of {{ $totalBadgeCount }} unlocked
                                </p>
                            </div>
                            <div class="mt-6">
                                <a href="{{ route('badges.index') }}"
                                   style="--lip: #4338CA;"
                                   class="btn-press block w-full rounded-2xl bg-indigo-600 py-3 text-center font-display text-xs font-black uppercase tracking-wider text-white shadow-md transition hover:bg-indigo-700">
                                    View Badges
                                </a>
                            </div>
                        </div>

                    </div>

                </div>

                {{-- SIDEBAR: Level & Readiness Widgets --}}
                <aside class="space-y-6">

                    {{-- Level & Progression Widget (Hidden on mobile since it's displayed above on mobile) --}}
                    <div class="hidden lg:block">
                        <section class="rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-6 shadow-sm">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gold-tint text-xl shadow-2xs">
                                        🛡️
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="font-display text-base font-extrabold text-slate-900 leading-tight">
                                                Level {{ $user->current_level ?? 1 }}
                                            </h3>
                                            @if ($user->level?->tier)
                                                <span class="inline-flex items-center rounded-lg bg-primary-tint px-2 py-0.5 font-display text-[10px] font-bold text-primary">
                                                    {{ $user->level->tier->tier_name }}
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-xs font-semibold text-muted-ink mt-0.5">
                                            {{ number_format((int) $user->total_xp) }} Total XP
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-5 border-t border-slate-100 pt-4">
                                <a href="{{ route('progress.index') }}" class="font-display text-xs font-bold text-primary hover:underline">
                                    View Full Progress Breakdown →
                                </a>
                            </div>
                        </section>
                    </div>

                    {{-- Board Readiness Prediction Widget --}}
                    @if ($user->is_diagnostic_completed)
                        <section class="rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-6 text-center shadow-sm">
                            <h3 class="font-display text-xs font-extrabold uppercase tracking-wider text-muted-ink">
                                Predicted PhLE Readiness
                            </h3>

                            <div class="mt-4 flex flex-col items-center">
                                @php 
                                    $pct = (float) ($user->predicted_readiness_pct ?? 0); 
                                    $dashCircumference = 251.33;
                                @endphp
                                
                                <div class="relative flex items-center justify-center w-32 h-32 mb-4"
                                     x-data="{
                                         target: {{ (float) $pct }},
                                         displayScore: '0.0',
                                         circumference: {{ $dashCircumference }}
                                     }"
                                     x-init="
                                         $nextTick(() => {
                                             let start = performance.now();
                                             let duration = 1200;
                                             let animate = (now) => {
                                                 let progress = Math.min((now - start) / duration, 1);
                                                 let ease = 1 - Math.pow(1 - progress, 3);
                                                 let val = ease * target;
                                                 
                                                 displayScore = val.toFixed(1);
                                                 
                                                 if ($refs.ring) {
                                                     let offset = circumference - (val / 100) * circumference;
                                                     $refs.ring.setAttribute('stroke-dashoffset', offset);
                                                 }

                                                 if (progress < 1) {
                                                     requestAnimationFrame(animate);
                                                 } else {
                                                     displayScore = target.toFixed(1);
                                                     if ($refs.ring) {
                                                         let finalOffset = circumference - (target / 100) * circumference;
                                                         $refs.ring.setAttribute('stroke-dashoffset', finalOffset);
                                                     }
                                                 }
                                             };
                                             requestAnimationFrame(animate);
                                         });
                                     ">
                                    
                                    <svg class="absolute inset-0 h-full w-full -rotate-90 transform" viewBox="0 0 100 100">
                                        <circle cx="50" cy="50" r="40" fill="none" stroke="#E2E8F0" stroke-width="10" />
                                        <circle x-ref="ring"
                                                cx="50" cy="50" r="40" fill="none" 
                                                stroke="{{ $readinessStyles['hex'] }}" 
                                                stroke-width="10" 
                                                stroke-linecap="round"
                                                stroke-dasharray="{{ $dashCircumference }}"
                                                stroke-dashoffset="{{ $dashCircumference }}" />
                                    </svg>
                                
                                    <div class="absolute flex flex-col items-center justify-center">
                                        <span class="font-display text-2xl font-extrabold text-slate-900 leading-none">
                                            <span x-text="displayScore">0.0</span><span class="text-sm font-bold text-muted-ink">%</span>
                                        </span>
                                    </div>
                                </div>

                                <span class="inline-flex items-center rounded-full border px-3 py-1 font-display text-xs font-bold {{ $readinessStyles['bg'] }} {{ $readinessStyles['text'] }} {{ $readinessStyles['border'] }}">
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

                </aside>

            </div>
        </div>
    </div>
</x-app-layout>