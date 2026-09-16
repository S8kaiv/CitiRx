<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
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
    @endphp

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">

            @if (session('error'))
                <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-lg">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Progress --}}
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
            @php
                $progress =
                    $session->target_length > 0 ? min(100, ($session->total_items / $session->target_length) * 100) : 0;
            @endphp

            <div class="w-full bg-gray-200 rounded-full h-2 mb-6">
                <div class="bg-indigo-600 h-2 rounded-full transition-all" style="width: {{ $progress }}%"></div>
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

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-4">
                    <div class="p-6">

                        <div class="text-xs uppercase tracking-wide text-gray-500 mb-1">
                            {{ $answeredQuestion->competency->domain->domain_name }}
                        </div>

                        <div class="text-xs text-gray-500 mb-3">
                            {{ $answeredQuestion->competency->title }}
                        </div>

                        <div class="text-lg font-medium mb-4">
                            {{ $answeredQuestion->question_text }}
                        </div>

                        @if ($isCorrect)
                            <div class="p-4 bg-green-50 border border-green-200 rounded-lg mb-4">
                                <p class="text-green-800 font-medium">
                                    Correct
                                </p>

                                @if ($correctChoice)
                                    <p class="text-sm text-green-700 mt-1">
                                        <strong>
                                            {{ $correctChoice->choice_letter }}.
                                        </strong>

                                        {{ $correctChoice->choice_text }}
                                    </p>
                                @endif
                            </div>
                        @else
                            <div class="p-4 bg-red-50 border border-red-200 rounded-lg mb-4">
                                <p class="text-red-800 font-medium">
                                    Incorrect
                                </p>

                                @if ($correctChoice)
                                    <p class="text-sm text-red-700 mt-1">
                                        Correct answer:

                                        <strong>
                                            {{ $correctChoice->choice_letter }}.
                                        </strong>

                                        {{ $correctChoice->choice_text }}
                                    </p>
                                @endif
                            </div>
                        @endif

                        @if ($answeredQuestion->hypercorrection_rationale)
                            <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg mb-4">
                                <p class="text-sm text-blue-900">
                                    <strong>
                                        Why:
                                    </strong>

                                    {{ $answeredQuestion->hypercorrection_rationale }}
                                </p>
                            </div>
                        @endif

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">

                            <div>
                                <div class="text-gray-500">
                                    XP earned
                                </div>

                                <div class="font-semibold">
                                    +{{ $feedback['answer_xp'] }}

                                    @if ($feedback['is_speed_flagged'])
                                        <span class="block text-xs text-amber-600 mt-1">
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

                        @if ($feedback['is_speed_flagged'])
                            <div class="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                                <p class="text-xs text-amber-800">
                                    This response was submitted faster
                                    than the CitiRx minimum-time heuristic.

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
                    <div class="bg-white rounded-lg shadow-sm p-6 text-center">
                        <p class="text-gray-600">
                            No Practice question is available right now.
                        </p>

                        <a href="{{ route('practice.intro') }}"
                            class="inline-block mt-4 px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                            Back to Practice Setup
                        </a>
                    </div>
                @else
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">

                            <div class="text-xs uppercase tracking-wide text-gray-500 mb-1">
                                {{ $question->competency->domain->domain_name }}
                            </div>

                            <div class="text-xs text-gray-500 mb-4">
                                {{ $question->competency->title }}
                            </div>

                            <div class="text-lg font-medium mb-6">
                                {{ $question->question_text }}
                            </div>

                            <form method="POST"
                                action="{{ route('practice.answer', [
                                    'session' => $session->session_id,
                                ]) }}">
                                @csrf

                                <input type="hidden" name="question_id" value="{{ $question->question_id }}">

                                @foreach ($question->choices as $choice)
                                    <label
                                        class="flex items-start p-3 mb-2 border rounded-lg cursor-pointer hover:bg-gray-50 transition">
                                        <input type="radio" name="selected_choice_id"
                                            value="{{ $choice->choice_id }}" @checked(old('selected_choice_id') === $choice->choice_id)
                                            class="mt-1 mr-3" required>

                                        <span>
                                            <span class="font-semibold mr-2">
                                                {{ $choice->choice_letter }}.
                                            </span>

                                            {{ $choice->choice_text }}
                                        </span>
                                    </label>
                                @endforeach

                                @error('selected_choice_id')
                                    <p class="mt-2 text-sm text-red-600">
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
