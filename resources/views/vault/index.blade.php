<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-display text-xl font-bold text-slate-900 leading-tight">
                    {{ __('Rx Vault') }}
                </h2>
                <p class="text-xs font-semibold uppercase tracking-wider text-muted-ink">
                    Remediation & Spaced Recovery
                </p>
            </div>

            <a href="{{ route('dashboard') }}" 
               class="font-display text-xs font-bold text-muted-ink hover:text-primary transition">
                Back to Dashboard
            </a>
        </div>
    </x-slot>

    @php
        $clearThreshold = \App\Services\RxVaultService::CLEAR_THRESHOLD;
    @endphp

    <div class="pt-6 pb-16 font-sans text-slate-900 antialiased">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 space-y-6">

            {{-- ========================================= --}}
            {{-- HERO SUMMARY CARD                         --}}
            {{-- ========================================= --}}
            <div class="relative overflow-hidden rounded-[1.5rem] border-2 border-b-4 border-[#F5A623]/30 bg-gradient-to-br from-gold-tint via-[#FFFBF2] to-white p-6 sm:p-8 shadow-sm">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-center">
                    
                    {{-- Active Counter --}}
                    <div class="md:col-span-4 flex flex-col items-center justify-center text-center border-b md:border-b-0 md:border-r border-gold/20 pb-6 md:pb-0 md:pr-6">

                        <span class="font-display text-5xl font-extrabold tracking-tight text-slate-900 leading-none">
                            {{ $activeCount }}
                        </span>
                        <span class="font-display text-xs font-bold uppercase tracking-wider text-gold-ink mt-2">
                            {{ \Illuminate\Support\Str::plural('Question', $activeCount) }} to Review
                        </span>
                        
                        <div class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1 font-display text-[11px] font-bold text-muted-ink shadow-xs border border-slate-200">
                            <span class="text-strong font-extrabold">✓</span>
                            <span>{{ $clearedCount }} Cleared to Date</span>
                        </div>
                    </div>

                    {{-- Vault Rules & Guidance --}}
                    <div class="md:col-span-8 space-y-3">
                        <span class="inline-flex items-center rounded-full bg-white px-3 py-1 font-display text-xs font-bold text-gold-ink shadow-xs ring-1 ring-gold/30">
                            Automatic Remediation Queue
                        </span>
                        
                        <h3 class="font-display text-xl font-extrabold text-slate-900">
                            How the Rx Vault Works
                        </h3>

                        <p class="text-xs leading-relaxed text-slate-700">
                            Whenever you miss a question during practice sessions, CitiRx automatically archives it here. To permanently clear an item, you must answer it correctly <strong>{{ $clearThreshold }} consecutive times</strong> during adaptive review.
                        </p>

                        <div class="flex items-center gap-2 rounded-xl border border-gold/30 bg-white/80 p-3 text-[11px] font-medium text-slate-700 shadow-xs">
                            <span><strong>Fair Mastery Guard:</strong> Speed-flagged answers (&lt; minimum reading time) are rejected and will not increment your clear streak.</span>
                        </div>
                    </div>

                </div>
            </div>

            {{-- ========================================= --}}
            {{-- EMPTY STATE                               --}}
            {{-- ========================================= --}}
            @if ($grouped->isEmpty())
                <div class="rounded-2xl border-2 border-b-4 border-slate-200 bg-white p-12 text-center shadow-sm">
                    <div class="flex h-16 w-16 mx-auto items-center justify-center rounded-3xl bg-strong-tint border-2 border-b-4 border-strong/30 text-3xl shadow-sm">
                        ✨
                    </div>

                    <h3 class="mt-4 font-display text-xl font-extrabold text-slate-900">
                        Rx Vault is Empty!
                    </h3>

                    <p class="mt-1.5 text-sm text-muted-ink max-w-md mx-auto">
                        You have zero active missed questions waiting for review. Great work maintaining high accuracy across your practice sessions.
                    </p>

                    <div class="mt-6">
                        <a href="{{ route('practice.intro') }}"
                           style="--lip: #4A2FC4;"
                           class="btn-press inline-flex items-center rounded-2xl bg-primary px-7 py-3 font-display text-sm font-bold text-white shadow-md transition hover:bg-primary/95">
                            Start Practice Session
                        </a>
                    </div>
                </div>
            @else

                {{-- ========================================= --}}
                {{-- GROUPED QUESTIONS BY DOMAIN               --}}
                {{-- ========================================= --}}
                <div class="space-y-6">
                    @foreach ($grouped as $group)
                        <div class="rounded-2xl border-2 border-b-4 border-slate-200 bg-white shadow-sm overflow-hidden">
                            
                            {{-- Group Header --}}
                            <div class="flex items-center justify-between border-b-2 border-slate-100 bg-slate-50/70 px-5 py-3.5 sm:px-6">
                                <div class="flex items-center gap-2.5">
                                    <span class="flex h-2.5 w-2.5 rounded-full bg-gold"></span>
                                    <h3 class="font-display text-sm sm:text-base font-extrabold text-slate-900">
                                        {{ $group['domain_name'] }}
                                    </h3>
                                </div>

                                <span class="rounded-full border border-gold/40 bg-gold-tint px-2.5 py-0.5 font-display text-[11px] font-bold text-gold-ink">
                                    {{ $group['entries']->count() }} {{ \Illuminate\Support\Str::plural('question', $group['entries']->count()) }}
                                </span>
                            </div>

                            {{-- Question Entries --}}
                            <div class="divide-y divide-slate-100">
                                @foreach ($group['entries'] as $entry)
                                    @php
                                        $q = $entry->question;
                                        $correctChoice = $q->correctChoice;
                                        $streak = (int) $entry->consecutive_correct_count;
                                    @endphp

                                    <div class="p-5 sm:p-6 space-y-4">
                                        
                                        {{-- Top: Meta & Streak Pips --}}
                                        <div class="flex flex-wrap items-center justify-between gap-3">
                                            <div class="flex items-center gap-2">
                                                <span class="inline-flex items-center rounded-lg bg-slate-100 px-2.5 py-0.5 font-display text-[10px] font-bold uppercase tracking-wider text-muted-ink">
                                                    Added {{ $entry->added_at->diffForHumans() }}
                                                </span>
                                            </div>

                                            {{-- Consecutive Clear Pips --}}
                                            <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-1.5"
                                                 title="Requires {{ $clearThreshold }} consecutive correct trusted answers to clear">
                                                <span class="font-display text-[11px] font-bold text-muted-ink uppercase tracking-wider">
                                                    Clear Streak:
                                                </span>
                                                <div class="flex items-center gap-1.5">
                                                    @for ($i = 1; $i <= $clearThreshold; $i++)
                                                        @if ($i <= $streak)
                                                            <span class="flex h-4 w-4 items-center justify-center rounded-full bg-strong text-[10px] font-bold text-white shadow-xs">✓</span>
                                                        @else
                                                            <span class="h-4 w-4 rounded-full border-2 border-dashed border-slate-300 bg-white"></span>
                                                        @endif
                                                    @endfor
                                                </div>
                                                <span class="font-display text-xs font-extrabold text-slate-800">
                                                    {{ $streak }}/{{ $clearThreshold }}
                                                </span>
                                            </div>
                                        </div>

                                        {{-- Question Text --}}
                                        <div class="font-sans text-sm sm:text-base font-semibold leading-relaxed text-slate-900">
                                            {{ $q->question_text }}
                                        </div>

                                        {{-- Correct Choice Display --}}
                                        @if ($correctChoice)
                                            <div class="rounded-xl border-2 border-b-4 border-strong/30 bg-strong-tint/60 p-3.5 sm:p-4 text-xs sm:text-sm text-strong-ink">
                                                <div class="flex items-start gap-2.5">
                                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-strong text-xs font-bold text-white">
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
                                            <div class="rounded-xl border border-clinical/20 bg-clinical-tint/40 p-3.5 text-xs text-slate-800 leading-relaxed">
                                                <span class="block font-display text-[10px] font-bold uppercase tracking-wider text-clinical-ink mb-1">
                                                    Clinical Rationale
                                                </span>
                                                {{ $q->hypercorrection_rationale }}
                                            </div>
                                        @endif

                                    </div>
                                @endforeach
                            </div>

                        </div>
                    @endforeach
                </div>

            @endif

            {{-- ========================================= --}}
            {{-- BOTTOM ACTION BUTTONS                     --}}
            {{-- ========================================= --}}
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