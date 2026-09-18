<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Practice Mode') }}
        </h2>
    </x-slot>

    @php
        /*
         * During a question:
         * total_items = already answered questions,
         * so display total_items + 1.
         *
         * During feedback:
         * total_items already includes the answer
         * that produced the feedback.
         */
        $displayQuestionNumber = $feedback
            ? min($session->total_items, $session->target_length)
            : min($session->total_items + 1, $session->target_length);

        $progress = $session->target_length > 0 ? min(100, ($session->total_items / $session->target_length) * 100) : 0;
    @endphp

    <div class="py-12">
        <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">

            <x-level-up-alert />

            {{-- Error message --}}
            @if (session('error'))
                <div role="alert" class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Status message --}}
            @if (session('status'))
                <div role="status" class="mb-4 rounded-lg border border-[#6D4AFF] bg-[#F0EDFF] p-4 text-[#4A2FC4]">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Progress information --}}
            <div class="mb-4 flex items-center justify-between text-sm text-gray-600">
                <span>
                    Question

                    <span class="font-semibold">
                        {{ $displayQuestionNumber }}
                    </span>

                    of {{ $session->target_length }}
                </span>

                <span>
                    Correct so far:

                    <span class="font-semibold text-green-700">
                        {{ $session->correct_items }}
                    </span>
                </span>
            </div>

            {{-- Progress bar --}}
            <div class="mb-6 h-2 w-full rounded-full bg-gray-200" role="progressbar" aria-label="Practice progress"
                aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ round($progress) }}">
                <div class="h-2 rounded-full bg-[#6D4AFF] transition-all" style="width: {{ $progress }}%"></div>
            </div>

            @if ($feedback)

                {{-- ============================================= --}}
                {{-- FEEDBACK VIEW                                 --}}
                {{-- ============================================= --}}

                @php
                    $isCorrect = (bool) $feedback['is_correct'];

                    $correctChoice = $answeredQuestion->choices->firstWhere(
                        'choice_id',
                        $feedback['correct_choice_id'],
                    );

                    if (!$correctChoice) {
                        $correctChoice = $answeredQuestion->choices->firstWhere('is_correct', true);
                    }
                @endphp

                <div class="mb-4 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="relative p-6 pr-16">

                        {{-- Feedback bookmark button --}}
                        <form method="POST"
                            action="{{ $isBookmarked
                                ? route('bookmarks.destroyQuestion', [
                                    'question' => $answeredQuestion->question_id,
                                ])
                                : route('bookmarks.store', [
                                    'question' => $answeredQuestion->question_id,
                                ]) }}"
                            class="absolute right-4 top-4">
                            @csrf
                            @method($isBookmarked ? 'DELETE' : 'PUT')

                            <input type="hidden" name="practice_session_id" value="{{ $session->session_id }}">

                            <button type="submit"
                                aria-label="{{ $isBookmarked ? 'Remove bookmark' : 'Bookmark this question' }}"
                                title="{{ $isBookmarked ? 'Remove bookmark' : 'Bookmark this question' }}"
                                class="text-3xl leading-none transition
                                    {{ $isBookmarked ? 'text-[#6D4AFF]' : 'text-gray-400 hover:text-[#6D4AFF]' }}">
                                <span aria-hidden="true">
                                    @if ($isBookmarked)
                                        &#9733;
                                    @else
                                        &#9734;
                                    @endif
                                </span>
                            </button>
                        </form>

                        {{-- Question metadata --}}
                        <div class="mb-1 text-xs uppercase tracking-wide text-[#0EA5A4]">
                            {{ $answeredQuestion->competency->domain->domain_name }}
                        </div>

                        <div class="mb-3 text-xs text-gray-500">
                            {{ $answeredQuestion->competency->title }}
                        </div>

                        <div class="mb-4 text-lg font-medium">
                            {{ $answeredQuestion->question_text }}
                        </div>

                        {{-- Correct/incorrect feedback --}}
                        @if ($isCorrect)
                            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4">
                                <p class="font-medium text-green-800">
                                    Correct
                                </p>

                                @if ($correctChoice)
                                    <p class="mt-1 text-sm text-green-700">
                                        <strong>
                                            {{ $correctChoice->choice_letter }}.
                                        </strong>

                                        {{ $correctChoice->choice_text }}
                                    </p>
                                @endif
                            </div>
                        @else
                            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4">
                                <p class="font-medium text-red-800">
                                    Incorrect
                                </p>

                                @if ($correctChoice)
                                    <p class="mt-1 text-sm text-red-700">
                                        Correct answer:

                                        <strong>
                                            {{ $correctChoice->choice_letter }}.
                                        </strong>

                                        {{ $correctChoice->choice_text }}
                                    </p>
                                @endif
                            </div>
                        @endif

                        {{-- Explanation --}}
                        @if ($answeredQuestion->hypercorrection_rationale)
                            <div class="mb-4 rounded-lg border border-[#0EA5A4]/30 bg-teal-50 p-4">
                                <p class="text-sm text-teal-900">
                                    <strong>
                                        Why:
                                    </strong>

                                    {{ $answeredQuestion->hypercorrection_rationale }}
                                </p>
                            </div>
                        @endif

                        {{-- Answer statistics --}}
                        <div class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-3">
                            <div>
                                <div class="text-gray-500">
                                    XP earned
                                </div>

                                <div class="font-semibold">
                                    +{{ $feedback['answer_xp'] }}

                                    @if ($feedback['is_speed_flagged'])
                                        <span class="mt-1 block text-xs text-[#F0524F]">
                                            Speed-flagged
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div>
                                <div class="text-gray-500">
                                    Mastery before
                                </div>

                                <div class="font-semibold">
                                    {{ number_format($feedback['prior_mastery'] * 100, 1) }}%
                                </div>
                            </div>

                            <div>
                                <div class="text-gray-500">
                                    Mastery after
                                </div>

                                <div class="font-semibold">
                                    {{ number_format($feedback['posterior_mastery'] * 100, 1) }}%
                                </div>
                            </div>
                        </div>

                        {{-- Speed warning --}}
                        @if ($feedback['is_speed_flagged'])
                            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-3">
                                <p class="text-xs text-red-800">
                                    This response was submitted faster
                                    than the CitiRx minimum-time
                                    heuristic.

                                    Response:
                                    {{ number_format($feedback['response_seconds'], 2) }}s.

                                    Minimum:
                                    {{ number_format($feedback['minimum_seconds'], 2) }}s.

                                    No answer XP was awarded, and this
                                    response is excluded from eligible
                                    accuracy and streak calculations.
                                </p>
                            </div>
                        @endif

                    </div>
                </div>

                {{-- Continue to next question --}}
                <form method="POST"
                    action="{{ route('practice.next', [
                        'session' => $session->session_id,
                    ]) }}">
                    @csrf

                    <x-primary-button>
                        Next Question
                    </x-primary-button>
                </form>
            @else
                {{-- ============================================= --}}
                {{-- QUESTION VIEW                                 --}}
                {{-- ============================================= --}}

                @if (!$question)
                    <div class="rounded-lg bg-white p-6 text-center shadow-sm">
                        <p class="text-gray-600">
                            No Practice question is available right
                            now.
                        </p>

                        <a href="{{ route('practice.intro') }}"
                            class="mt-4 inline-block rounded bg-[#6D4AFF] px-4 py-2 text-white transition hover:bg-[#4A2FC4]">
                            Back to Practice Setup
                        </a>
                    </div>
                @else
                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div class="relative p-6 pr-16">

                            {{-- Question bookmark button --}}
                            <form method="POST"
                                action="{{ $isBookmarked
                                    ? route('bookmarks.destroyQuestion', [
                                        'question' => $question->question_id,
                                    ])
                                    : route('bookmarks.store', [
                                        'question' => $question->question_id,
                                    ]) }}"
                                class="absolute right-4 top-4">
                                @csrf
                                @method($isBookmarked ? 'DELETE' : 'PUT')

                                <input type="hidden" name="practice_session_id" value="{{ $session->session_id }}">

                                <button type="submit"
                                    aria-label="{{ $isBookmarked ? 'Remove bookmark' : 'Bookmark this question' }}"
                                    title="{{ $isBookmarked ? 'Remove bookmark' : 'Bookmark this question' }}"
                                    class="text-3xl leading-none transition
                                        {{ $isBookmarked ? 'text-[#6D4AFF]' : 'text-gray-400 hover:text-[#6D4AFF]' }}">
                                    <span aria-hidden="true">
                                        @if ($isBookmarked)
                                            &#9733;
                                        @else
                                            &#9734;
                                        @endif
                                    </span>
                                </button>
                            </form>

                            {{-- Question metadata --}}
                            <div class="mb-1 text-xs uppercase tracking-wide text-[#0EA5A4]">
                                {{ $question->competency->domain->domain_name }}
                            </div>

                            <div class="mb-4 text-xs text-gray-500">
                                {{ $question->competency->title }}
                            </div>

                            <div class="mb-6 text-lg font-medium">
                                {{ $question->question_text }}
                            </div>

                            {{-- Answer form --}}
                            <form method="POST"
                                action="{{ route('practice.answer', [
                                    'session' => $session->session_id,
                                ]) }}">
                                @csrf

                                <input type="hidden" name="question_id" value="{{ $question->question_id }}">

                                @foreach ($question->choices as $choice)
                                    <label
                                        class="mb-2 flex cursor-pointer items-start rounded-lg border p-3 transition hover:bg-gray-50 focus-within:border-[#6D4AFF] focus-within:ring-2 focus-within:ring-[#6D4AFF]/20">
                                        <input type="radio" name="selected_choice_id"
                                            value="{{ $choice->choice_id }}" @checked(old('selected_choice_id') === $choice->choice_id)
                                            class="mr-3 mt-1 text-[#6D4AFF] focus:ring-[#6D4AFF]" required>

                                        <span>
                                            <span class="mr-2 font-semibold">
                                                {{ $choice->choice_letter }}.
                                            </span>

                                            {{ $choice->choice_text }}
                                        </span>
                                    </label>
                                @endforeach

                                @error('selected_choice_id')
                                    <p class="mt-2 text-sm text-[#F0524F]">
                                        {{ $message }}
                                    </p>
                                @enderror

                                <div class="mt-6">
                                    <x-primary-button>
                                        Submit Answer
                                    </x-primary-button>
                                </div>
                            </form>

                        </div>
                    </div>
                @endif

            @endif

            <div class="mt-6 text-center text-xs text-gray-500">
                Session ID:
                {{ $session->session_id }}
            </div>

        </div>
    </div>
</x-app-layout>
