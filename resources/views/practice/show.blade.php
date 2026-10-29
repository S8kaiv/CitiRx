<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            @php
                $practiceTitle = match ($session->practice_mode ?? 'adaptive') {
                    'mistakes' => 'Mistake Drill',
                    'bookmarks' => 'Bookmark Drill',
                    default => 'Practice Session',
                };
            @endphp
            <h1 class="font-display text-xl font-black leading-tight text-ink">
                {{ $practiceTitle }}
            </h1>

            <a href="{{ route('dashboard') }}"
                class="font-display text-xs font-bold text-ink-muted transition hover:text-primary">
                Exit to Dashboard
            </a>
        </div>
    </x-slot>

    @php
        $displayQuestionNumber = $feedback
            ? min($session->total_items, $session->target_length)
            : min($session->total_items + 1, $session->target_length);

        $progress = $session->target_length > 0 ? min(100, ($session->total_items / $session->target_length) * 100) : 0;
    @endphp

    {{-- Compact Outer Container (py-3 sm:py-5) --}}
    <div class="ui-assessment-stage py-3 sm:py-5 font-sans antialiased text-ink">
        <div class="max-w-2xl px-4 mx-auto sm:px-6">
            {{-- Level Up Notification Banner --}}
            <x-level-up-alert />

            {{-- Status & Error Messages --}}
            @if (session('status'))
                <div role="status"
                    class="flex items-center gap-2.5 p-3 mb-3 border-2 border-b-4 shadow-sm rounded-xl border-primary/30 bg-primary-tint/60 font-display text-xs font-bold text-primary animate-pop">
                    <span class="text-base">ℹ️</span>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div role="alert"
                    class="p-3 mb-3 text-xs font-medium border-2 border-b-4 rounded-xl border-weak/40 bg-weak-tint text-weak-ink animate-pop">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Progress Header & Bar (Tightened vertical height) --}}
            <div class="mb-3.5 space-y-1.5">
                <div class="flex items-center justify-between font-display text-xs font-bold uppercase tracking-wider">
                    <span class="text-ink">
                        Question {{ $displayQuestionNumber }} of {{ $session->target_length }}
                    </span>

                    <span
                        class="inline-flex items-center gap-1.5 rounded-full bg-strong-tint px-2 py-0.5 text-xs text-strong-ink shadow-xs">
                        <span class="h-1.5 w-1.5 rounded-full bg-strong animate-pulse"></span>
                        {{ $session->correct_items }} Correct
                    </span>
                </div>

                <div class="h-2.5 w-full overflow-hidden rounded-full bg-surface-2 p-0.5 ring-1 ring-inset ring-slate-200/60"
                    role="progressbar" aria-label="Practice progress" aria-valuemin="0" aria-valuemax="100"
                    aria-valuenow="{{ round($progress) }}">
                    <div class="h-full rounded-full bg-primary transition-all duration-700 ease-out"
                        style="width: {{ $progress }}%"></div>
                </div>
            </div>

            @if ($feedback)

                {{-- ============================================= --}}
                {{-- FEEDBACK VIEW (Compact Height)                --}}
                {{-- ============================================= --}}

                @php
                    $isCorrect = (bool) $feedback['is_correct'];

                    $correctChoice =
                        $answeredQuestion->choices->firstWhere('choice_id', $feedback['correct_choice_id']) ??
                        $answeredQuestion->choices->firstWhere('is_correct', true);
                @endphp

                <div
                    class="relative p-4 sm:p-6 bg-surface border-2 border-b-4 shadow-sm overflow-hidden rounded-2xl border-line space-y-4 animate-pop">

                    {{-- Feedback Bookmark Button --}}
                    <form method="POST"
                        action="{{ $isBookmarked
                            ? route('bookmarks.destroyQuestion', ['question' => $answeredQuestion->question_id])
                            : route('bookmarks.store', ['question' => $answeredQuestion->question_id]) }}"
                        class="absolute z-10 right-4 top-4">
                        @csrf
                        @method($isBookmarked ? 'DELETE' : 'PUT')
                        <input type="hidden" name="practice_session_id" value="{{ $session->session_id }}">

                        <button type="submit"
                            aria-label="{{ $isBookmarked ? 'Remove bookmark' : 'Bookmark this question' }}"
                            title="{{ $isBookmarked ? 'Remove bookmark' : 'Bookmark this question' }}"
                            class="flex h-8 w-8 items-center justify-center rounded-xl border-2 transition active:scale-95 {{ $isBookmarked ? 'border-[#F5A623]/40 bg-gold-tint text-gold shadow-sm' : 'border-line bg-surface-2 text-ink-muted hover:text-gold hover:border-[#F5A623]/40' }}">
                            <span class="text-lg leading-none">{{ $isBookmarked ? '★' : '☆' }}</span>
                        </button>
                    </form>

                    {{-- Domain Metadata --}}
                    <div class="flex flex-wrap items-center gap-2 pr-10">
                        <span
                            class="inline-flex items-center rounded-md bg-clinical-tint px-2 py-0.5 font-display text-[11px] font-bold text-clinical-ink">
                            {{ $answeredQuestion->competency->domain->domain_name }}
                        </span>
                        <span class="font-bold text-slate-300">•</span>
                        <span class="text-xs font-semibold text-ink-muted">
                            {{ $answeredQuestion->competency->title }}
                        </span>
                    </div>

                    {{-- Question Text --}}
                    <div class="text-sm sm:text-base font-semibold leading-relaxed font-sans text-ink">
                        {{ $answeredQuestion->question_text }}
                    </div>

                    {{-- Outcome Banner --}}
                    <x-ui.feedback-sheet
                        :status="$isCorrect
                            ? 'correct'
                            : 'incorrect'"
                    >
                        <x-slot:answer>
                            @if ($correctChoice)
                                @if (! $isCorrect)
                                    Correct answer:
                                @endif

                                <strong>
                                    {{ $correctChoice->choice_letter }}.
                                </strong>

                                {{ $correctChoice->choice_text }}
                            @endif
                        </x-slot:answer>

                        @if (
                            $answeredQuestion
                                ->hypercorrection_rationale
                        )
                            <div>
                                <h4 class="font-display text-xs font-extrabold uppercase tracking-wider">
                                    Clinical rationale
                                </h4>

                                <p class="mt-1">
                                    {{ $answeredQuestion
                                        ->hypercorrection_rationale }}
                                </p>
                            </div>
                        @endif

                        <x-slot:meta>
                            <div class="grid grid-cols-3 gap-2">
                                <div class="rounded-control bg-surface/70 p-2 text-center">
                                    <span class="block text-[10px] font-bold uppercase text-ink-muted">
                                        XP earned
                                    </span>

                                    <strong class="text-ink">
                                        +{{ $feedback['answer_xp'] }}
                                    </strong>
                                </div>

                                <div class="rounded-control bg-surface/70 p-2 text-center">
                                    <span class="block text-[10px] font-bold uppercase text-ink-muted">
                                        Prior mastery
                                    </span>

                                    <strong class="text-ink">
                                        {{ number_format(
                                            $feedback['prior_mastery']
                                                * 100,
                                            1,
                                        ) }}%
                                    </strong>
                                </div>

                                <div class="rounded-control bg-surface/70 p-2 text-center">
                                    <span class="block text-[10px] font-bold uppercase text-ink-muted">
                                        Updated mastery
                                    </span>

                                    <strong class="text-ink">
                                        {{ number_format(
                                            $feedback[
                                                'posterior_mastery'
                                            ] * 100,
                                            1,
                                        ) }}%
                                    </strong>
                                </div>
                            </div>
                        </x-slot:meta>
                    </x-ui.feedback-sheet>

                    {{-- BKT & XP Telemetry Strip --}}
                    <div class="grid grid-cols-3 gap-2.5 pt-1">
                        <div class="p-2 sm:p-2.5 text-center border rounded-xl border-line bg-surface-2/70">
                            <span class="block text-[10px] font-bold uppercase tracking-wider text-ink-muted">XP
                                Earned</span>
                            <span
                                class="text-base font-bold font-display text-gold-ink">+{{ $feedback['answer_xp'] }}</span>
                        </div>

                        <div class="p-2 sm:p-2.5 text-center border rounded-xl border-line bg-surface-2/70">
                            <span class="block text-[10px] font-bold uppercase tracking-wider text-ink-muted">Prior
                                Mastery</span>
                            <span
                                class="text-base font-bold font-display text-ink">{{ number_format($feedback['prior_mastery'] * 100, 1) }}%</span>
                        </div>

                        <div class="p-2 sm:p-2.5 text-center border rounded-xl border-line bg-surface-2/70">
                            <span class="block text-[10px] font-bold uppercase tracking-wider text-ink-muted">Updated
                                Mastery</span>
                            <span
                                class="text-base font-bold font-display text-clinical-ink">{{ number_format($feedback['posterior_mastery'] * 100, 1) }}%</span>
                        </div>
                    </div>

                    {{-- Speed Warning --}}
                    @if ($feedback['is_speed_flagged'])
                        <div
                            class="rounded-xl border border-gold/40 bg-gold-tint p-2.5 text-[11px] text-gold-ink leading-relaxed">
                            <strong>Speed-Flagged:</strong> Response submitted in
                            {{ number_format($feedback['response_seconds'], 2) }}s (min:
                            {{ number_format($feedback['minimum_seconds'], 2) }}s). No XP awarded.
                        </div>
                    @endif

                    {{-- Continue Button --}}
                    <form method="POST" action="{{ route('practice.next', ['session' => $session->session_id]) }}"
                        class="pt-1">
                        @csrf
                        <button type="submit" style="--lip: rgb(var(--color-primary-lip));"
                            class="btn-press w-full rounded-xl bg-primary py-2.5 sm:py-3 font-display text-sm font-bold text-white shadow-sm transition hover:bg-primary/95">
                            Continue
                        </button>
                    </form>

                </div>
            @else
                {{-- ============================================= --}}
                {{-- QUESTION VIEW (Compact Height)                --}}
                {{-- ============================================= --}}

                @if (!$question)
                    <div
                        class="p-6 text-center bg-surface border-2 border-b-4 shadow-sm rounded-2xl border-line animate-pop">
                        <p class="font-medium text-ink-muted text-sm">
                            No Practice question is available right now.
                        </p>
                        <div class="mt-4">
                            <a href="{{ route('practice.intro') }}" style="--lip: rgb(var(--color-primary-lip));"
                                class="btn-press inline-flex items-center rounded-xl bg-primary px-5 py-2 font-display text-xs font-bold text-white">
                                Back to Practice Setup
                            </a>
                        </div>
                    </div>
                @else
                    <div
                        class="relative p-4 sm:p-6 bg-surface border-2 border-b-4 shadow-sm overflow-hidden rounded-2xl border-line">

                        {{-- Bookmark Button --}}
                        <form method="POST"
                            action="{{ $isBookmarked
                                ? route('bookmarks.destroyQuestion', ['question' => $question->question_id])
                                : route('bookmarks.store', ['question' => $question->question_id]) }}"
                            class="absolute z-10 right-4 top-4">
                            @csrf
                            @method($isBookmarked ? 'DELETE' : 'PUT')
                            <input type="hidden" name="practice_session_id" value="{{ $session->session_id }}">

                            <button type="submit"
                                aria-label="{{ $isBookmarked ? 'Remove bookmark' : 'Bookmark this question' }}"
                                title="{{ $isBookmarked ? 'Remove bookmark' : 'Bookmark this question' }}"
                                class="flex h-8 w-8 items-center justify-center rounded-xl border-2 transition active:scale-95 {{ $isBookmarked ? 'border-[#F5A623]/40 bg-gold-tint text-gold shadow-sm' : 'border-line bg-surface-2 text-ink-muted hover:text-gold hover:border-[#F5A623]/40' }}">
                                <span class="text-lg leading-none">{{ $isBookmarked ? '★' : '☆' }}</span>
                            </button>
                        </form>

                        {{-- Domain Metadata --}}
                        <div class="flex flex-wrap items-center gap-2 pr-10">
                            <span
                                class="inline-flex items-center rounded-md bg-clinical-tint px-2 py-0.5 font-display text-[11px] font-bold text-clinical-ink">
                                {{ $question->competency->domain->domain_name }}
                            </span>
                            <span class="font-bold text-slate-300">•</span>
                            <span class="text-xs font-semibold text-ink-muted">
                                {{ $question->competency->title }}
                            </span>
                        </div>

                        {{-- Question Stem --}}
                        <div class="mt-2.5 text-base sm:text-lg font-semibold leading-snug font-sans text-ink">
                            {{ $question->question_text }}
                        </div>

                        {{-- Answer Options Form (Compact py-2.5 tiles) --}}
                        <form method="POST"
                            action="{{ route('practice.answer', ['session' => $session->session_id]) }}"
                            class="mt-4 space-y-2">
                            @csrf
                            <input type="hidden" name="question_id" value="{{ $question->question_id }}">

                            @foreach ($question->choices as $choice)
                                <x-ui.choice-card :compact="true">
                                    <x-slot:control>
                                        <input
                                            type="radio"
                                            name="selected_choice_id"
                                            value="{{ $choice->choice_id }}"
                                            @checked(
                                                old('selected_choice_id')
                                                    === $choice->choice_id
                                            )
                                            class="peer sr-only"
                                            required
                                        >
                                    </x-slot:control>

                                    <x-slot:letter>
                                        {{ $choice->choice_letter }}
                                    </x-slot:letter>

                                    {{ $choice->choice_text }}
                                </x-ui.choice-card>
                            @endforeach

                            @error('selected_choice_id')
                                <p class="mt-1.5 text-xs font-semibold text-weak-ink">
                                    {{ $message }}
                                </p>
                            @enderror

                            {{-- Compact Submit Button --}}
                            <div class="pt-3">
                                <button type="submit" style="--lip: rgb(var(--color-primary-lip));"
                                    class="btn-press w-full rounded-xl bg-primary py-2.5 sm:py-3 font-display text-sm font-bold text-white shadow-sm transition hover:bg-primary/95">
                                    Submit Answer
                                </button>
                            </div>
                        </form>

                    </div>
                @endif

            @endif

            {{-- Compact Session Ref --}}
            <div class="mt-2.5 text-center font-mono text-[10px] text-ink-muted">
                Session Ref: {{ $session->session_id }}
            </div>

        </div>
    </div>
</x-app-layout>
