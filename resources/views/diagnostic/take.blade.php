<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Diagnostic Test') }}
        </h2>
    </x-slot>

    @php
        $questionsJson = $questions
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

    <div
        class="py-12"
        x-data="diagnosticForm()"
        x-init="initialize()"
    >
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">

            @if (session('error'))
                <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-lg">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-lg">
                    Please answer all diagnostic questions before submitting.
                </div>
            @endif

            {{-- Progress --}}
            <div class="mb-4 flex items-center justify-between text-sm text-gray-600">
                <span>
                    Question

                    <span
                        class="font-semibold"
                        x-text="currentIndex + 1"
                    ></span>

                    of {{ $questions->count() }}
                </span>

                <span>
                    Answered

                    <span
                        class="font-semibold"
                        x-text="answeredCount"
                    ></span>

                    / {{ $questions->count() }}
                </span>
            </div>

            {{-- Progress bar --}}
            <div class="w-full bg-gray-200 rounded-full h-2 mb-6">
                <div
                    class="bg-indigo-600 h-2 rounded-full transition-all"
                    :style="`width: ${progressPercentage}%`"
                ></div>
            </div>

            {{-- Question card --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">

                {{-- Question content --}}
                <div class="p-6">
                    <template
                        x-for="(question, index) in questions"
                        :key="question.id"
                    >
                        <div
                            x-show="currentIndex === index"
                            x-cloak
                        >
                            <div
                                class="mb-2 text-xs uppercase tracking-wide text-gray-500"
                                x-text="question.domain"
                            ></div>

                            <div
                                class="mb-4 text-xs text-gray-500"
                                x-text="question.competency"
                            ></div>

                            <div
                                class="text-lg font-medium mb-6"
                                x-text="question.text"
                            ></div>

                            <template
                                x-for="choice in question.choices"
                                :key="choice.id"
                            >
                                <label
                                    class="flex items-start p-3 mb-2 border rounded cursor-pointer hover:bg-gray-50 transition"
                                    :class="{
                                        'border-indigo-500 bg-indigo-50':
                                            answers[question.id] === choice.id,

                                        'opacity-60':
                                            saving
                                    }"
                                >
                                    <input
                                        type="radio"
                                        :name="'q_' + question.id"
                                        :value="choice.id"
                                        :checked="
                                            answers[question.id] === choice.id
                                        "
                                        @change="
                                            selectAnswer(
                                                question.id,
                                                choice.id
                                            )
                                        "
                                        :disabled="saving"
                                        class="mt-1 mr-3"
                                    >

                                    <span>
                                        <span
                                            class="font-semibold mr-2"
                                            x-text="choice.letter + '.'"
                                        ></span>

                                        <span
                                            x-text="choice.text"
                                        ></span>
                                    </span>
                                </label>
                            </template>
                        </div>
                    </template>
                </div>

                {{-- Autosave status --}}
                <div class="px-6 pb-3 text-sm min-h-6">
                    <span
                        x-show="saving"
                        x-cloak
                        class="text-gray-500"
                    >
                        Saving answer...
                    </span>

                    <span
                        x-show="saveError"
                        x-cloak
                        x-text="saveError"
                        class="text-red-600"
                    ></span>
                </div>

                {{-- Navigation --}}
                <div class="px-6 py-4 bg-gray-50 flex justify-between items-center border-t">

                    {{-- Previous --}}
                    <button
                        type="button"
                        @click="previousQuestion()"
                        :disabled="
                            currentIndex === 0 || saving
                        "
                        class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        &larr; Previous
                    </button>

                    {{-- Next --}}
                    <template
                        x-if="
                            currentIndex <
                            questions.length - 1
                        "
                    >
                        <button
                            type="button"
                            @click="nextQuestion()"
                            :disabled="saving"
                            class="px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Next &rarr;
                        </button>
                    </template>

                    {{-- Submit --}}
                    <template
                        x-if="
                            currentIndex ===
                            questions.length - 1
                        "
                    >
                        <form
                            method="POST"
                            action="{{ route('diagnostic.submit', ['session' => $session->session_id]) }}"
                            @submit="handleSubmit($event)"
                        >
                            @csrf

                            {{-- Keep final answer inputs --}}
                            <template
                                x-for="(choiceId, questionId) in answers"
                                :key="questionId"
                            >
                                <input
                                    type="hidden"
                                    :name="'answers[' + questionId + ']'"
                                    :value="choiceId"
                                >
                            </template>

                            <button
                                type="submit"
                                :disabled="
                                    answeredCount < questions.length
                                    || saving
                                "
                                class="px-4 py-2 rounded bg-green-600 text-white hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Submit Diagnostic
                            </button>
                        </form>
                    </template>

                </div>
            </div>

            {{-- Question number navigation --}}
            <div class="mt-6 flex flex-wrap gap-2 justify-center">
                <template
                    x-for="(question, index) in questions"
                    :key="'nav-' + question.id"
                >
                    <button
                        type="button"
                        @click="goToQuestion(index)"
                        :disabled="saving"
                        class="w-9 h-9 rounded text-sm font-medium border disabled:opacity-50 disabled:cursor-not-allowed"
                        :class="{
                            'bg-indigo-600 text-white border-indigo-600':
                                currentIndex === index,

                            'bg-green-100 text-green-800 border-green-300':
                                currentIndex !== index
                                && answers[question.id],

                            'bg-white text-gray-700':
                                currentIndex !== index
                                && !answers[question.id]
                        }"
                        x-text="index + 1"
                    ></button>
                </template>
            </div>

            {{-- Autosave explanation --}}
            <p class="mt-4 text-xs text-gray-500 text-center">
                Your answers are automatically saved so you can safely
                resume the diagnostic later.
            </p>

        </div>
    </div>

    <script>
        function diagnosticForm() {
            return {
                currentIndex: 0,

                /**
                 * Restore answers from:
                 *
                 * 1. Laravel validation/scoring errors, or
                 * 2. database draft_answers when resuming.
                 */
                answers: @js(
                    (object) old(
                        'answers',
                        $savedAnswers ?? []
                    )
                ),

                questions: @js($questionsJson),

                saving: false,

                saveError: null,

                /**
                 * Resume from the first unanswered question.
                 */
                initialize() {
                    const firstUnanswered =
                        this.questions.findIndex(
                            question =>
                                !this.answers[
                                    question.id
                                ]
                        );

                    this.currentIndex =
                        firstUnanswered === -1
                            ? Math.max(
                                0,
                                this.questions.length - 1
                            )
                            : firstUnanswered;
                },

                /**
                 * Save one answer to assessment_sessions.draft_answers.
                 */
                async selectAnswer(
                    questionId,
                    choiceId
                ) {
                    const previousChoice =
                        this.answers[
                            questionId
                        ];

                    /*
                     * Optimistically update the UI.
                     */
                    this.answers[
                        questionId
                    ] = choiceId;

                    this.saving = true;
                    this.saveError = null;

                    try {
                        const response =
                            await fetch(
                                @js(
                                    route(
                                        'diagnostic.answer',
                                        [
                                            'session' =>
                                                $session->session_id
                                        ]
                                    )
                                ),
                                {
                                    method: 'POST',

                                    headers: {
                                        'Content-Type':
                                            'application/json',

                                        'Accept':
                                            'application/json',

                                        'X-CSRF-TOKEN':
                                            document
                                                .querySelector(
                                                    'meta[name="csrf-token"]'
                                                )
                                                .getAttribute(
                                                    'content'
                                                ),
                                    },

                                    body:
                                        JSON.stringify({
                                            question_id:
                                                questionId,

                                            choice_id:
                                                choiceId,
                                        }),
                                }
                            );

                        if (!response.ok) {
                            throw new Error(
                                'Unable to save answer.'
                            );
                        }

                    } catch (error) {

                        /*
                         * Autosave failed.
                         *
                         * Restore the previous selection so the
                         * interface does not claim the new answer
                         * was successfully persisted.
                         */
                        if (previousChoice) {
                            this.answers[
                                questionId
                            ] = previousChoice;
                        } else {
                            delete this.answers[
                                questionId
                            ];
                        }

                        this.saveError =
                            'Your answer could not be saved. Please try again.';

                    } finally {
                        this.saving = false;
                    }
                },

                /**
                 * Number of answered diagnostic questions.
                 */
                get answeredCount() {
                    return this.questions.filter(
                        question => {
                            return !!this.answers[
                                question.id
                            ];
                        }
                    ).length;
                },

                /**
                 * Progress bar percentage.
                 */
                get progressPercentage() {
                    if (
                        this.questions.length === 0
                    ) {
                        return 0;
                    }

                    return (
                        (
                            this.answeredCount /
                            this.questions.length
                        ) * 100
                    );
                },

                /**
                 * Go to the next question.
                 */
                nextQuestion() {
                    if (
                        !this.saving
                        && this.currentIndex
                            < this.questions.length - 1
                    ) {
                        this.currentIndex++;
                    }
                },

                /**
                 * Go to the previous question.
                 */
                previousQuestion() {
                    if (
                        !this.saving
                        && this.currentIndex > 0
                    ) {
                        this.currentIndex--;
                    }
                },

                /**
                 * Jump using the numbered navigation.
                 */
                goToQuestion(index) {
                    if (!this.saving) {
                        this.currentIndex = index;
                    }
                },

                /**
                 * Protect final submission.
                 */
                handleSubmit(event) {
                    /*
                     * Don't submit while an autosave request
                     * is still running.
                     */
                    if (this.saving) {
                        event.preventDefault();

                        return;
                    }

                    /*
                     * Every diagnostic question must be answered.
                     */
                    if (
                        this.answeredCount <
                        this.questions.length
                    ) {
                        event.preventDefault();

                        alert(
                            'Please answer all questions before submitting.'
                        );
                    }
                },
            };
        }
    </script>
</x-app-layout>