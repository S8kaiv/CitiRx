<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="font-display text-xl font-black text-slate-900 leading-tight">
                    {{ __('Achievements & Badges') }}
                </h1>
                <p class="hidden sm:block text-xs font-semibold uppercase tracking-wider text-muted-ink">
                    Milestones, Streaks & Special Honors
                </p>
            </div>
        </div>
    </x-slot>

    @php
        $unlockedCount = $userBadges->count();
        $totalCount = $badges->count();
        $progressPct = $totalCount > 0 ? round(($unlockedCount / $totalCount) * 100) : 0;
    @endphp

    <div class="pt-6 pb-16 font-sans text-slate-900 antialiased">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 space-y-6">

            {{-- TOP HERO CARD: TROPHY SHOWCASE & PROGRESSION --}}
            <div class="relative overflow-hidden rounded-[1.5rem] border-2 border-b-4 border-primary/30 bg-gradient-to-br from-primary-tint via-[#FAF8FF] to-white p-6 sm:p-8 shadow-sm">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-6">
                    
                    {{-- Trophy & Heading --}}
                    <div class="flex items-center gap-5 text-center sm:text-left">
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl border-2 border-b-4 border-[#F5A623]/40 bg-gold-tint text-3xl shadow-sm">
                            🏆
                        </div>
                        <div>
                            <span class="inline-flex items-center rounded-full bg-white px-3 py-0.5 font-display text-[11px] font-bold text-primary shadow-sm ring-1 ring-primary/25">
                                Review Milestone Honors
                            </span>
                            <h3 class="mt-1 font-display text-2xl font-extrabold text-slate-900 sm:text-3xl">
                                {{ $unlockedCount }} of {{ $totalCount }} Unlocked
                            </h3>
                            <p class="text-xs font-medium text-slate-600 mt-0.5">
                                Earn badges by hitting daily streaks, mastering modules, and completing practice drills.
                            </p>
                        </div>
                    </div>

                    {{-- Total Badge XP Pill --}}
                    <div class="flex flex-col items-center sm:items-end shrink-0">
                        <div class="flex items-center gap-2 rounded-2xl border-2 border-b-4 border-[#F5A623]/30 bg-white/90 px-4 py-2.5 shadow-sm">
                            <span class="text-lg">⚡</span>
                            <div>
                                <span class="block font-display text-base font-extrabold text-slate-900 leading-none">
                                    +{{ number_format($badgeXpTotal ?? 0) }} XP
                                </span>
                                <span class="block text-[10px] font-bold uppercase tracking-wider text-muted-ink mt-0.5">
                                    Total Honor Bonus
                                </span>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- Overall Badge Progress Track --}}
                <div class="mt-6 pt-4 border-t border-primary/15">
                    <div class="flex items-center justify-between text-xs mb-1.5">
                        <span class="font-display font-bold text-slate-800">Collection Completion</span>
                        <span class="font-display font-extrabold text-primary">{{ $progressPct }}%</span>
                    </div>
                    <div class="h-3 w-full overflow-hidden rounded-full bg-white ring-1 ring-inset ring-primary/20 p-0.5 shadow-inner">
                        <div class="h-full rounded-full bg-primary transition-all duration-700 ease-out"
                             style="width: {{ $progressPct }}%"></div>
                    </div>
                </div>
            </div>

            {{-- BADGES GRID --}}
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($badges as $badge)
                    @php
                        $userBadge = $userBadges->get($badge->badge_id);
                        $isUnlocked = $userBadge !== null;

                        // Optional: Select dynamic icon based on badge attributes or slug/name
                        $badgeIcon = match(true) {
                            str_contains(strtolower($badge->badge_name), 'streak') => '🔥',
                            str_contains(strtolower($badge->badge_name), 'xp') || str_contains(strtolower($badge->badge_name), 'master') => '⚡',
                            str_contains(strtolower($badge->badge_name), 'diagnostic') || str_contains(strtolower($badge->badge_name), 'test') => '🎯',
                            default => '🏆',
                        };
                    @endphp

                    @if ($isUnlocked)
                        {{-- UNLOCKED BADGE: Tactile 3D Card --}}
                        <div class="group flex flex-col justify-between rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-5 shadow-xs transition hover:-translate-y-0.5 hover:border-slate-300">
                            <div>
                                <div class="flex items-start justify-between gap-3">
                                    {{-- Badge Token --}}
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border-2 border-b-4 border-[#F5A623]/30 bg-gold-tint text-2xl shadow-2xs group-hover:scale-105 transition">
                                        {{ $badgeIcon }}
                                    </div>

                                    {{-- Reward XP Pill --}}
                                    <span class="inline-flex items-center gap-1 rounded-full border-2 border-[#F5A623]/40 bg-gold-tint px-2.5 py-0.5 font-display text-xs font-black text-gold-ink shadow-2xs">
                                        <span>⚡</span>
                                        +{{ $badge->xp_reward }} XP
                                    </span>
                                </div>

                                <h4 class="mt-4 font-display text-base font-black text-slate-900">
                                    {{ $badge->badge_name }}
                                </h4>
                                <p class="mt-1 text-xs leading-relaxed text-slate-600">
                                    {{ $badge->description }}
                                </p>
                            </div>

                            <div class="mt-5 pt-3 border-t-2 border-slate-100 flex items-center justify-between">
                                <span class="inline-flex items-center gap-1.5 font-display text-[11px] font-black uppercase tracking-wider text-emerald-600">
                                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                    Unlocked
                                </span>
                                <span class="text-[10px] font-bold text-muted-ink">
                                    {{ $userBadge->unlocked_at ? $userBadge->unlocked_at->diffForHumans() : 'Earned' }}
                                </span>
                            </div>
                        </div>
                    @else
                        {{-- LOCKED BADGE: Muted & Dashed Container --}}
                        <div class="flex flex-col justify-between rounded-3xl border-2 border-dashed border-slate-200 bg-slate-50/70 p-5 opacity-75">
                            <div>
                                <div class="flex items-start justify-between gap-3">
                                    {{-- Padlock Token --}}
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border-2 border-slate-200 bg-slate-100 text-xl text-slate-400 shadow-inner">
                                        🔒
                                    </div>

                                    {{-- Dimmed Reward Pill --}}
                                    <span class="inline-flex items-center rounded-full border-2 border-slate-200 bg-white px-2.5 py-0.5 font-display text-xs font-bold text-slate-400">
                                        +{{ $badge->xp_reward }} XP
                                    </span>
                                </div>

                                <h4 class="mt-4 font-display text-base font-bold text-slate-700">
                                    {{ $badge->badge_name }}
                                </h4>
                                <p class="mt-1 text-xs leading-relaxed text-muted-ink">
                                    {{ $badge->description }}
                                </p>
                            </div>

                            <div class="mt-5 pt-3 border-t-2 border-slate-200/60 flex items-center justify-between">
                                <span class="font-display text-[11px] font-bold uppercase tracking-wider text-muted-ink">
                                    Locked
                                </span>
                                <span class="text-[10px] text-slate-400 font-medium">
                                    Incomplete
                                </span>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            {{-- CALL TO ACTION BUTTONS --}}
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-4">
                <a href="{{ route('practice.intro') }}"
                   style="--lip: #4A2FC4;"
                   class="btn-press w-full sm:w-auto rounded-2xl bg-primary px-8 py-3.5 text-center font-display text-xs font-black uppercase tracking-wider text-white shadow-sm transition hover:bg-primary/95">
                    Practice to Earn XP
                </a>

                <a href="{{ route('dashboard') }}"
                   style="--lip: #CBD5E1;"
                   class="btn-press w-full sm:w-auto rounded-2xl border-2 border-b-4 border-slate-300 bg-white px-8 py-3.5 text-center font-display text-xs font-black uppercase tracking-wider text-slate-700 transition hover:bg-slate-50">
                    Back to Dashboard
                </a>
            </div>

        </div>
    </div>
</x-app-layout>