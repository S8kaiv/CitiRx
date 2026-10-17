<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="font-display text-xl font-black text-slate-900 leading-tight">
                    {{ __('Level Progression') }}
                </h1>
                <p class="hidden sm:block text-xs font-semibold uppercase tracking-wider text-muted-ink">
                    Experience & Tier Milestones
                </p>
            </div>
        </div>
    </x-slot>

    @php
        // Group levels by Tier for segmented roadmap chapters
        $groupedLevels = $levels->groupBy(fn($l) => $l->tier->tier_name ?? 'Standard');

        // Tier theme accents
        $tierStyles = [
            'Beginner'     => ['border' => 'border-amber-300',   'badge' => 'bg-amber-100 border-amber-300 text-amber-900',    'accent' => '#F5A623', 'icon' => '🥉'],
            'Intermediate' => ['border' => 'border-teal-300',    'badge' => 'bg-teal-100 border-teal-300 text-teal-900',      'accent' => '#0EA5A4', 'icon' => '🥈'],
            'Advanced'     => ['border' => 'border-indigo-300',  'badge' => 'bg-indigo-100 border-indigo-300 text-indigo-900',  'accent' => '#6366F1', 'icon' => '🥇'],
            'Board Ready'  => ['border' => 'border-emerald-300', 'badge' => 'bg-emerald-100 border-emerald-300 text-emerald-900', 'accent' => '#22C55E', 'icon' => '👑'],
        ];
    @endphp

    <div class="pt-6 pb-16 font-sans antialiased text-slate-900">
        <div class="max-w-3xl px-4 mx-auto space-y-8 sm:px-6">

            {{-- ========================================= --}}
            {{-- 1. CURRENT LEVEL HERO CARD (Chunky 2.5D)  --}}
            {{-- ========================================= --}}
            <section class="relative overflow-hidden rounded-3xl border-2 border-b-4 border-amber-300 bg-gradient-to-br from-amber-50 via-white to-orange-50/30 p-6 sm:p-8 shadow-sm">
                
                <div class="flex flex-col sm:flex-row items-center sm:items-center gap-5 text-center sm:text-left">
                    
                    {{-- Tactile 3D Crest --}}
                    <div style="--lip: #D97706;" 
                         class="btn-press flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl border-2 border-b-4 border-amber-400 bg-gradient-to-b from-amber-100 to-amber-200/70 text-3xl shadow-sm">
                        🛡️
                    </div>

                    {{-- Content Block --}}
                    <div class="flex-1">
                        {{-- Unified Tier Pill --}}
                        <div class="flex items-center justify-center sm:justify-start gap-2">
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-300 bg-amber-100/70 px-2.5 py-0.5 font-display text-[11px] font-extrabold uppercase tracking-wider text-amber-900">
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                {{ $currentLevel->tier->tier_name }} Tier
                            </span>
                            <span class="font-display text-xs font-bold text-muted-ink">• Active Rank</span>
                        </div>

                        {{-- Level Title + XP Badge Row --}}
                        <div class="mt-1 flex flex-wrap items-center justify-center sm:justify-start gap-3">
                            <h3 class="font-display text-3xl sm:text-4xl font-black tracking-tight text-slate-900 leading-none">
                                Level {{ $currentLevel->level_number }}
                            </h3>

                            {{-- Tactile XP Chip --}}
                            <div class="inline-flex items-center gap-1.5 rounded-xl border-2 border-b-3 border-amber-300/80 bg-amber-50 px-2.5 py-1 shadow-2xs">
                                <span class="font-mono text-xs font-black text-amber-900">
                                    {{ number_format((int) $user->total_xp) }}
                                </span>
                                <span class="font-display text-[10px] font-extrabold uppercase text-amber-700/80">XP</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Progression Toward Next Level --}}
                @if ($nextLevel)
                    <div class="mt-6 pt-6 border-t border-amber-200/60"
                         x-data="{ barWidth: '0%' }"
                         x-init="$nextTick(() => { setTimeout(() => barWidth = '{{ min(100, max(0, round($progressPercent, 1))) }}%', 120) })">
                        
                        <div class="flex items-center justify-between mb-2 font-display text-xs font-extrabold uppercase tracking-wider text-slate-600">
                            <span>Level {{ $currentLevel->level_number }}</span>
                            <span class="text-primary font-black">Level {{ $nextLevel->level_number }}</span>
                        </div>

                        {{-- Chunky Progress Track --}}
                        <div class="h-4 w-full overflow-hidden rounded-full bg-slate-200/70 p-0.5 ring-1 ring-inset ring-slate-300">
                            <div class="h-full rounded-full bg-gradient-to-r from-primary to-indigo-600 transition-all duration-1000 ease-out shadow-inner"
                                 style="width: 0%;"
                                 :style="'width: ' + barWidth"></div>
                        </div>

                        <div class="flex items-center justify-between mt-2.5 text-xs">
                            <span class="font-medium text-slate-600">
                                <strong>{{ number_format($xpToNext) }} XP</strong> needed for Level {{ $nextLevel->level_number }} ({{ $nextLevel->tier->tier_name }})
                            </span>
                            <span class="font-display font-extrabold text-slate-900 shrink-0">
                                {{ number_format($progressPercent, 1) }}%
                            </span>
                        </div>
                    </div>
                @else
                    <div class="mt-6 inline-flex items-center gap-2 rounded-2xl border-2 border-b-4 border-strong/40 bg-strong-tint/60 px-4 py-2 font-display text-xs font-bold text-strong-ink shadow-xs">
                        <span>👑</span>
                        <span>Maximum level reached! Continue drilling to sharpen your PRC TOS readiness.</span>
                    </div>
                @endif
            </section>

            {{-- ========================================= --}}
            {{-- 2. CONNECTED TROPHY ROAD TIMELINE         --}}
            {{-- ========================================= --}}
            <div class="space-y-8">
                <div>
                    <h3 class="font-display text-xl font-black text-slate-900">
                        Milestone Trophy Road
                    </h3>
                    <p class="text-xs font-medium text-muted-ink">
                        Climb through each professional competency tier by earning practice XP.
                    </p>
                </div>

                @foreach ($groupedLevels as $tierName => $tierLevels)
                    @php
                        $style = $tierStyles[$tierName] ?? [
                            'border' => 'border-slate-300',
                            'badge'  => 'bg-slate-100 border-slate-300 text-slate-800',
                            'accent' => '#64748B',
                            'icon'   => '⭐',
                        ];
                    @endphp

                    <div class="rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-5 sm:p-7 shadow-sm">
                        
                        {{-- Chapter Header --}}
                        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-6">
                            <div class="flex items-center gap-2.5">
                                <span class="text-xl">{{ $style['icon'] }}</span>
                                <div>
                                    <h4 class="font-display text-base font-extrabold text-slate-900">
                                        {{ $tierName }} Tier
                                    </h4>
                                    <p class="text-[11px] font-semibold text-muted-ink">
                                        Levels {{ $tierLevels->first()->level_number }} – {{ $tierLevels->last()->level_number }}
                                    </p>
                                </div>
                            </div>

                            <span class="rounded-full border px-3 py-0.5 font-display text-[11px] font-bold uppercase tracking-wider {{ $style['badge'] }}">
                                {{ $tierName }}
                            </span>
                        </div>

                        {{-- Timeline Track Wrapper --}}
                        <div class="relative space-y-4 pl-2">
                            
                            {{-- Base Gray Spine (Contained securely inside this tier card) --}}
                            <div class="absolute left-8 top-6 bottom-6 w-1.5 -translate-x-1/2 rounded-full bg-slate-200 z-0"></div>

                            @foreach ($tierLevels as $level)
                                @php
                                    $isCurrent = (int) $level->level_number === (int) $currentLevel->level_number;
                                    $isReached = (int) $level->min_xp <= (int) $user->total_xp;
                                @endphp

                                <div class="relative z-10 flex items-center gap-4">
                                    
                                    {{-- Tactile Node Puck --}}
                                    @if ($isCurrent)
                                        <div style="--lip: #4A2FC4;"
                                             class="btn-press flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border-2 border-b-4 border-primary/50 bg-primary text-white shadow-md ring-4 ring-primary/20">
                                            <span class="font-display text-base font-black">🎯</span>
                                        </div>
                                    @elseif ($isReached)
                                        <div style="--lip: #15803D;"
                                             class="btn-press flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border-2 border-b-4 border-strong/50 bg-strong text-white shadow-xs">
                                            <span class="font-display text-base font-black">✓</span>
                                        </div>
                                    @else
                                        <div style="--lip: #94A3B8;"
                                             class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border-2 border-b-4 border-slate-300 bg-slate-100 text-slate-400 shadow-xs">
                                            <span class="text-sm">🔒</span>
                                        </div>
                                    @endif

                                    {{-- Level Milestone Card --}}
                                    <div @class([
                                        'flex-1 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 rounded-2xl p-3.5 sm:p-4 transition-all',
                                        'border-2 border-b-4 border-primary/40 bg-primary-tint/30 shadow-xs ring-1 ring-primary/20' => $isCurrent,
                                        'border-2 border-b-4 border-strong/30 bg-strong-tint/20' => $isReached && !$isCurrent,
                                        'border-2 border-b-4 border-slate-200 bg-slate-50/50 opacity-75' => !$isReached,
                                    ])>
                                        <div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <h5 class="font-display text-sm sm:text-base font-black text-slate-900 leading-none">
                                                    Level {{ $level->level_number }}
                                                </h5>
                                                
                                                @if ($isCurrent)
                                                    <span class="rounded-full bg-primary px-2 py-0.5 font-display text-[9px] sm:text-[10px] font-black uppercase tracking-wider text-white shadow-xs">
                                                        Active Standing
                                                    </span>
                                                @elseif ($isReached)
                                                    <span class="font-display text-[11px] font-bold text-strong-ink">
                                                        Unlocked
                                                    </span>
                                                @endif
                                            </div>

                                            <p class="text-xs font-semibold text-muted-ink mt-1">
                                                {{ $tierName }} Tier
                                            </p>
                                        </div>

                                        {{-- XP Target Pill --}}
                                        <div class="self-start sm:self-auto">
                                            <span @class([
                                                'inline-flex items-center rounded-xl border px-2.5 py-1 font-display text-xs font-extrabold shadow-2xs',
                                                'border-primary/30 bg-white text-primary' => $isCurrent,
                                                'border-strong/30 bg-white text-strong-ink' => $isReached && !$isCurrent,
                                                'border-slate-200 bg-white/70 text-slate-600' => !$isReached,
                                            ])>
                                                {{ number_format((int) $level->min_xp) }} XP
                                            </span>
                                        </div>

                                    </div>
                                </div>
                            @endforeach

                        </div>
                    </div>
                @endforeach
            </div>

            {{-- ========================================= --}}
            {{-- 3. ACTION BUTTONS                         --}}
            {{-- ========================================= --}}
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-4">
                <a href="{{ route('practice.intro') }}"
                   style="--lip: #4A2FC4;"
                   class="btn-press w-full sm:w-auto rounded-2xl bg-primary px-8 py-3.5 text-center font-display text-sm font-black text-white shadow-sm transition hover:bg-primary/95">
                    Earn More XP →
                </a>

                <a href="{{ route('dashboard') }}"
                   style="--lip: #CBD5E1;"
                   class="btn-press w-full sm:w-auto rounded-2xl border-2 border-slate-200 bg-white px-8 py-3.5 text-center font-display text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                    Back to Dashboard
                </a>
            </div>

        </div>
    </div>
</x-app-layout>