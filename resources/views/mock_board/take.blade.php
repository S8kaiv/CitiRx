<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="font-display text-lg font-black text-slate-900 sm:text-xl">
                Research Post-Test
            </h1>

            <p class="font-display text-[10px] font-bold uppercase tracking-wider text-muted-ink">
                Form B · Timed Assessment
            </p>
        </div>
    </x-slot>

    @php
        /*
         * Only send data needed by the interface.
         *
         * Do not serialize the Eloquent models directly because doing so
         * could expose is_correct, rationales, or distractor notes.
         */
        $questionPayload = $responses
            ->map(function ($response) use ($session) {
                $question = $response->question;

                return [
                    'id' => $question->question_id,
                    'position' => $response->item_position,
                    'text' => $question->question_text,
                    'domain' => $question->competency->domain->domain_name,
                    'competency' => $question->competency->title,
                    'selectedChoiceId' => $response->selected_choice_id,

                    'saveUrl' => route('mock-board.answer', [
                        'session' => $session->session_id,
                        'question' => $question->question_id,
                    ]),

                    'choices' => $question->choices
                        ->map(fn ($choice) => [
                            'id' => $choice->choice_id,
                            'letter' => $choice->choice_letter,
                            'text' => $choice->choice_text,
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();

        $firstUnansweredIndex = $responses->search(
            fn ($response): bool =>
                $response->selected_choice_id === null
        );

        if ($firstUnansweredIndex === false) {
            $firstUnansweredIndex = 0;
        }
    @endphp

    <div
        class="py-5 sm:py-8"
        x-data="mockBoardExam()"
        x-cloak
    >
        <div class="mx-auto max-w-4xl space-y-5 px-4 sm:px-6">

            @if (session('error'))
                <div
                    role="alert"
                    class="rounded-2xl border-2 border-b-4 border-weak/40 bg-weak-tint p-4 text-sm font-bold text-weak-ink"
                >
                    {{ session('error') }}
                </div>
            @endif

            <section class="rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-4 shadow-xs sm:p-5">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="font-display text-[10px] font-black uppercase tracking-wider text-muted-ink">
                            Current progress
                        </p>

                        <p class="mt-1 font-display text-sm font-black text-slate-900">
                            Question
                            <span class="text-primary" x-text="currentIndex + 1"></span>
                            of
                            <span>{{ count($questionPayload) }}</span>
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <div class="rounded-2xl border-2 border-b-4 border-clinical/30 bg-clinical-tint px-4 py-2">
                            <p class="font-display text-[9px] font-black uppercase tracking-wider text-clinical-ink">
                                Answered
                            </p>

                            <p class="font-display text-base font-black text-clinical-ink">
                                <span x-text="answeredCount"></span>
                                /
                                <span x-text="questions.length"></span>
                            </p>
                        </div>

                        <div
                            role="timer"
                            aria-live="polite"
                            class="min-w-28 rounded-2xl border-2 border-b-4 px-4 py-2 text-center transition"
                            :class="timeIsUrgent
                                ? 'border-weak/40 bg-weak-tint text-weak-ink'
                                : 'border-primary/30 bg-primary-tint text-primary'"
                        >
                            <p class="font-display text-[9px] font-black uppercase tracking-wider">
                                Time remaining
                            </p>

                            <p
                                class="font-mono text-lg font-black"
                                x-text="formattedTime"
                            ></p>
                        </div>
                    </div>
                </div>

                <div
                    class="mt-4 h-4 overflow-hidden rounded-full border-2 border-slate-200 bg-slate-100 p-0.5"
                    role="progressbar"
                    aria-label="Answered questions"
                    aria-valuemin="0"
                    aria-valuemax="100"
                    :aria-valuenow="Math.round(progressPercentage)"
                >
                    <div
                        class="relative h-full rounded-full bg-gradient-to-r from-primary to-[#8F75FF] transition-all duration-300"
                        :style="`width: ${progressPercentage}%`"
                    >
                        <div class="absolute inset-x-0 top-0.5 h-1 rounded-full bg-white/30"></div>
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-5 shadow-xs sm:p-8">
                <template x-if="currentQuestion">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                class="rounded-xl border border-b-2 border-clinical/30 bg-clinical-tint px-3 py-1 font-display text-xs font-black text-clinical-ink"
                                x-text="currentQuestion.domain"
                            ></span>

                            <span class="font-bold text-slate-300">
                                •
                            </span>

                            <span
                                class="font-display text-xs font-bold text-muted-ink"
                                x-text="currentQuestion.competency"
                            ></span>
                        </div>

                        <h2
                            class="mt-5 font-display text-lg font-black leading-relaxed text-slate-900 sm:text-xl"
                            x-text="currentQuestion.text"
                        ></h2>

                        <div class="mt-6 space-y-3">
                            <template
                                x-for="choice in currentQuestion.choices"
                                :key="choice.id"
                            >
                                <label class="group block cursor-pointer">
                                    <input
                                        type="radio"
                                        class="peer sr-only"
                                        :name="'question_' + currentQuestion.id"
                                        :value="choice.id"
                                        :checked="answers[currentQuestion.id] === choice.id"
                                        :disabled="saving || submitting || expirySubmitted"
                                        @change="saveAnswer(
                                            currentQuestion.id,
                                            choice.id
                                        )"
                                    >

                                    <div
                                        class="flex items-center gap-4 rounded-2xl border-2 border-b-4 p-4 transition active:translate-y-0.5 active:border-b-2 peer-focus-visible:ring-4 peer-focus-visible:ring-primary/25"
                                        :class="answers[currentQuestion.id] === choice.id
                                            ? 'border-primary bg-primary-tint/40'
                                            : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50'"
                                    >
                                        <div
                                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border-2 font-display text-sm font-black transition"
                                            :class="answers[currentQuestion.id] === choice.id
                                                ? 'border-primary-lip bg-primary text-white'
                                                : 'border-slate-200 bg-slate-100 text-slate-600'"
                                        >
                                            <span x-text="choice.letter"></span>
                                        </div>

                                        <span
                                            class="text-sm font-semibold leading-relaxed sm:text-base"
                                            :class="answers[currentQuestion.id] === choice.id
                                                ? 'text-primary'
                                                : 'text-slate-800'"
                                            x-text="choice.text"
                                        ></span>
                                    </div>
                                </label>
                            </template>
                        </div>

                        <div class="mt-5 flex min-h-7 flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4">
                            <div
                                aria-live="polite"
                                class="text-xs font-bold"
                            >
                                <span
                                    x-show="saveState === 'idle'"
                                    class="text-muted-ink"
                                >
                                    Autosave ready
                                </span>

                                <span
                                    x-show="saveState === 'saving'"
                                    class="inline-flex items-center gap-2 text-primary"
                                >
                                    <span class="h-3 w-3 animate-spin rounded-full border-2 border-primary/25 border-t-primary"></span>
                                    Saving...
                                </span>

                                <span
                                    x-show="saveState === 'saved'"
                                    class="text-strong-ink"
                                >
                                    Answer saved
                                </span>

                                <span
                                    x-show="saveState === 'error'"
                                    class="text-weak-ink"
                                    x-text="saveError"
                                ></span>
                            </div>

                            <button
                                type="button"
                                x-show="currentQuestion
                                    && answers[currentQuestion.id]"
                                :disabled="saving || submitting || expirySubmitted"
                                @click="saveAnswer(
                                    currentQuestion.id,
                                    null
                                )"
                                class="rounded-xl px-3 py-1.5 font-display text-xs font-black text-muted-ink transition hover:bg-slate-100 hover:text-weak-ink focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/25 disabled:cursor-not-allowed disabled:opacity-40"
                            >
                                Clear answer
                            </button>
                        </div>
                    </div>
                </template>

                <div class="mt-5 flex items-center justify-between gap-3 border-t border-slate-100 pt-5">
                    <button
                        type="button"
                        :disabled="currentIndex === 0 || saving || submitting"
                        @click="previousQuestion()"
                        class="rounded-2xl border-2 border-b-4 border-slate-200 bg-white px-5 py-3 font-display text-xs font-black uppercase tracking-wider text-slate-600 transition hover:bg-slate-50 focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/25 active:translate-y-0.5 active:border-b-2 disabled:cursor-not-allowed disabled:opacity-30"
                    >
                        Back
                    </button>

                    <button
                        x-show="currentIndex < questions.length - 1"
                        type="button"
                        :disabled="saving || submitting"
                        @click="nextQuestion()"
                        style="--lip: #4A2FC4;"
                        class="btn-press rounded-2xl bg-primary px-7 py-3 font-display text-sm font-black text-white transition hover:bg-primary/95 focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/25 disabled:cursor-not-allowed disabled:opacity-40"
                    >
                        Continue
                    </button>

                    <button
                        x-show="currentIndex === questions.length - 1"
                        type="button"
                        :disabled="saving || submitting"
                        @click="submitDialogOpen = true"
                        style="--lip: #15803D;"
                        class="btn-press rounded-2xl bg-strong px-7 py-3 font-display text-sm font-black text-white transition hover:brightness-95 focus:outline-none focus-visible:ring-4 focus-visible:ring-strong/25 disabled:cursor-not-allowed disabled:opacity-40"
                    >
                        Review & Submit
                    </button>
                </div>
            </section>

            <section class="rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-5 shadow-xs">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="font-display text-sm font-black text-slate-900">
                            Question navigator
                        </h2>

                        <p class="mt-1 text-xs font-semibold text-muted-ink">
                            Page
                            <span x-text="currentPage + 1"></span>
                            of
                            <span x-text="totalPages"></span>
                        </p>
                    </div>

                    <button
                        type="button"
                        :disabled="saving || submitting"
                        @click="submitDialogOpen = true"
                        class="rounded-xl px-3 py-2 font-display text-xs font-black text-primary transition hover:bg-primary-tint focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/25 disabled:cursor-not-allowed disabled:opacity-40"
                    >
                        Submit assessment
                    </button>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <template
                        x-for="page in totalPages"
                        :key="'page-' + page"
                    >
                        <button
                            type="button"
                            :disabled="saving || submitting"
                            @click="goToQuestion((page - 1) * pageSize)"
                            class="h-9 min-w-9 rounded-xl border-2 border-b-4 px-2 font-display text-xs font-black transition focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/25 active:translate-y-0.5 active:border-b-2 disabled:cursor-not-allowed disabled:opacity-40"
                            :class="currentPage === page - 1
                                ? 'border-primary-lip bg-primary text-white'
                                : 'border-slate-200 bg-slate-50 text-slate-600 hover:bg-white'"
                            x-text="page"
                        ></button>
                    </template>
                </div>

                <div class="mt-4 grid grid-cols-5 gap-2 sm:grid-cols-10">
                    <template
                        x-for="item in visibleQuestions"
                        :key="'item-' + item.question.id"
                    >
                        <button
                            type="button"
                            :disabled="saving || submitting"
                            @click="goToQuestion(item.index)"
                            class="aspect-square rounded-xl border-2 border-b-4 font-display text-xs font-black transition focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/25 active:translate-y-0.5 active:border-b-2 disabled:cursor-not-allowed disabled:opacity-40"
                            :class="{
                                'border-primary-lip bg-primary text-white':
                                    currentIndex === item.index,

                                'border-strong/50 bg-strong-tint text-strong-ink':
                                    currentIndex !== item.index
                                    && answers[item.question.id],

                                'border-slate-200 bg-slate-50 text-slate-600 hover:bg-white':
                                    currentIndex !== item.index
                                    && !answers[item.question.id]
                            }"
                            x-text="item.index + 1"
                        ></button>
                    </template>
                </div>

                <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4">
                    <button
                        type="button"
                        :disabled="saving || submitting"
                        @click="exitAssessment()"
                        class="font-display text-xs font-black text-muted-ink transition hover:text-primary focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/25 disabled:cursor-not-allowed disabled:opacity-40"
                    >
                        Save and exit
                    </button>

                    <p class="font-mono text-[10px] text-muted-ink">
                        Session: {{ $session->session_id }}
                    </p>
                </div>
            </section>

        </div>

        <div
            x-show="submitDialogOpen"
            x-transition.opacity
            @keydown.escape.window="
                if (!submitting) {
                    submitDialogOpen = false;
                }
            "
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="submit-dialog-title"
        >
            <div
                @click.outside="
                    if (!submitting) {
                        submitDialogOpen = false;
                    }
                "
                class="w-full max-w-md rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-6 shadow-xl sm:p-7"
            >
                <h2
                    id="submit-dialog-title"
                    class="font-display text-xl font-black text-slate-900"
                >
                    Submit your post-test?
                </h2>

                <p class="mt-3 text-sm font-medium leading-relaxed text-slate-600">
                    You answered
                    <strong
                        class="text-slate-900"
                        x-text="answeredCount"
                    ></strong>
                    of
                    <strong class="text-slate-900">
                        {{ count($questionPayload) }}
                    </strong>
                    questions.
                </p>

                <div
                    x-show="unansweredCount > 0"
                    class="mt-4 rounded-2xl border-2 border-gold/30 bg-gold-tint p-4 text-sm font-bold text-gold-ink"
                >
                    <span x-text="unansweredCount"></span>
                    unanswered
                    <span x-text="unansweredCount === 1
                        ? 'question will'
                        : 'questions will'"></span>
                    remain unanswered in your final score.
                </div>

                <p class="mt-4 text-sm font-semibold text-slate-600">
                    Submission is final. You cannot retry or change your answers.
                </p>

                <div class="mt-6 flex justify-end gap-3">
                    <button
                        type="button"
                        :disabled="submitting"
                        @click="submitDialogOpen = false"
                        class="rounded-2xl border-2 border-b-4 border-slate-200 bg-white px-5 py-3 font-display text-xs font-black text-slate-600 transition hover:bg-slate-50 focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/25 active:translate-y-0.5 active:border-b-2 disabled:opacity-40"
                    >
                        Continue Reviewing
                    </button>

                    <form
                        x-ref="submitForm"
                        method="POST"
                        action="{{ route('mock-board.submit', [
                            'session' => $session->session_id,
                        ]) }}"
                        @submit="handleSubmit($event)"
                    >
                        @csrf

                        <button
                            type="submit"
                            :disabled="saving || submitting"
                            style="--lip: #15803D;"
                            class="btn-press rounded-2xl bg-strong px-6 py-3 font-display text-xs font-black text-white transition hover:brightness-95 focus:outline-none focus-visible:ring-4 focus-visible:ring-strong/25 disabled:cursor-not-allowed disabled:opacity-40"
                        >
                            <span x-show="!submitting">
                                Submit Final
                            </span>

                            <span x-show="submitting">
                                Submitting...
                            </span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function mockBoardExam() {
            return {
                questions: @js($questionPayload),
                currentIndex: @js((int) $firstUnansweredIndex),
                secondsRemaining: @js((int) $secondsRemaining),
                csrfToken: @js(csrf_token()),
                introUrl: @js(route('mock-board.intro')),

                pageSize: 10,
                answers: {},
                deadline: null,
                timer: null,
                saving: false,
                submitting: false,
                expirySubmitted: false,
                submitDialogOpen: false,
                saveState: 'idle',
                saveError: '',

                init() {
                    this.answers = Object.fromEntries(
                        this.questions.map((question) => [
                            question.id,
                            question.selectedChoiceId ?? null,
                        ]),
                    );

                    this.deadline =
                        Date.now()
                        + (this.secondsRemaining * 1000);

                    this.tick();

                    if (this.secondsRemaining > 0) {
                        this.timer = window.setInterval(
                            () => this.tick(),
                            1000,
                        );
                    }
                },

                destroy() {
                    if (this.timer !== null) {
                        window.clearInterval(this.timer);
                    }
                },

                get currentQuestion() {
                    return this.questions[
                        this.currentIndex
                    ] ?? null;
                },

                get answeredCount() {
                    return Object.values(this.answers)
                        .filter((choiceId) =>
                            typeof choiceId === 'string'
                            && choiceId.length > 0
                        )
                        .length;
                },

                get unansweredCount() {
                    return Math.max(
                        0,
                        this.questions.length
                            - this.answeredCount,
                    );
                },

                get progressPercentage() {
                    if (this.questions.length === 0) {
                        return 0;
                    }

                    return (
                        this.answeredCount
                        / this.questions.length
                    ) * 100;
                },

                get totalPages() {
                    return Math.ceil(
                        this.questions.length
                        / this.pageSize,
                    );
                },

                get currentPage() {
                    return Math.floor(
                        this.currentIndex
                        / this.pageSize,
                    );
                },

                get visibleQuestions() {
                    const start =
                        this.currentPage
                        * this.pageSize;

                    return this.questions
                        .slice(
                            start,
                            start + this.pageSize,
                        )
                        .map((question, offset) => ({
                            question,
                            index: start + offset,
                        }));
                },

                get formattedTime() {
                    const hours = Math.floor(
                        this.secondsRemaining / 3600,
                    );

                    const minutes = Math.floor(
                        (this.secondsRemaining % 3600) / 60,
                    );

                    const seconds =
                        this.secondsRemaining % 60;

                    if (hours > 0) {
                        return [
                            hours,
                            String(minutes).padStart(2, '0'),
                            String(seconds).padStart(2, '0'),
                        ].join(':');
                    }

                    return [
                        String(minutes).padStart(2, '0'),
                        String(seconds).padStart(2, '0'),
                    ].join(':');
                },

                get timeIsUrgent() {
                    return this.secondsRemaining <= 300;
                },

                tick() {
                    this.secondsRemaining = Math.max(
                        0,
                        Math.ceil(
                            (
                                this.deadline
                                - Date.now()
                            ) / 1000,
                        ),
                    );

                    if (
                        this.secondsRemaining === 0
                        && !this.expirySubmitted
                    ) {
                        this.expirySubmitted = true;

                        if (this.timer !== null) {
                            window.clearInterval(this.timer);
                        }

                        if (!this.saving) {
                            this.requestSubmission();
                        }
                    }
                },

                async saveAnswer(
                    questionId,
                    choiceId,
                ) {
                    if (
                        this.saving
                        || this.submitting
                        || this.expirySubmitted
                    ) {
                        return;
                    }

                    const question = this.questions.find(
                        (item) =>
                            item.id === questionId,
                    );

                    if (!question) {
                        return;
                    }

                    const previousChoice =
                        this.answers[questionId] ?? null;

                    this.answers[questionId] = choiceId;
                    this.saving = true;
                    this.saveState = 'saving';
                    this.saveError = '';

                    try {
                        const response = await fetch(
                            question.saveUrl,
                            {
                                method: 'PATCH',

                                headers: {
                                    'Accept': 'application/json',
                                    'Content-Type':
                                        'application/json',
                                    'X-CSRF-TOKEN':
                                        this.csrfToken,
                                },

                                body: JSON.stringify({
                                    selected_choice_id:
                                        choiceId,
                                }),
                            },
                        );

                        if (!response.ok) {
                            let message =
                                'The answer could not be saved.';

                            try {
                                const payload =
                                    await response.json();

                                message =
                                    payload.message
                                    ?? message;
                            } catch (error) {
                                // Keep the fallback message.
                            }

                            const saveError =
                                new Error(message);

                            saveError.expired =
                                response.status === 409;

                            throw saveError;
                        }

                        this.saveState = 'saved';
                    } catch (error) {
                        this.answers[questionId] =
                            previousChoice;

                        this.saveState = 'error';
                        this.saveError =
                            error.message
                            ?? 'The answer could not be saved.';

                        if (error.expired) {
                            this.expirySubmitted = true;
                        }
                    } finally {
                        this.saving = false;

                        if (
                            this.expirySubmitted
                            && !this.submitting
                        ) {
                            this.requestSubmission();
                        }
                    }
                },

                previousQuestion() {
                    this.goToQuestion(
                        this.currentIndex - 1,
                    );
                },

                nextQuestion() {
                    this.goToQuestion(
                        this.currentIndex + 1,
                    );
                },

                goToQuestion(index) {
                    if (
                        this.saving
                        || this.submitting
                    ) {
                        return;
                    }

                    const safeIndex = Math.max(
                        0,
                        Math.min(
                            index,
                            this.questions.length - 1,
                        ),
                    );

                    this.currentIndex = safeIndex;

                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth',
                    });
                },

                exitAssessment() {
                    if (
                        this.saving
                        || this.submitting
                    ) {
                        return;
                    }

                    window.location.href =
                        this.introUrl;
                },

                requestSubmission() {
                    if (this.submitting) {
                        return;
                    }

                    this.submitDialogOpen = false;

                    this.$nextTick(() => {
                        this.$refs.submitForm
                            ?.requestSubmit();
                    });
                },

                handleSubmit(event) {
                    if (
                        this.saving
                        && !this.expirySubmitted
                    ) {
                        event.preventDefault();

                        this.saveState = 'error';
                        this.saveError =
                            'Wait for the current answer to finish saving.';

                        return;
                    }

                    this.submitting = true;

                    if (this.timer !== null) {
                        window.clearInterval(this.timer);
                    }
                },
            };
        }
    </script>
</x-app-layout>
