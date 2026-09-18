<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-display text-xl font-bold text-slate-900 leading-tight">
                {{ __('Practice Session') }}
            </h2>

            <a href="{{ route('dashboard') }}" 
               class="font-display text-xs font-bold text-muted-ink hover:text-primary transition">
                Exit to Dashboard
            </a>
        </div>
    </x-slot>

    @php
        $displayQuestionNumber = $feedback
            ? min($session->total_items, $session->target_length)
            : min($session->total_items + 1, $session->target_length);

        $progress = $session->target_length > 0 
            ? min(100, ($session->total_items / $session->target_length) * 100) 
            : 0;
    @endphp

    <div class="pt-8 pb-16 font-sans text-slate-900 antialiased">
        <div class="max-w-2xl mx-auto px-4 sm:px-6">

            {{-- Status message --}}
            @if (session('status'))
                <div role="status" class="mb-5 flex items-center gap-3 rounded-2xl border-2 border-b-4 border-primary/30 bg-primary-tint/60 p-4 font-display text-xs font-bold text-primary shadow-sm">
                    <span class="text-base">ℹ️</span>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            {{-- Error message --}}
            @if (session('error'))
                <div role="alert" class="mb-5 rounded-2xl border-2 border-b-4 border-weak/40 bg-weak-tint p-4 text-sm font-medium text-weak-ink">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Progress Header & Bar --}}
            <div class="mb-6 space-y-2">
                <div class="flex items-center justify-between font-display text-xs font-bold uppercase tracking-wider">
                    <span class="text-slate-700">
                        Question {{ $displayQuestionNumber }} of {{ $session->target_length }}
                    </span>

                    <span class="inline-flex items-center gap-1.5 rounded-full bg-strong-tint px-2.5 py-0.5 text-strong-ink">
                        <span class="h-2 w-2 rounded-full bg-strong"></span>
                        {{ $session->correct_items }} Correct
                    </span>
                </div>

                <div class="h-3.5 w-full overflow-hidden rounded-full bg-slate-100 p-0.5 ring-1 ring-inset ring-slate-200/60"
                     role="progressbar"
                     aria-label="Practice progress"
                     aria-valuemin="0"
                     aria-valuemax="100"
                     aria-valuenow="{{ round($progress) }}">
                    <div class="h-full rounded-full bg-primary transition-all duration-300"
                         style="width: {{ $progress }}%"></div>
                </div>
            </div>

            @if ($feedback)

                {{-- ============================================= --}}
                {{-- FEEDBACK VIEW                                 --}}
                {{-- ============================================= --}}

                @php
                    $isCorrect = (bool) $feedback['is_correct'];

                    $correctChoice = $answeredQuestion->choices->firstWhere(
                        'choice_id',
                        $feedback['correct_choice_id']
                    ) ?? $answeredQuestion->choices->firstWhere('is_correct', true);
                @endphp

                <div class="relative overflow-hidden rounded-2xl border-2 border-b-4 border-slate-200 bg-white p-6 shadow-sm sm:p-8 space-y-6">
                    
                    {{-- Feedback Bookmark / Vault Button (Teammate feature styled) --}}
                    <form method="POST"
                          action="{{ $isBookmarked
                              ? route('bookmarks.destroyQuestion', ['question' => $answeredQuestion->question_id])
                              : route('bookmarks.store', ['question' => $answeredQuestion->question_id]) }}"
                          class="absolute right-6 top-6 z-10">
                        @csrf
                        @method($isBookmarked ? 'DELETE' : 'PUT')
                        <input type="hidden" name="practice_session_id" value="{{ $session->session_id }}">

                        <button type="submit"
                                aria-label="{{ $isBookmarked ? 'Remove bookmark' : 'Bookmark this question' }}"
                                title="{{ $isBookmarked ? 'Remove bookmark' : 'Bookmark this question' }}"
                                class="flex h-9 w-9 items-center justify-center rounded-xl border-2 transition {{ $isBookmarked ? 'border-[#F5A623]/40 bg-gold-tint text-gold shadow-sm' : 'border-slate-200 bg-slate-50 text-slate-400 hover:text-gold hover:border-[#F5A623]/40' }}">
                            <span class="text-xl leading-none">{{ $isBookmarked ? '★' : '☆' }}</span>
                        </button>
                    </form>

                    {{-- Domain Metadata --}}
                    <div class="flex flex-wrap items-center gap-2 pr-12">
                        <span class="inline-flex items-center rounded-lg bg-clinical-tint px-2.5 py-1 font-display text-xs font-bold text-clinical-ink">
                            {{ $answeredQuestion->competency->domain->domain_name }}
                        </span>
                        <span class="text-slate-300 font-bold">•</span>
                        <span class="text-xs font-semibold text-muted-ink">
                            {{ $answeredQuestion->competency->title }}
                        </span>
                    </div>

                    {{-- Question Text --}}
                    <div class="font-sans text-base font-semibold leading-relaxed text-slate-900 sm:text-lg">
                        {{ $answeredQuestion->question_text }}
                    </div>

                    {{-- Outcome Banner --}}
                    @if ($isCorrect)
                        <div class="rounded-2xl border-2 border-b-4 border-strong/40 bg-strong-tint p-4 sm:p-5">
                            <div class="flex items-center gap-2 font-display text-base font-bold text-strong-ink sm:text-lg">
                                <svg class="h-5 w-5 text-strong shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
                                </svg>
                                <span>Correct Answer</span>
                            </div>

                            @if ($correctChoice)
                                <p class="mt-2 text-sm text-strong-ink">
                                    <strong>{{ $correctChoice->choice_letter }}.</strong> {{ $correctChoice->choice_text }}
                                </p>
                            @endif
                        </div>
                    @else
                        <div class="rounded-2xl border-2 border-b-4 border-weak/40 bg-weak-tint p-4 sm:p-5">
                            <div class="flex items-center gap-2 font-display text-base font-bold text-weak-ink sm:text-lg">
                                <svg class="h-5 w-5 text-weak shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                                </svg>
                                <span>Incorrect</span>
                            </div>

                            @if ($correctChoice)
                                <p class="mt-2 text-sm text-weak-ink">
                                    Correct answer: <strong>{{ $correctChoice->choice_letter }}.</strong> {{ $correctChoice->choice_text }}
                                </p>
                            @endif
                        </div>
                    @endif

                    {{-- Clinical Rationale --}}
                    @if ($answeredQuestion->hypercorrection_rationale)
                        <div class="rounded-2xl border border-clinical/20 bg-clinical-tint/40 p-4 text-sm leading-relaxed text-slate-800">
                            <span class="block font-display text-xs font-bold uppercase tracking-wider text-clinical-ink mb-1">
                                Clinical Rationale
                            </span>
                            {{ $answeredQuestion->hypercorrection_rationale }}
                        </div>
                    @endif

                    {{-- BKT & XP Telemetry Strip --}}
                    <div class="grid grid-cols-3 gap-3 pt-2">
                        <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-3 text-center">
                            <span class="block text-[11px] font-bold uppercase tracking-wider text-muted-ink">XP Earned</span>
                            <span class="font-display text-lg font-bold text-gold-ink">+{{ $feedback['answer_xp'] }}</span>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-3 text-center">
                            <span class="block text-[11px] font-bold uppercase tracking-wider text-muted-ink">Prior Mastery</span>
                            <span class="font-display text-lg font-bold text-slate-800">{{ number_format($feedback['prior_mastery'] * 100, 1) }}%</span>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-3 text-center">
                            <span class="block text-[11px] font-bold uppercase tracking-wider text-muted-ink">Updated Mastery</span>
                            <span class="font-display text-lg font-bold text-clinical-ink">{{ number_format($feedback['posterior_mastery'] * 100, 1) }}%</span>
                        </div>
                    </div>

                    {{-- Speed Guessing Warning Flag --}}
                    @if ($feedback['is_speed_flagged'])
                        <div class="rounded-xl border border-gold/40 bg-gold-tint p-3.5 text-xs text-gold-ink leading-relaxed">
                            <strong>Speed-Flagged:</strong> Response submitted in {{ number_format($feedback['response_seconds'], 2) }}s (minimum threshold: {{ number_format($feedback['minimum_seconds'], 2) }}s). No XP was awarded and this item is excluded from streak progression.
                        </div>
                    @endif

                    {{-- Next Action --}}
                    <form method="POST"
                          action="{{ route('practice.next', ['session' => $session->session_id]) }}"
                          class="pt-2">
                        @csrf
                        <button type="submit"
                                style="--lip: #4A2FC4;"
                                class="btn-press w-full rounded-2xl bg-primary py-3.5 font-display text-base font-bold text-white shadow-md transition hover:bg-primary/95">
                            Continue
                        </button>
                    </form>

                </div>

            @else

                {{-- ============================================= --}}
                {{-- QUESTION VIEW                                 --}}
                {{-- ============================================= --}}

                @if (!$question)
                    <div class="rounded-2xl border-2 border-b-4 border-slate-200 bg-white p-8 text-center shadow-sm">
                        <p class="text-slate-600 font-medium">
                            No Practice question is available right now.
                        </p>
                        <div class="mt-5">
                            <a href="{{ route('practice.intro') }}"
                               style="--lip: #4A2FC4;"
                               class="btn-press inline-flex items-center rounded-xl bg-primary px-6 py-2.5 font-display text-sm font-bold text-white">
                                Back to Practice Setup
                            </a>
                        </div>
                    </div>
                @else
                    <div class="relative overflow-hidden rounded-2xl border-2 border-b-4 border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                        
                        {{-- Question Bookmark / Vault Button (Teammate feature styled) --}}
                        <form method="POST"
                              action="{{ $isBookmarked
                                  ? route('bookmarks.destroyQuestion', ['question' => $question->question_id])
                                  : route('bookmarks.store', ['question' => $question->question_id]) }}"
                              class="absolute right-6 top-6 z-10">
                            @csrf
                            @method($isBookmarked ? 'DELETE' : 'PUT')
                            <input type="hidden" name="practice_session_id" value="{{ $session->session_id }}">

                            <button type="submit"
                                    aria-label="{{ $isBookmarked ? 'Remove bookmark' : 'Bookmark this question' }}"
                                    title="{{ $isBookmarked ? 'Remove bookmark' : 'Bookmark this question' }}"
                                    class="flex h-9 w-9 items-center justify-center rounded-xl border-2 transition {{ $isBookmarked ? 'border-[#F5A623]/40 bg-gold-tint text-gold shadow-sm' : 'border-slate-200 bg-slate-50 text-slate-400 hover:text-gold hover:border-[#F5A623]/40' }}">
                                <span class="text-xl leading-none">{{ $isBookmarked ? '★' : '☆' }}</span>
                            </button>
                        </form>

                        {{-- Domain Metadata --}}
                        <div class="flex flex-wrap items-center gap-2 pr-12">
                            <span class="inline-flex items-center rounded-lg bg-clinical-tint px-2.5 py-1 font-display text-xs font-bold text-clinical-ink">
                                {{ $question->competency->domain->domain_name }}
                            </span>
                            <span class="text-slate-300 font-bold">•</span>
                            <span class="text-xs font-semibold text-muted-ink">
                                {{ $question->competency->title }}
                            </span>
                        </div>

                        {{-- Question Stem --}}
                        <div class="mt-4 font-sans text-base font-semibold leading-relaxed text-slate-900 sm:text-lg">
                            {{ $question->question_text }}
                        </div>

                        {{-- Answer Options Form (3D Selectable Cards) --}}
                        <form method="POST"
                              action="{{ route('practice.answer', ['session' => $session->session_id]) }}"
                              class="mt-6 space-y-3">
                            @csrf
                            <input type="hidden" name="question_id" value="{{ $question->question_id }}">

                            @foreach ($question->choices as $choice)
                                <label class="group relative block cursor-pointer">
                                    <input type="radio" 
                                           name="selected_choice_id" 
                                           value="{{ $choice->choice_id }}" 
                                           @checked(old('selected_choice_id') === $choice->choice_id)
                                           class="peer sr-only" 
                                           required>

                                    <div class="flex items-center gap-3.5 rounded-2xl border-2 border-b-4 border-slate-200 bg-white p-3.5 sm:p-4 transition-all hover:border-slate-300 hover:bg-slate-50/80 peer-checked:border-primary peer-checked:border-b-primary-lip peer-checked:bg-primary-tint/30">
                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border-2 border-slate-200 font-display text-sm font-bold text-slate-600 transition group-hover:border-slate-300 peer-checked:border-primary peer-checked:bg-primary peer-checked:text-white">
                                            {{ $choice->choice_letter }}
                                        </span>

                                        <span class="font-sans text-sm font-medium leading-relaxed text-slate-800">
                                            {{ $choice->choice_text }}
                                        </span>
                                    </div>
                                </label>
                            @endforeach

                            @error('selected_choice_id')
                                <p class="mt-2 text-xs font-semibold text-weak-ink">
                                    {{ $message }}
                                </p>
                            @enderror

                            <div class="pt-4">
                                <button type="submit"
                                        style="--lip: #4A2FC4;"
                                        class="btn-press w-full rounded-2xl bg-primary py-3.5 font-display text-base font-bold text-white shadow-md transition hover:bg-primary/95">
                                    Submit Answer
                                </button>
                            </div>
                        </form>

                    </div>
                @endif

            @endif

            <div class="mt-6 text-center font-mono text-[11px] text-muted-ink">
                Session Ref: {{ $session->session_id }}
            </div>

        </div>
    </div>
</x-app-layout>