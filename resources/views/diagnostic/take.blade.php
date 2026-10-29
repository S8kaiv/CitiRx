<x-app-layout>
    {{-- Header --}}
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('dashboard') }}"
                   title="Exit Diagnostic"
                   class="flex h-10 w-10 items-center justify-center rounded-2xl border-2 border-b-4 border-line bg-surface text-ink-muted shadow-xs transition hover:border-line-strong hover:bg-surface-2 hover:text-ink active:translate-y-0.5 active:border-b-2">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </a>

                <div>
                    <h1 class="font-display text-lg sm:text-xl font-black leading-tight text-ink">
                        {{ __('Diagnostic Test') }}
                    </h1>
                    <p class="font-display text-[10px] font-bold uppercase tracking-wider text-ink-muted">
                        Baseline TOS Calibration
                    </p>
                </div>
            </div>

            <a href="{{ route('dashboard') }}"
               class="font-display text-xs font-bold text-ink-muted transition hover:text-primary">
                Exit
            </a>
        </div>
    </x-slot>

    @php
        $questionsJson =$questions
            ->map(function ($question) {
                return [
                    'id' => $question->question_id,
                    'text' => $question->question_text,
                    'domain' => $question->competency->domain->domain_name,
                    'competency' => $question->competency->title,
                    'choices' => $question->choices
                        ->map(function ($choice) {
                            return [
                                'id' => $choice->choice_id,
                                'letter' => $choice->choice_letter,
                                'text' => $choice->choice_text,
                            ];
                        })
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    @endphp

    {{-- Main --}}
    <div
        class="ui-assessment-stage py-6 sm:py-8 font-sans antialiased text-ink"
        x-data="diagnosticForm()"
        x-init="initialize()"
    >
        <div class="max-w-2xl mx-auto px-4 sm:px-6 space-y-5">

            {{-- Error Alerts --}}
            @if (session('error'))
                <div role="alert" class="flex items-center gap-3 p-4 border-2 border-b-4 rounded-2xl border-weak/40 bg-weak-tint text-weak-ink text-sm font-bold shadow-xs animate-pop">
                    <svg class="h-5 w-5 shrink-0 text-weak" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div role="alert" class="flex items-center gap-3 p-4 border-2 border-b-4 rounded-2xl border-weak/40 bg-weak-tint text-weak-ink text-sm font-bold shadow-xs animate-pop">
                    <svg class="h-5 w-5 shrink-0 text-weak" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>
                    <span>Please answer all diagnostic questions before submitting.</span>
                </div>
            @endif

            {{-- Duolingo-Style Chunky Progress HUD --}}
            <div class="space-y-2">
                <div class="flex items-center justify-between font-display text-xs font-black uppercase tracking-wider">
                    <span class="text-ink">
                        Question <span class="text-primary font-black" x-text="currentIndex + 1"></span> of {{ $questions->count() }}
                    </span>

                    {{-- Answered Pill --}}
                    <div class="inline-flex items-center gap-2 rounded-2xl border-2 border-b-4 border-emerald-200 bg-emerald-50 px-3 py-1 text-emerald-700 shadow-xs">
                        <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="font-display font-black text-xs">
                            <span x-text="answeredCount"></span> / {{ $questions->count() }} Answered
                        </span>
                    </div>
                </div>

                {{-- Chunky Rounded Progress Bar --}}
                <div
                    class="ui-progress-track"
                    role="progressbar"
                    aria-label="Diagnostic progress"
                    aria-valuemin="0"
                    aria-valuemax="100"
                    x-bind:aria-valuenow="
                        Math.round(progressPercentage)
                    "
                >
                    <div
                        class="ui-progress-fill"
                        x-bind:style="
                            `--ui-progress:
                                ${progressPercentage / 100}`
                        "
                    ></div>
                </div>
            </div>

            {{-- Main Question Card --}}
            <div class="rounded-3xl border-2 border-b-4 border-line bg-surface p-5 sm:p-7 shadow-xs">
                <template x-for="(question, index) in questions" :key="question.id">
                    <div x-show="currentIndex === index" x-cloak class="space-y-5">

                        {{-- Domain Pill & Competency --}}
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 rounded-xl border border-b-2 border-clinical/30 bg-clinical-tint px-3 py-1 font-display text-xs font-extrabold text-clinical-ink"
                                  x-text="question.domain"></span>
                            <span class="font-bold text-slate-300">&bull;</span>
                            <span class="font-display text-xs font-bold text-ink-muted"
                                  x-text="question.competency"></span>
                        </div>

                        {{-- Question Stem --}}
                        <div class="font-display text-lg sm:text-xl font-black leading-snug text-ink"
                             x-text="question.text"></div>

                        {{-- Shared assessment choices --}}
                        <div class="space-y-3 pt-1">
                            <template
                                x-for="choice in question.choices"
                                :key="choice.id"
                            >
                                <x-ui.choice-card
                                    x-bind:class="{
                                        'pointer-events-none': saving,
                                    }"
                                >
                                    <x-slot:control>
                                        <input
                                            type="radio"
                                            class="peer sr-only"
                                            x-bind:name="'q_' + question.id"
                                            x-bind:value="choice.id"
                                            x-bind:checked="
                                                answers[question.id]
                                                    === choice.id
                                            "
                                            x-bind:disabled="saving"
                                            x-on:change="
                                                selectAnswer(
                                                    question.id,
                                                    choice.id
                                                )
                                            "
                                        >
                                    </x-slot:control>

                                    <x-slot:letter>
                                        <span x-text="choice.letter"></span>
                                    </x-slot:letter>

                                    <span x-text="choice.text"></span>
                                </x-ui.choice-card>
                            </template>
                        </div>

                    </div>
                </template>

                {{-- Autosave Indicator --}}
                <div class="mt-5 flex items-center justify-between text-xs min-h-6 pt-3 border-t border-line">
                    <div>
                        <span x-show="saving" x-cloak class="inline-flex items-center gap-1.5 font-display font-bold text-ink-muted">
                            <svg class="h-3.5 w-3.5 animate-spin text-primary" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            Syncing answer...
                        </span>

                        <span x-show="saveError" x-cloak x-text="saveError" class="font-bold text-weak-ink"></span>
                    </div>

                    <span class="font-mono text-[10px] font-bold uppercase tracking-wider text-ink-muted">
                    Autosave active
                    </span>
                </div>

                {{-- Mobile-safe assessment navigation --}}
                <x-ui.assessment-actions
                    label="Diagnostic navigation"
                >
                    <div class="flex w-full items-center justify-between gap-3">
                        <x-ui.button
                            type="button"
                            variant="secondary"
                            x-on:click="previousQuestion()"
                            x-bind:disabled="
                                currentIndex === 0
                                || saving
                            "
                        >
                            <svg
                                class="h-4 w-4"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2.5"
                                aria-hidden="true"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="m15.75 19.5-7.5-7.5 7.5-7.5"
                                />
                            </svg>

                            <span>Back</span>
                        </x-ui.button>

                        <template
                            x-if="
                                currentIndex
                                    < questions.length - 1
                            "
                        >
                            <x-ui.button
                                type="button"
                                x-on:click="nextQuestion()"
                                x-bind:disabled="saving"
                            >
                                <span>Continue</span>

                                <svg
                                    class="h-4 w-4"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2.5"
                                    aria-hidden="true"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="m8.25 4.5 7.5 7.5-7.5 7.5"
                                    />
                                </svg>
                            </x-ui.button>
                        </template>

                        <template
                            x-if="
                                currentIndex
                                    === questions.length - 1
                            "
                        >
                            <form
                                method="POST"
                                action="{{ route('diagnostic.submit', [
                                    'session' => $session->session_id,
                                ]) }}"
                                x-on:submit="handleSubmit($event)"
                            >
                                @csrf

                                <template
                                    x-for="
                                        (choiceId, questionId)
                                            in answers
                                    "
                                    :key="questionId"
                                >
                                    <input
                                        type="hidden"
                                        x-bind:name="
                                            'answers['
                                                + questionId
                                                + ']'
                                        "
                                        x-bind:value="choiceId"
                                    >
                                </template>

                                <x-ui.button
                                    type="submit"
                                    x-bind:disabled="
                                        answeredCount
                                            < questions.length
                                        || saving
                                    "
                                >
                                    Complete Diagnostic
                                </x-ui.button>
                            </form>
                        </template>
                    </div>
                </x-ui.assessment-actions>

            </div>

            {{-- Duolingo Path Puck Matrix --}}
            <div class="rounded-3xl border-2 border-b-4 border-line bg-surface p-5 shadow-xs">
                <div class="flex items-center justify-between mb-3 px-1">
                    <span class="font-display text-xs font-black uppercase tracking-wider text-ink">
                        Item Map
                    </span>
                    <span class="font-display text-[11px] font-bold text-ink-muted">
                        Tap to jump
                    </span>
                </div>

                <div class="flex flex-wrap gap-2 justify-start">
                    <template x-for="(question, index) in questions" :key="'nav-' + question.id">
                        <button type="button"
                                @click="goToQuestion(index)"
                                :disabled="saving"
                                class="h-9 w-9 rounded-xl font-display text-xs font-black transition-all active:translate-y-0.5 active:border-b-2 disabled:opacity-40 disabled:cursor-not-allowed"
                                :class="{
                                    'border-2 border-b-4 border-primary-lip bg-primary text-white shadow-xs scale-105': currentIndex === index,
                                    'border-2 border-b-4 border-emerald-600 bg-emerald-500 text-white shadow-xs': currentIndex !== index && answers[question.id],
                                    'border-2 border-b-4 border-line bg-surface-2 text-ink-muted hover:border-line-strong hover:bg-surface': currentIndex !== index && !answers[question.id]
                                }"
                                x-text="index + 1"></button>
                    </template>
                </div>
            </div>

            {{-- Session Ref --}}
            <p class="text-center font-mono text-[10px] text-ink-muted">
                Session Ref: {{ $session->session_id }}
            </p>

        </div>
    </div>

    {{-- Script --}}
    <script>
        function diagnosticForm() {
            return {
                currentIndex: 0,
                answers: @js((object) old('answers', $savedAnswers ?? [])),
                questions: @js($questionsJson),
                saving: false,
                saveError: null,

                initialize() {
                    const firstUnanswered = this.questions.findIndex(
                        question => !this.answers[question.id]
                    );

                    this.currentIndex = firstUnanswered === -1
                        ? Math.max(0, this.questions.length - 1)
                        : firstUnanswered;
                },

                async selectAnswer(questionId, choiceId) {
                    const previousChoice = this.answers[questionId];

                    this.answers[questionId] = choiceId;
                    this.saving = true;
                    this.saveError = null;

                    try {
                        const response = await fetch(
                            @js(route('diagnostic.answer', ['session' => $session->session_id])),
                            {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                },
                                body: JSON.stringify({
                                    question_id: questionId,
                                    choice_id: choiceId,
                                }),
                            }
                        );

                        if (!response.ok) {
                            throw new Error('Unable to save answer.');
                        }
                    } catch (error) {
                        if (previousChoice) {
                            this.answers[questionId] = previousChoice;
                        } else {
                            delete this.answers[questionId];
                        }

                        this.saveError = 'Your answer could not be saved. Please try again.';
                    } finally {
                        this.saving = false;
                    }
                },

                get answeredCount() {
                    return this.questions.filter(
                        question => !!this.answers[question.id]
                    ).length;
                },

                get progressPercentage() {
                    if (this.questions.length === 0) return 0;
                    return (this.answeredCount / this.questions.length) * 100;
                },

                nextQuestion() {
                    if (!this.saving && this.currentIndex < this.questions.length - 1) {
                        this.currentIndex++;
                    }
                },

                previousQuestion() {
                    if (!this.saving && this.currentIndex > 0) {
                        this.currentIndex--;
                    }
                },

                goToQuestion(index) {
                    if (!this.saving) {
                        this.currentIndex = index;
                    }
                },

                handleSubmit(event) {
                    if (this.saving) {
                        event.preventDefault();
                        return;
                    }

                    if (this.answeredCount < this.questions.length) {
                        event.preventDefault();
                        alert('Please answer all questions before submitting.');
                    }
                },
            };
        }
    </script>
</x-app-layout>
