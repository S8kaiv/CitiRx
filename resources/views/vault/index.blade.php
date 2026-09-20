<x-app-layout>
    {{-- Header --}}
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="font-display text-xl font-black text-slate-900 leading-tight">
                    {{ __('Rx Vault') }}
                </h1>
                <p class="hidden sm:block text-xs font-semibold uppercase tracking-wider text-muted-ink">
                    Remediation & Spaced Recovery Hub
                </p>
            </div>

        </div>
    </x-slot>

    @php
        $clearThreshold = \App\Services\RxVaultService::CLEAR_THRESHOLD ?? 3;
    @endphp

    {{-- Main Container --}}
    <div class="py-6 sm:py-8 font-sans text-slate-900 antialiased"
         x-data="{ activeTab: @js($activeTab ?? 'mistakes') }">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 space-y-6">

            {{-- Status & Error Alerts --}}
            @if (session('status'))
                <div role="status" class="flex items-center gap-2.5 p-3.5 border-2 border-b-4 rounded-2xl border-primary/30 bg-primary-tint/60 font-display text-xs font-bold text-primary shadow-xs animate-pop">
                    <span class="text-base">ℹ️</span>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div role="alert" class="p-3.5 text-xs font-bold border-2 border-b-4 rounded-2xl border-weak/40 bg-weak-tint text-weak-ink shadow-xs animate-pop">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Hero Summary Card --}}
            <div class="rounded-3xl border-2 border-b-4 border-slate-200/90 bg-white p-6 sm:p-8 shadow-xs">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-center">
                    
                    {{-- Telemetry Counters --}}
                    <div class="md:col-span-5 grid grid-cols-2 gap-3 border-b md:border-b-0 md:border-r border-slate-100 pb-6 md:pb-0 md:pr-6">
                        
                        {{-- Mistakes Stat --}}
                        <div class="flex flex-col items-center justify-center p-4 rounded-2xl border-2 border-b-4 border-rose-200 bg-rose-50/60 text-center">
                            <span class="font-display text-3xl sm:text-4xl font-black text-rose-600 leading-none">
                                {{ $activeCount }}
                            </span>
                            <span class="font-display text-[10px] font-bold uppercase tracking-wider text-rose-800 mt-1.5">
                                Active Mistakes
                            </span>
                            <span class="mt-2 inline-flex items-center rounded-full bg-white px-2 py-0.5 font-mono text-[9px] font-bold text-slate-600 border border-slate-200 shadow-2xs">
                                {{ $clearedCount }} Cleared
                            </span>
                        </div>

                        {{-- Bookmarks Stat --}}
                        <div class="flex flex-col items-center justify-center p-4 rounded-2xl border-2 border-b-4 border-amber-200 bg-amber-50/60 text-center">
                            <span class="font-display text-3xl sm:text-4xl font-black text-amber-600 leading-none">
                                {{ $bookmarksCount }}
                            </span>
                            <span class="font-display text-[10px] font-bold uppercase tracking-wider text-amber-800 mt-1.5">
                                Bookmarked Questions
                            </span>
                            <span class="mt-2 inline-flex items-center rounded-full bg-white px-2 py-0.5 font-mono text-[9px] font-bold text-slate-600 border border-slate-200 shadow-2xs">
                                Starred
                            </span>
                        </div>

                    </div>

                    {{-- Vault Rules & Remediation Actions --}}
                    <div class="md:col-span-7 space-y-4">
                        <div>
                            <span class="inline-flex items-center gap-1.5 rounded-lg bg-clinical-tint px-2.5 py-1 font-display text-[11px] font-bold uppercase tracking-wider text-clinical-ink">
                                <span class="h-1.5 w-1.5 rounded-full bg-clinical"></span>
                                Clinical Spaced Recovery
                            </span>
                            <h2 class="mt-1.5 font-display text-xl font-black text-slate-900 tracking-tight">
                                Targeted Remediation Engine
                            </h2>
                            <p class="mt-1 text-xs sm:text-sm text-slate-600 leading-relaxed">
                                Missed questions require <strong>{{ $clearThreshold }} consecutive correct responses</strong> in practice sessions to be permanently cleared from this locker.
                            </p>
                        </div>

                        {{-- Drill Action Buttons (Two Clean, Side-by-Side 2.5D Actions) --}}
                        <div class="flex flex-wrap gap-2.5 pt-1">
                            @if (\Illuminate\Support\Facades\Route::has('vault.drill.mistakes'))
                                <form method="POST" action="{{ route('vault.drill.mistakes') }}">
                                    @csrf
                                    <button type="submit"
                                            @disabled($activeCount === 0)
                                            style="--lip: #E11D48;"
                                            class="btn-press inline-flex items-center gap-2 rounded-xl bg-rose-500 px-4 py-2.5 font-display text-xs font-black uppercase tracking-wider text-white shadow-xs transition hover:bg-rose-600 disabled:opacity-40 disabled:cursor-not-allowed">
                                        <span>Drill Active Mistakes ({{ $activeCount }})</span>
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                        </svg>
                                    </button>
                                </form>
                            @endif

                            @if (\Illuminate\Support\Facades\Route::has('vault.drill.bookmarks'))
                                <form method="POST" action="{{ route('vault.drill.bookmarks') }}">
                                    @csrf
                                    <button type="submit"
                                            @disabled($bookmarksCount === 0)
                                            style="--lip: #B45309;"
                                            class="btn-press inline-flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2.5 font-display text-xs font-black uppercase tracking-wider text-white shadow-xs transition hover:bg-amber-600 disabled:opacity-40 disabled:cursor-not-allowed">
                                        <span>Drill Bookmarks ({{ $bookmarksCount }})</span>
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                        </svg>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                </div>
            </div>

            {{-- 2.5D Tactile Sub-Vault Navigation Tabs --}}
            <div class="flex items-center gap-3">
                <button type="button"
                        @click="activeTab = 'mistakes'"
                        class="btn-press inline-flex items-center gap-2 rounded-2xl border-2 border-b-4 px-4 sm:px-5 py-2.5 font-display text-xs font-black uppercase tracking-wider transition-all"
                        :class="activeTab === 'mistakes'
                            ? 'border-primary-lip bg-primary text-white shadow-xs'
                            : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'">
                    
                    {{-- Responsive label: "Mistakes" on mobile, "Mistake Locker" on web --}}
                    <span>
                        <span class="inline sm:hidden">Mistakes</span>
                        <span class="hidden sm:inline">Mistake Locker</span>
                    </span>

                    <span class="rounded-full px-2 py-0.5 text-[10px]"
                          :class="activeTab === 'mistakes' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700'">
                        {{ $activeCount }}
                    </span>
                </button>

                <button type="button"
                        @click="activeTab = 'bookmarks'"
                        class="btn-press inline-flex items-center gap-2 rounded-2xl border-2 border-b-4 px-4 sm:px-5 py-2.5 font-display text-xs font-black uppercase tracking-wider transition-all"
                        :class="activeTab === 'bookmarks'
                            ? 'border-amber-700 bg-amber-500 text-white shadow-xs'
                            : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'">
                    <span>Bookmarks</span>
                    <span class="rounded-full px-2 py-0.5 text-[10px]"
                          :class="activeTab === 'bookmarks' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700'">
                        {{ $bookmarksCount }}
                    </span>
                </button>
            </div>

            {{-- ========================================================= --}}
            {{-- TAB 1: MISTAKE LOCKER                                     --}}
            {{-- ========================================================= --}}
            <div x-show="activeTab === 'mistakes'" x-cloak class="space-y-6">
                @if ($grouped->isEmpty())
                    <div class="rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-10 text-center shadow-xs">
                        <div class="flex h-14 w-14 mx-auto items-center justify-center rounded-2xl bg-emerald-50 border-2 border-b-4 border-emerald-200 text-2xl text-emerald-600 shadow-xs">
                            ✓
                        </div>
                        <h3 class="mt-3.5 font-display text-lg font-black text-slate-900">
                            Mistake Locker is Clear!
                        </h3>
                        <p class="mt-1 text-xs text-muted-ink max-w-sm mx-auto">
                            You have zero active missed questions waiting for review. Great work maintaining accuracy across your sessions.
                        </p>
                        <div class="mt-5">
                            <a href="{{ route('practice.intro') }}"
                               style="--lip: #4A2FC4;"
                               class="btn-press inline-flex items-center rounded-2xl bg-primary px-6 py-2.5 font-display text-xs font-bold text-white shadow-xs">
                                Start Practice Session
                            </a>
                        </div>
                    </div>
                @else
                    @foreach ($grouped as $group)
                        <div class="space-y-3">
                            {{-- Domain Group Header --}}
                            <div class="flex items-center justify-between px-2">
                                <div class="flex items-center gap-2">
                                    <span class="h-2.5 w-2.5 rounded-full bg-rose-500"></span>
                                    <h3 class="font-display text-base font-black text-slate-900">
                                        {{ $group['domain_name'] }}
                                    </h3>
                                </div>
                                <span class="rounded-lg border border-slate-200 bg-white px-2.5 py-0.5 font-display text-[10px] font-bold text-slate-700 shadow-2xs">
                                    {{ $group['entries']->count() }} {{ \Illuminate\Support\Str::plural('item', $group['entries']->count()) }}
                                </span>
                            </div>

                            {{-- Question Entries 2-Column Grid --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @foreach ($group['entries'] as $entry)
                                    @php
                                        $q = $entry->question;
                                        $correctChoice = $q->correctChoice;
                                        $streak = (int) $entry->consecutive_correct_count;
                                    @endphp
                                    <div class="rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-5 space-y-3 shadow-xs flex flex-col justify-between">
                                        <div class="space-y-3">
                                            {{-- Header: Timing & Streak Pips --}}
                                            <div class="flex flex-wrap items-center justify-between gap-2">
                                                <span class="font-mono text-[10px] text-muted-ink">
                                                    Added {{ $entry->added_at ? $entry->added_at->diffForHumans() : 'recently' }}
                                                </span>

                                                {{-- Clear Streak Progression Pips --}}
                                                <div class="flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 px-2 py-1"
                                                     title="Requires {{ $clearThreshold }} consecutive correct trusted answers to clear">
                                                    <span class="font-display text-[10px] font-bold uppercase tracking-wider text-muted-ink">
                                                        Streak:
                                                    </span>
                                                    <div class="flex items-center gap-1">
                                                        @for ($i = 1; $i <= $clearThreshold; $i++)
                                                            @if ($i <= $streak)
                                                                <span class="flex h-3.5 w-3.5 items-center justify-center rounded-full bg-emerald-500 text-[8px] font-bold text-white shadow-2xs">✓</span>
                                                            @else
                                                                <span class="h-3.5 w-3.5 rounded-full border border-dashed border-slate-300 bg-white"></span>
                                                            @endif
                                                        @endfor
                                                    </div>
                                                    <span class="font-display text-[11px] font-black text-slate-800">
                                                        {{ $streak }}/{{ $clearThreshold }}
                                                    </span>
                                                </div>
                                            </div>

                                            {{-- Question Stem --}}
                                            <div class="font-sans text-sm font-semibold leading-relaxed text-slate-900">
                                                {{ $q->question_text }}
                                            </div>

                                            {{-- Correct Choice Display --}}
                                            @if ($correctChoice)
                                                <div class="rounded-xl border-2 border-b-4 border-emerald-500/30 bg-emerald-50/60 p-3 text-xs text-emerald-950">
                                                    <div class="flex items-start gap-2">
                                                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md bg-emerald-600 font-display text-[11px] font-bold text-white">
                                                            {{ $correctChoice->choice_letter }}
                                                        </span>
                                                        <div class="leading-relaxed">
                                                            <span class="font-bold">Correct Solution:</span>
                                                            <span>{{ $correctChoice->choice_text }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif

                                            {{-- Clinical Rationale --}}
                                            @if ($q->hypercorrection_rationale)
                                                <div class="rounded-xl border border-clinical/20 bg-clinical-tint/40 p-2.5 text-xs text-slate-800 leading-relaxed">
                                                    <span class="block font-display text-[10px] font-bold uppercase tracking-wider text-clinical-ink mb-0.5">
                                                        Clinical Rationale
                                                    </span>
                                                    {{ $q->hypercorrection_rationale }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            {{-- ========================================================= --}}
            {{-- TAB 2: BOOKMARKED STEMS                                   --}}
            {{-- ========================================================= --}}
            <div x-show="activeTab === 'bookmarks'" x-cloak class="space-y-6">
                @if ($groupedBookmarks->isEmpty())
                    <div class="rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-10 text-center shadow-xs">
                        <div class="flex h-14 w-14 mx-auto items-center justify-center rounded-2xl bg-amber-50 border-2 border-b-4 border-amber-200 text-2xl text-amber-500 shadow-xs">
                            ☆
                        </div>
                        <h3 class="mt-3.5 font-display text-lg font-black text-slate-900">
                            No Bookmarked Stems
                        </h3>
                        <p class="mt-1 text-xs text-muted-ink max-w-sm mx-auto">
                            Tap the star icon during practice or diagnostic sessions to store high-yield questions here for quick revision.
                        </p>
                    </div>
                @else
                    @foreach ($groupedBookmarks as $group)
                        <div class="space-y-3">
                            {{-- Domain Group Header --}}
                            <div class="flex items-center justify-between px-2">
                                <div class="flex items-center gap-2">
                                    <span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                                    <h3 class="font-display text-base font-black text-slate-900">
                                        {{ $group['domain_name'] }}
                                    </h3>
                                </div>
                                <span class="rounded-lg border border-slate-200 bg-white px-2.5 py-0.5 font-display text-[10px] font-bold text-slate-700 shadow-2xs">
                                    {{ $group['entries']->count() }} {{ \Illuminate\Support\Str::plural('bookmark', $group['entries']->count()) }}
                                </span>
                            </div>

                            {{-- Bookmarked Entries 2-Column Grid --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @foreach ($group['entries'] as $bookmark)
                                    @php
                                        $q = $bookmark->question;
                                        $correctChoice = $q->correctChoice;
                                    @endphp
                                    <div class="flex flex-col justify-between space-y-3 rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-5 shadow-xs">
                                        <div class="space-y-3">

                                            {{-- Header and Unbookmark Button --}}
                                            <div class="flex items-center justify-between">
                                                <span class="inline-flex items-center rounded-md bg-clinical-tint px-2 py-0.5 font-display text-[10px] font-bold text-clinical-ink">
                                                    {{ $q->competency->title ?? 'Core Competency' }}
                                                </span>

                                                <form method="POST"
                                                      action="{{ route('bookmarks.destroy', ['bookmark' => $bookmark->bookmark_id]) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            title="Remove Bookmark"
                                                            aria-label="Remove bookmark"
                                                            class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 bg-white text-amber-500 shadow-2xs transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-500">
                                                        <span class="text-xs">★</span>
                                                    </button>
                                                </form>
                                            </div>

                                            {{-- Question Text --}}
                                            <div class="font-sans text-sm font-semibold leading-relaxed text-slate-900">
                                                {{ $q->question_text }}
                                            </div>

                                            {{-- Correct Solution --}}
                                            @if ($correctChoice)
                                                <div class="rounded-xl border-2 border-b-4 border-slate-200 bg-slate-50 p-3 text-xs text-slate-800">
                                                    <div class="flex items-start gap-2">
                                                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md bg-primary font-display text-[11px] font-bold text-white">
                                                            {{ $correctChoice->choice_letter }}
                                                        </span>
                                                        <div class="leading-relaxed">
                                                            <span class="font-bold">Correct Solution:</span>
                                                            <span>{{ $correctChoice->choice_text }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif

                                            {{-- Clinical Rationale --}}
                                            @if ($q->hypercorrection_rationale)
                                                <div class="rounded-xl border border-clinical/20 bg-clinical-tint/40 p-2.5 text-xs leading-relaxed text-slate-800">
                                                    <span class="mb-0.5 block font-display text-[10px] font-bold uppercase tracking-wider text-clinical-ink">
                                                        Clinical Rationale
                                                    </span>
                                                    {{ $q->hypercorrection_rationale }}
                                                </div>
                                            @endif

                                            {{-- Personal Notes Form --}}
                                            <form method="POST"
                                                  action="{{ route('bookmarks.updateNotes', ['bookmark' => $bookmark->bookmark_id]) }}"
                                                  class="mt-3 space-y-2 border-t border-slate-100 pt-3">
                                                @csrf
                                                @method('PATCH')

                                                <label for="notes-{{ $bookmark->bookmark_id }}"
                                                       class="block font-display text-[10px] font-bold uppercase tracking-wider text-muted-ink">
                                                    Personal Notes
                                                </label>

                                                <textarea id="notes-{{ $bookmark->bookmark_id }}" 
                                                          name="personal_notes" 
                                                          rows="2" 
                                                          maxlength="2000"
                                                          placeholder="Add a memory aid or review note..."
                                                          class="w-full rounded-xl border-slate-200 text-xs shadow-xs focus:border-primary focus:ring-primary">{{ old('personal_notes', $bookmark->personal_notes) }}</textarea>

                                                <div class="flex justify-end">
                                                    <button type="submit"
                                                            class="rounded-xl border-2 border-slate-200 bg-white px-3 py-1.5 font-display text-[11px] font-bold text-slate-700 transition hover:bg-slate-50">
                                                        Save Notes
                                                    </button>
                                                </div>
                                            </form>

                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            {{-- Bottom Navigation Actions --}}
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-4">
                <a href="{{ route('practice.intro') }}"
                   style="--lip: #4A2FC4;"
                   class="btn-press w-full sm:w-auto rounded-2xl bg-primary px-8 py-3 text-center font-display text-xs font-black uppercase tracking-wider text-white shadow-sm transition hover:bg-primary/95">
                    Start Adaptive Practice
                </a>

                <a href="{{ route('dashboard') }}"
                   class="w-full sm:w-auto rounded-2xl border-2 border-slate-200 bg-white px-8 py-3 text-center font-display text-xs font-bold text-slate-700 transition hover:bg-slate-50">
                    Back to Dashboard
                </a>
            </div>

        </div>
    </div>
</x-app-layout>