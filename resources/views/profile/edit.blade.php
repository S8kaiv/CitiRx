<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-display text-xl font-black text-slate-900 leading-tight">
                    {{ __('Account Profile') }}
                </h2>
                <p class="text-xs font-semibold uppercase tracking-wider text-muted-ink">
                    Manage Identity & Statistics
                </p>
            </div>
        </div>
    </x-slot>

    @php
        $user = $user ?? Auth::user();

        // Standardized full name and initials resolution
        $displayName = method_exists($user, 'fullName') ? trim($user->fullName()) : trim($user->name ?? 'User');
        if ($displayName === '') {
            $displayName = 'User';
        }

        $nameParts = preg_split('/\s+/', $displayName, -1, PREG_SPLIT_NO_EMPTY);
        $firstName = $nameParts[0] ?? 'U';
        $lastName = count($nameParts) > 1 ? $nameParts[array_key_last($nameParts)] : '';
        $initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));

        // Levels and Tiers calculation for the dynamic avatar ring
        if ($user?->role === 'student') {
            $user->loadMissing('level.tier');
        }
        $userLevel = $user?->role === 'student' ? $user->level : null;
        $levelNumber = (int) ($userLevel?->level_number ?? ($user?->current_level ?? 1));
        $tierName = strtolower($userLevel?->tier?->tier_name ?? 'beginner');
        $tierDisplayName = $userLevel?->tier?->tier_name ?? 'Beginner';

        // Avatar border styling based on the student's tier (matching app.blade.php)
        $tierRingClasses = match (true) {
            str_contains($tierName, 'expert')
                => 'ring-2 ring-primary border-primary/40 bg-primary-tint text-primary shadow-[0_0_10px_rgba(109,74,255,0.35)]',

            str_contains($tierName, 'advanced')
                => 'ring-2 ring-[#0EA5A4] border-[#0EA5A4]/40 bg-[#0EA5A4]/10 text-[#0E7A79] shadow-[0_0_10px_rgba(14,165,164,0.35)]',

            str_contains($tierName, 'intermediate')
                => 'ring-2 ring-[#F5A623] border-[#F5A623]/40 bg-[#F5A623]/10 text-[#B45309] shadow-[0_0_10px_rgba(245,166,35,0.3)]',

            default => 'ring-2 ring-slate-300 border-slate-200 bg-slate-100 text-slate-700',
        };

        $levelBadgeBg = match (true) {
            str_contains($tierName, 'expert') => 'bg-primary text-white',
            str_contains($tierName, 'advanced') => 'bg-[#0EA5A4] text-white',
            str_contains($tierName, 'intermediate') => 'bg-[#F5A623] text-white',
            default => 'bg-slate-700 text-white',
        };

        // Stats Variables
        $streak = (int) ($user->streak_count ?? 0);
        $xp = (int) ($user->total_xp ?? 0);
        $badgesCount = method_exists($user, 'badges') ? $user->badges()->count() : ($user->unlockedBadgeCount ?? 2);

        // Readiness Variables (For mobile view overview)
        $pct = (float) ($user->predicted_readiness_pct ?? 0);
        $dashPct = max(0, min(100, $pct));
        $dashCircumference = 251.33;
        $dashOffset = $dashCircumference * (1 - $dashPct / 100);

        $readinessBand = $user->readiness_band ?? null;
        if (empty($readinessBand) && $dashPct > 0) {
            if ($dashPct >= 75) $readinessBand = 'board_ready';
            elseif ($dashPct >= 50) $readinessBand = 'approaching';
            elseif ($dashPct >= 25) $readinessBand = 'developing';
            else $readinessBand = 'at_risk';
        }

        $readinessStyles = match ($readinessBand) {
            'board_ready' => ['label' => 'Board Ready', 'bg' => 'bg-strong-tint', 'text' => 'text-strong-ink', 'border' => 'border-[#22C55E]/40', 'hex' => '#22C55E'],
            'approaching' => ['label' => 'Approaching Readiness', 'bg' => 'bg-clinical-tint', 'text' => 'text-clinical-ink', 'border' => 'border-[#0EA5A4]/40', 'hex' => '#0EA5A4'],
            'developing' => ['label' => 'Developing', 'bg' => 'bg-gold-tint', 'text' => 'text-gold-ink', 'border' => 'border-[#F5A623]/40', 'hex' => '#F5A623'],
            'at_risk' => ['label' => 'At Risk', 'bg' => 'bg-weak-tint', 'text' => 'text-weak-ink', 'border' => 'border-[#F0524F]/40', 'hex' => '#F0524F'],
            default => ['label' => 'Pending Diagnostic', 'bg' => 'bg-slate-100', 'text' => 'text-muted-ink', 'border' => 'border-slate-200', 'hex' => '#94A3B8'],
        };
    @endphp

    <style>
        [x-cloak] { display: none !important; }
    </style>

    {{-- Alpine State: activeTab controls switching between Overview and Settings --}}
    <div class="pt-6 pb-20 font-sans text-slate-900 antialiased" x-data="{ activeTab: 'overview' }">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 space-y-6">

            {{-- 1. STUDENT IDENTITY CREST WITH DYNAMIC TIER AVATAR --}}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6 rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-6 sm:p-8 shadow-sm">
                <div class="flex items-center gap-4 min-w-0">
                    
                    {{-- DYNAMIC TIER-BASED AVATAR RING & LEVEL BADGE --}}
                    <div class="relative shrink-0">
                        <div class="flex h-16 w-16 items-center justify-center rounded-2xl border-2 border-b-4 font-display text-xl font-black transition-all {{ $tierRingClasses }}">
                            {{ $initials }}
                        </div>
                        <span class="absolute -bottom-1 -right-1 flex h-6 min-w-6 px-1.5 items-center justify-center rounded-full font-mono text-xs font-black ring-2 ring-white shadow-xs {{ $levelBadgeBg }}">
                            {{ $levelNumber }}
                        </span>
                    </div>

                    <div class="min-w-0">
                        <h3 class="font-display text-xl font-black text-slate-900 truncate">
                            {{ $displayName }}
                        </h3>
                        <p class="text-xs text-muted-ink font-medium mt-0.5 truncate">
                            {{ $user->email }}
                            @if ($user->cohort)
                                · <span class="font-semibold text-slate-700">{{ $user->cohort->cohort_name }}</span>
                            @endif
                        </p>
                        <div class="flex items-center gap-2 mt-2">
                            <span class="rounded-lg border-2 border-primary/20 bg-primary-tint px-2.5 py-0.5 font-display text-[10px] font-black uppercase tracking-wider text-primary">
                                {{ $tierDisplayName }} Tier
                            </span>
                            <span class="rounded-lg border-2 border-[#F5A623]/30 bg-gold-tint px-2.5 py-0.5 font-display text-[10px] font-black uppercase tracking-wider text-gold-ink">
                                {{ number_format($xp) }} XP
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Settings Tab Switcher Button --}}
                <div class="flex items-center shrink-0">
                    <button @click="activeTab = (activeTab === 'overview' ? 'settings' : 'overview')" style="--lip: #4A2FC4;" class="btn-press w-full sm:w-auto inline-flex justify-center items-center gap-2 rounded-2xl bg-primary px-5 py-3 font-display text-xs font-black uppercase tracking-wider text-white shadow-sm transition hover:bg-primary/95">
                        <span x-text="activeTab === 'overview' ? 'Settings' : 'Overview'">Settings</span>
                    </button>
                </div>
            </div>

            {{-- ========================================================= --}}
            {{-- TAB 1: OVERVIEW (Statistics, Mobile Readiness & Logout)   --}}
            {{-- ========================================================= --}}
            <div x-show="activeTab === 'overview'" 
                 x-transition:enter="transition ease-out duration-200" 
                 x-transition:enter-start="opacity-0 translate-y-2" 
                 x-transition:enter-end="opacity-100 translate-y-0" 
                 class="space-y-6">

                <div class="space-y-4">
                    <h3 class="font-display text-lg font-black text-slate-900 px-1">
                        Statistics
                    </h3>

                    {{-- 2x2 Grid (Forced 2 columns even on mobile devices) --}}
                    <div class="grid grid-cols-2 gap-4">
                        
                        {{-- Card 1: Streak --}}
                        <div class="flex items-center gap-3 sm:gap-4 rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-4 sm:p-5 shadow-sm">
                            <div class="flex h-10 w-10 sm:h-12 sm:w-12 shrink-0 items-center justify-center rounded-2xl border-2 border-b-3 border-amber-300 bg-gold-tint text-lg sm:text-xl shadow-2xs">
                                🔥
                            </div>
                            <div class="min-w-0">
                                <span class="font-display text-xl sm:text-2xl font-black text-slate-900 leading-none block truncate">
                                    {{ $streak }}
                                </span>
                                <p class="font-display text-[10px] sm:text-xs font-bold uppercase tracking-wider text-muted-ink mt-0.5 truncate">
                                    Day Streak
                                </p>
                            </div>
                        </div>

                        {{-- Card 2: Total XP --}}
                        <div class="flex items-center gap-3 sm:gap-4 rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-4 sm:p-5 shadow-sm">
                            <div class="flex h-10 w-10 sm:h-12 sm:w-12 shrink-0 items-center justify-center rounded-2xl border-2 border-b-3 border-amber-300 bg-gold-tint text-lg sm:text-xl shadow-2xs">
                                ⚡
                            </div>
                            <div class="min-w-0">
                                <span class="font-display text-xl sm:text-2xl font-black text-slate-900 leading-none block truncate">
                                    {{ number_format($xp) }}
                                </span>
                                <p class="font-display text-[10px] sm:text-xs font-bold uppercase tracking-wider text-muted-ink mt-0.5 truncate">
                                    Total XP
                                </p>
                            </div>
                        </div>

                        {{-- Card 3: Current Tier --}}
                        <div class="flex items-center gap-3 sm:gap-4 rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-4 sm:p-5 shadow-sm">
                            <div class="flex h-10 w-10 sm:h-12 sm:w-12 shrink-0 items-center justify-center rounded-2xl border-2 border-b-3 border-primary/30 bg-primary-tint text-lg sm:text-xl shadow-2xs">
                                🛡️
                            </div>
                            <div class="min-w-0">
                                <span class="font-display text-lg sm:text-2xl font-black text-slate-900 leading-none block truncate">
                                    {{ $tierDisplayName }}
                                </span>
                                <p class="font-display text-[10px] sm:text-xs font-bold uppercase tracking-wider text-muted-ink mt-0.5 truncate">
                                    Current Tier
                                </p>
                            </div>
                        </div>

                        {{-- Card 4: Badges Earned --}}
                        <div class="flex items-center gap-3 sm:gap-4 rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-4 sm:p-5 shadow-sm">
                            <div class="flex h-10 w-10 sm:h-12 sm:w-12 shrink-0 items-center justify-center rounded-2xl border-2 border-b-3 border-indigo-300 bg-indigo-50 text-lg sm:text-xl shadow-2xs">
                                🏆
                            </div>
                            <div class="min-w-0">
                                <span class="font-display text-xl sm:text-2xl font-black text-slate-900 leading-none block truncate">
                                    {{ $badgesCount }}
                                </span>
                                <p class="font-display text-[10px] sm:text-xs font-bold uppercase tracking-wider text-muted-ink mt-0.5 truncate">
                                    Badges Unlocked
                                </p>
                            </div>
                        </div>

                    </div>

                    {{-- Predicted Readiness Card (Mobile View Only) --}}
                    @if ($user->is_diagnostic_completed || $dashPct > 0)
                        <div class="block sm:hidden rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-6 text-center shadow-sm mt-6">
                            <h4 class="font-display text-xs font-extrabold uppercase tracking-wider text-muted-ink">
                                Predicted PhLE Readiness
                            </h4>

                            <div class="mt-4 flex flex-col items-center">
                                <div class="relative mb-4 flex h-32 w-32 items-center justify-center"
                                     x-data="{
                                         target: {{ (float) $dashPct }},
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
                                                 }
                                             };
                                             requestAnimationFrame(animate);
                                         });
                                     ">

                                    <svg class="absolute inset-0 h-full w-full -rotate-90 transform" viewBox="0 0 100 100">
                                        <circle cx="50" cy="50" r="40" fill="none" stroke="#E2E8F0" stroke-width="10" />
                                        <circle x-ref="ring" cx="50" cy="50" r="40" fill="none"
                                            stroke="{{ $readinessStyles['hex'] }}" stroke-width="10"
                                            stroke-linecap="round" stroke-dasharray="{{ $dashCircumference }}"
                                            stroke-dashoffset="{{ round($dashOffset, 2) }}" />
                                    </svg>

                                    <div class="absolute flex flex-col items-center justify-center">
                                        <span class="font-display text-2xl font-extrabold text-slate-900 leading-none">
                                            <span x-text="displayScore">{{ number_format($dashPct, 1) }}</span><span class="text-sm font-bold text-muted-ink">%</span>
                                        </span>
                                    </div>
                                </div>

                                <span class="inline-flex items-center rounded-full border px-3 py-1 font-display text-xs font-bold {{ $readinessStyles['bg'] }} {{ $readinessStyles['text'] }} {{ $readinessStyles['border'] }}">
                                    {{ $readinessStyles['label'] }}
                                </span>
                            </div>

                            <div class="mt-5 border-t-2 border-slate-100 pt-4">
                                <a href="{{ route('readiness.show') }}" class="font-display text-xs font-bold text-primary hover:underline">
                                    Detailed Statistics Breakdown →
                                </a>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- LOGOUT BUTTON PLACED ON THE OVERVIEW TAB --}}
                <div class="pt-6 flex justify-center">
                    <form method="POST" action="{{ route('logout') }}" class="w-full sm:w-auto">
                        @csrf
                        <button type="submit" style="--lip: #E11D48;" class="btn-press w-full sm:w-80 rounded-2xl border-2 border-b-4 border-rose-300 bg-rose-500 py-3.5 text-center font-display text-s font-black tracking-wider text-white shadow-md transition hover:bg-rose-600">
                            Log Out
                        </button>
                    </form>
                </div>

            </div>

            {{-- ========================================================= --}}
            {{-- TAB 2: SETTINGS SECTION (Forms Only)                      --}}
            {{-- ========================================================= --}}
            <div x-cloak x-show="activeTab === 'settings'" 
                 x-transition:enter="transition ease-out duration-200" 
                 x-transition:enter-start="opacity-0 translate-y-2" 
                 x-transition:enter-end="opacity-100 translate-y-0" 
                 class="space-y-6">

                <div class="space-y-4">
                    <h3 class="font-display text-lg font-black text-slate-900 px-1">
                        Settings & Security
                    </h3>

                    {{-- Profile Details Form --}}
                    <div class="rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-6 sm:p-8 shadow-sm">
                        <div class="max-w-xl">
                            @include('profile.partials.update-profile-information-form')
                        </div>
                    </div>

                    {{-- Password / Security Form --}}
                    <div class="rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-6 sm:p-8 shadow-sm">
                        <div class="max-w-xl">
                            @include('profile.partials.update-password-form')
                        </div>
                    </div>

                    {{-- Danger Zone (Delete Account) --}}
                    <div class="rounded-3xl border-2 border-b-4 border-weak/40 bg-white p-6 sm:p-8 shadow-sm">
                        <div class="max-w-xl">
                            @include('profile.partials.delete-user-form')
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</x-app-layout>