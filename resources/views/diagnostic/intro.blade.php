<x-app-layout>
    {{-- Header --}}
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="font-display text-xl font-bold leading-tight text-slate-900">
                    {{ __('Diagnostic Test') }}
                </h1>
                <p class="font-display text-[10px] font-bold uppercase tracking-wider text-muted-ink">
                    PhLE Table of Specifications Baseline
                </p>
            </div>

            <a href="{{ route('dashboard') }}" 
               class="font-display text-xs font-bold text-muted-ink transition hover:text-primary">
                Exit to Dashboard
            </a>
        </div>
    </x-slot>

    {{-- Main --}}
    <div class="py-8 sm:py-10 font-sans antialiased text-slate-900">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('error'))
                {{-- Error Alert --}}
                <div role="alert" class="flex items-center gap-3 p-4 border-2 border-b-4 rounded-2xl border-weak/40 bg-weak-tint text-weak-ink text-sm font-medium shadow-xs animate-pop">
                    <svg class="h-5 w-5 shrink-0 text-weak" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            {{-- Assessment Card --}}
            <div class="rounded-3xl border-2 border-b-4 border-slate-200/90 bg-white p-6 sm:p-8 shadow-xs">
                
                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-clinical-tint px-2.5 py-1 font-display text-[11px] font-bold uppercase tracking-wider text-clinical-ink">
                            <span class="h-1.5 w-1.5 rounded-full bg-clinical"></span>
                            Calibration Engine
                        </span>
                        <span class="font-bold text-slate-300">&bull;</span>
                        <span class="font-display text-xs font-bold uppercase tracking-wider text-muted-ink">
                            One-Time Assessment
                        </span>
                    </div>

                    <h2 class="font-display text-2xl sm:text-3xl font-black tracking-tight text-slate-900">
                        Establish your baseline
                    </h2>

                    <p class="text-sm sm:text-base leading-relaxed text-slate-600 max-w-2xl pt-1">
                        This diagnostic covers all six Pharmacy Licensure Examination (PhLE) Table of Specifications domains. Your results compute initial Bayesian knowledge parameters to dynamically tailor your daily practice.
                    </p>
                </div>

                {{-- Parameter Badges --}}
                <div class="mt-6 sm:mt-8 grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    
                    <div class="flex items-start gap-3 rounded-2xl border-2 border-slate-200/80 bg-slate-50/70 p-4">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-b-2 border-primary/30 bg-primary-tint/60 text-primary font-display font-bold text-sm shadow-xs">
                            60
                        </div>
                        <div>
                            <h3 class="font-display text-xs font-bold uppercase tracking-wider text-slate-900">
                                Domain Coverage
                            </h3>
                            <p class="mt-0.5 text-xs text-slate-600 leading-snug">
                                Up to 10 questions per domain (60 total items when complete).
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3 rounded-2xl border-2 border-slate-200/80 bg-slate-50/70 p-4">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-b-2 border-teal-200 bg-teal-50 text-teal-600 shadow-xs">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-display text-xs font-bold uppercase tracking-wider text-slate-900">
                                Unrestricted Pacing
                            </h3>
                            <p class="mt-0.5 text-xs text-slate-600 leading-snug">
                                No countdown clock &mdash; prioritize clinical accuracy and reasoning.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3 rounded-2xl border-2 border-slate-200/80 bg-slate-50/70 p-4">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-b-2 border-amber-200 bg-amber-50 text-amber-600 shadow-xs">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-display text-xs font-bold uppercase tracking-wider text-slate-900">
                                Session Continuity
                            </h3>
                            <p class="mt-0.5 text-xs text-slate-600 leading-snug">
                                Resume any time. Responses commit to telemetry when submitted.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3 rounded-2xl border-2 border-slate-200/80 bg-slate-50/70 p-4">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-b-2 border-indigo-200 bg-indigo-50 text-indigo-600 shadow-xs">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-display text-xs font-bold uppercase tracking-wider text-slate-900">
                                Singular Baseline
                            </h3>
                            <p class="mt-0.5 text-xs text-slate-600 leading-snug">
                                Completed only once to anchor your personal progress curve.
                            </p>
                        </div>
                    </div>

                </div>

                {{-- Action Form --}}
                <div class="mt-8 pt-6 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <form method="POST" action="{{ route('diagnostic.start') }}" class="w-full sm:w-auto">
                        @csrf
                        <button type="submit"
                                style="--lip: #4A2FC4;"
                                class="btn-press w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-2xl bg-primary px-8 py-3.5 font-display text-sm font-bold text-white shadow-md transition hover:bg-primary/95">
                            <span>Begin Diagnostic Assessment</span>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                            </svg>
                        </button>
                    </form>

                    <span class="text-center sm:text-right font-mono text-[11px] text-muted-ink">
                        Estimated: 35–45 mins
                    </span>
                </div>

            </div>

        </div>
    </div>
</x-app-layout>