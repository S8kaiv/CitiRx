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

            {{-- Streak & XP Counters --}}
            <div class="flex items-center gap-2.5">
                @php
                    $streak = (int) ($user->streak_count ?? 0);
                    $xp = (int) ($user->total_xp ?? 0);
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
        // Readiness badge styles with CitiRx design tokens
        $readinessStyles = match ($readinessBand ?? null) {
            'board_ready' => ['label' => 'Board Ready',           'bg' => 'bg-strong-tint',   'text' => 'text-strong-ink',   'border' => 'border-[#22C55E]/40'],
            'approaching' => ['label' => 'Approaching Readiness', 'bg' => 'bg-clinical-tint', 'text' => 'text-clinical-ink', 'border' => 'border-[#0EA5A4]/40'],
            'developing'  => ['label' => 'Developing',            'bg' => 'bg-gold-tint',     'text' => 'text-gold-ink',     'border' => 'border-[#F5A623]/40'],
            'at_risk'     => ['label' => 'At Risk',               'bg' => 'bg-weak-tint',     'text' => 'text-weak-ink',     'border' => 'border-[#F0524F]/40'],
            default       => ['label' => 'Pending Diagnostic',    'bg' => 'bg-slate-100',     'text' => 'text-muted-ink',    'border' => 'border-slate-200'],
        };
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

                    {{-- 2. RX VAULT, BOOKMARKS & ACHIEVEMENTS ROW --}}
                    <div class="grid gap-4 sm:grid-cols-3">
                        
                        {{-- Rx Vault Tile --}}
                        <div class="flex flex-col justify-between rounded-2xl border-2 border-b-4 border-slate-200 bg-white p-4 sm:p-5 shadow-sm">
                            <div>
                                <div class="flex h-11 w-11 items-center justify-center rounded-2xl border-2 border-b-4 border-[#F5A623]/30 bg-gold-tint text-xl shadow-sm">
                                    💊
                                </div>
                                <h4 class="mt-3 font-display text-base font-bold text-slate-900">Rx Vault</h4>
                                <p class="text-xs font-medium text-muted-ink mt-0.5">
                                    @if ($activeVaultCount > 0)
                                        <span class="font-bold text-slate-900">{{ $activeVaultCount }}</span> {{ \Illuminate\Support\Str::plural('item', $activeVaultCount) }} to review.
                                    @else
                                        No pending items.
                                    @endif
                                </p>
                            </div>
                            <div class="mt-4">
                                <a href="{{ route('vault.index') }}"
                                   style="--lip: #CBD5E1;"
                                   class="btn-press block w-full rounded-xl border-2 border-slate-200 bg-slate-50 py-1.5 text-center font-display text-[11px] font-extrabold uppercase tracking-wider text-slate-700 hover:bg-slate-100">
                                    Open Vault
                                </a>
                            </div>
                        </div>

                        {{-- Bookmarks Tile --}}
                        <div class="flex flex-col justify-between rounded-2xl border-2 border-b-4 border-slate-200 bg-white p-4 sm:p-5 shadow-sm">
                            <div>
                                <div class="flex h-11 w-11 items-center justify-center rounded-2xl border-2 border-b-4 border-primary/30 bg-primary-tint text-xl shadow-sm">
                                    ⭐
                                </div>
                                <h4 class="mt-3 font-display text-base font-bold text-slate-900">Bookmarks</h4>
                                <p class="text-xs font-medium text-muted-ink mt-0.5">
                                    @if (($bookmarkCount ?? 0) > 0)
                                        <span class="font-bold text-slate-900">{{ $bookmarkCount }}</span> {{ \Illuminate\Support\Str::plural('question', $bookmarkCount) }} starred.
                                    @else
                                        No starred questions.
                                    @endif
                                </p>
                            </div>
                            <div class="mt-4">
                                <a href="{{ route('bookmarks.index') }}"
                                   style="--lip: #CBD5E1;"
                                   class="btn-press block w-full rounded-xl border-2 border-slate-200 bg-slate-50 py-1.5 text-center font-display text-[11px] font-extrabold uppercase tracking-wider text-slate-700 hover:bg-slate-100">
                                    Bookmarks
                                </a>
                            </div>
                        </div>

                        {{-- Achievements Tile --}}
                        <div class="flex flex-col justify-between rounded-2xl border-2 border-b-4 border-slate-200 bg-white p-4 sm:p-5 shadow-sm">
                            <div>
                                <div class="flex h-11 w-11 items-center justify-center rounded-2xl border-2 border-b-4 border-[#F5A623]/30 bg-gold-tint text-xl shadow-sm">
                                    🏆
                                </div>
                                <h4 class="mt-3 font-display text-base font-bold text-slate-900">Badges</h4>
                                <p class="text-xs font-medium text-muted-ink mt-0.5">
                                    <span class="font-bold text-slate-900">{{ $unlockedBadgeCount }}</span> of {{ $totalBadgeCount }} unlocked
                                </p>
                            </div>
                            <div class="mt-4">
                                <a href="{{ route('badges.index') }}"
                                   style="--lip: #CBD5E1;"
                                   class="btn-press block w-full rounded-xl border-2 border-slate-200 bg-slate-50 py-1.5 text-center font-display text-[11px] font-extrabold uppercase tracking-wider text-slate-700 hover:bg-slate-100">
                                    View Badges
                                </a>
                            </div>
                        </div>

                    </div>

                </div>

                {{-- SIDEBAR: Level & Readiness Widgets --}}
                <aside class="space-y-6">

                    {{-- Level & Progression Widget --}}
                    <section class="rounded-2xl border-2 border-b-4 border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gold-tint text-xl">
                                    🛡️
                                </div>
                                <div>
                                    <h3 class="font-display text-base font-extrabold text-slate-900 leading-snug">
                                        Level {{ $user->current_level ?? 1 }}
                                        @if ($user->level?->tier)
                                            — {{ $user->level->tier->tier_name }}
                                        @endif
                                    </h3>
                                    <p class="text-xs font-semibold text-muted-ink">
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

                </aside>

            </div>
        </div>
    </div>
</x-app-layout>