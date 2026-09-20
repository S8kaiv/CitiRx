<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-display text-xl font-bold leading-tight text-slate-900">
                    {{ __('Level Progression') }}
                </h2>
                <p class="text-xs font-semibold uppercase tracking-wider text-muted-ink">
                    Experience & Tier Milestones
                </p>
            </div>

            <a href="{{ route('dashboard') }}" 
               class="font-display text-xs font-bold text-muted-ink transition hover:text-primary">
                Back to Dashboard
            </a>
        </div>
    </x-slot>

    <div class="pt-6 pb-16 font-sans antialiased text-slate-900">
        <div class="max-w-4xl px-4 mx-auto space-y-6 sm:px-6">

            {{-- ========================================= --}}
            {{-- CURRENT LEVEL HERO CARD                   --}}
            {{-- ========================================= --}}
            <section class="relative overflow-hidden rounded-[1.5rem] border-2 border-b-4 border-[#F5A623]/30 bg-gradient-to-br from-gold-tint via-[#FFFBF2] to-white p-6 sm:p-8 shadow-sm text-center">
                
                {{-- Level Badge Icon --}}
                <div class="flex items-center justify-center w-14 h-14 mx-auto mb-3 text-3xl bg-white border-2 border-b-4 rounded-2xl border-[#F5A623]/40 shadow-xs">
                    🛡️
                </div>

                <p class="font-display text-xs font-bold uppercase tracking-wider text-muted-ink">
                    Current Standing
                </p>

                <h3 class="mt-1 font-display text-4xl sm:text-5xl font-extrabold tracking-tight text-slate-900 leading-tight">
                    Level {{ $currentLevel->level_number }}
                </h3>

                <div class="mt-2.5 flex items-center justify-center gap-2">
                    <span class="inline-flex items-center rounded-full bg-clinical-tint px-3 py-1 font-display text-xs font-bold text-clinical-ink shadow-xs ring-1 ring-clinical/30">
                        {{ $currentLevel->tier->tier_name }} Tier
                    </span>
                    <span class="font-bold text-slate-300">•</span>
                    <span class="font-display text-xs font-bold text-gold-ink">
                        {{ number_format((int) $user->total_xp) }} Total XP
                    </span>
                </div>

                {{-- Progression Toward Next Level --}}
                @if ($nextLevel)
                    <div class="max-w-md mx-auto mt-6">
                        <div class="flex items-center justify-between mb-2 font-display text-xs font-bold uppercase tracking-wider text-muted-ink">
                            <span>Level {{ $currentLevel->level_number }}</span>
                            <span>Level {{ $nextLevel->level_number }}</span>
                        </div>

                        {{-- Progress Bar --}}
                        <div role="progressbar"
                             aria-label="Progress toward Level {{ $nextLevel->level_number }}"
                             aria-valuemin="0"
                             aria-valuemax="100"
                             aria-valuenow="{{ round($progressPercent) }}"
                             class="h-3.5 w-full overflow-hidden rounded-full bg-slate-100 p-0.5 ring-1 ring-inset ring-slate-200/60">
                            <div class="h-full rounded-full bg-primary transition-all duration-700"
                                 style="width: {{ $progressPercent }}%"></div>
                        </div>

                        <div class="flex items-center justify-between mt-2.5 text-xs">
                            <span class="font-medium text-slate-600">
                                <strong>{{ number_format($xpToNext) }} XP</strong> needed for Level {{ $nextLevel->level_number }} ({{ $nextLevel->tier->tier_name }})
                            </span>
                            <span class="font-display font-bold text-slate-800 shrink-0">
                                {{ number_format($progressPercent, 1) }}%
                            </span>
                        </div>
                    </div>
                @else
                    <div class="mt-6 inline-flex items-center gap-2 rounded-xl border border-strong/30 bg-strong-tint/60 px-4 py-2 font-display text-xs font-bold text-strong-ink shadow-xs">
                        <span>✨</span>
                        <span>Maximum level reached! Continue practice sessions to refine board readiness.</span>
                    </div>
                @endif
            </section>

            {{-- ========================================= --}}
            {{-- LEVEL LADDER MILESTONES                   --}}
            {{-- ========================================= --}}
            <section class="overflow-hidden bg-white border-2 border-b-4 border-slate-200 rounded-2xl shadow-sm">
                <div class="p-6 border-b border-slate-100 sm:px-8">
                    <h3 class="font-display text-base sm:text-lg font-bold text-slate-900">
                        Level Ladder
                    </h3>
                    <p class="mt-0.5 text-xs text-muted-ink">
                        Advance through tiers by earning XP during practice sessions and unlocking milestones.
                    </p>
                </div>

                <div class="p-6 sm:px-8 space-y-2.5">
                    @foreach ($levels as $level)
                        @php
                            $isCurrent = (int) $level->level_number === (int) $currentLevel->level_number;
                            $isReached = (int) $level->min_xp <= (int) $user->total_xp;
                        @endphp

                        <div @class([
                            'flex items-center justify-between rounded-xl px-4 py-3 transition-all',
                            'border-2 border-b-4 border-primary/40 bg-primary-tint/30 shadow-xs' => $isCurrent,
                            'border-2 border-b-4 border-strong/30 bg-strong-tint/30' => $isReached && !$isCurrent,
                            'border-2 border-slate-200 bg-slate-50/60 opacity-60' => !$isReached,
                        ])>
                            <div class="flex items-center gap-3.5">
                                {{-- Status Icon Pip --}}
                                <span class="flex items-center justify-center w-8 h-8 rounded-lg font-display text-sm font-bold shrink-0
                                    {{ $isCurrent ? 'bg-primary text-white shadow-xs' : ($isReached ? 'bg-strong text-white' : 'bg-slate-200 text-slate-500') }}">
                                    @if ($isCurrent)
                                        🎯
                                    @elseif ($isReached)
                                        ✓
                                    @else
                                        🔒
                                    @endif
                                </span>

                                <div>
                                    <div class="flex items-center gap-2">
                                        <p class="font-display text-sm font-bold text-slate-900">
                                            Level {{ $level->level_number }}
                                        </p>
                                        @if ($isCurrent)
                                            <span class="rounded-md bg-primary px-1.5 py-0.5 font-display text-[10px] font-bold text-white uppercase tracking-wider">
                                                Current
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-xs font-semibold text-muted-ink">
                                        {{ $level->tier->tier_name }} Tier
                                    </p>
                                </div>
                            </div>

                            <div class="text-right">
                                <span class="font-display text-xs font-bold text-slate-800">
                                    {{ number_format((int) $level->min_xp) }} XP
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- ========================================= --}}
            {{-- ACTION BUTTONS                            --}}
            {{-- ========================================= --}}
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
                <a href="{{ route('practice.intro') }}"
                   style="--lip: #4A2FC4;"
                   class="btn-press w-full sm:w-auto rounded-xl bg-primary px-8 py-3 text-center font-display text-sm font-bold text-white shadow-sm transition hover:bg-primary/95">
                    Earn More XP
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