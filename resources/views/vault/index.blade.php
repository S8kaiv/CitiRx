<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Rx Vault') }}
        </h2>
    </x-slot>

    <div class="py-12">

        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- ========================================= --}}
            {{-- SUMMARY                                   --}}
            {{-- ========================================= --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">

                <div class="p-6 text-center">

                    <div class="text-sm text-gray-500 uppercase tracking-wide">
                        Questions to Review
                    </div>

                    <div class="mt-2 text-4xl font-bold text-amber-600">
                        {{ $activeCount }}
                    </div>

                    <p class="mt-2 text-xs text-gray-500">
                        Cleared:
                        {{ $clearedCount }}
                    </p>

                    <p class="mt-3 text-sm text-gray-600 max-w-lg mx-auto">
                        Questions you miss during Practice are added here
                        automatically.

                        Answer a Vault question correctly

                        <strong>
                            {{ \App\Services\RxVaultService::CLEAR_THRESHOLD }}
                        </strong>

                        trusted times in a row to clear it.
                    </p>

                    <p class="mt-2 text-xs text-gray-500 max-w-lg mx-auto">
                        Speed-flagged correct answers do not count toward
                        clearing a Vault question.
                    </p>

                </div>

            </div>

            {{-- ========================================= --}}
            {{-- EMPTY STATE                               --}}
            {{-- ========================================= --}}
            @if ($grouped->isEmpty())

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">

                    <div class="p-12 text-center">

                        <div class="text-5xl mb-3">
                            ✨
                        </div>

                        <h3 class="text-lg font-semibold text-gray-900">
                            Vault is empty
                        </h3>

                        <p class="mt-2 text-sm text-gray-600">
                            You currently have no missed Practice questions
                            waiting for review.
                        </p>

                    </div>

                </div>

            @else

                {{-- ===================================== --}}
                {{-- GROUPED QUESTIONS                     --}}
                {{-- ===================================== --}}
                @foreach ($grouped as $group)

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">

                        <div class="border-b border-gray-100 px-6 py-4">

                            <h3 class="font-semibold text-gray-900">
                                {{ $group['domain_name'] }}
                            </h3>

                            <p class="text-xs text-gray-500 mt-1">

                                {{ $group['entries']->count() }}

                                {{ \Illuminate\Support\Str::plural(
                                    'question',
                                    $group['entries']->count()
                                ) }}

                                to review

                            </p>

                        </div>

                        <div class="divide-y divide-gray-100">

                            @foreach ($group['entries'] as $entry)

                                @php
                                    $q =
                                        $entry->question;

                                    $correctChoice =
                                        $q->correctChoice;
                                @endphp

                                <div class="p-6">

                                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

                                        {{-- Question information --}}
                                        <div class="flex-1">

                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $q->question_text }}
                                            </div>

                                            @if ($correctChoice)

                                                <div class="mt-3 text-sm text-green-700">

                                                    <strong>
                                                        Correct answer:
                                                    </strong>

                                                    {{ $correctChoice->choice_letter }}.

                                                    {{ $correctChoice->choice_text }}

                                                </div>

                                            @endif

                                            @if ($q->hypercorrection_rationale)

                                                <div class="mt-3 rounded bg-gray-50 p-3 text-sm text-gray-600">

                                                    <strong>
                                                        Rationale:
                                                    </strong>

                                                    {{ $q->hypercorrection_rationale }}

                                                </div>

                                            @endif

                                        </div>

                                        {{-- Progress --}}
                                        <div class="shrink-0 sm:text-right">

                                            <div class="text-xs text-gray-500">
                                                Clear Progress
                                            </div>

                                            <div class="mt-1 text-lg font-semibold text-amber-600">

                                                {{ $entry->consecutive_correct_count }}

                                                /

                                                {{ \App\Services\RxVaultService::CLEAR_THRESHOLD }}

                                            </div>

                                            <div class="mt-1 text-xs text-gray-400">

                                                Added

                                                {{ $entry->added_at->diffForHumans() }}

                                            </div>

                                        </div>

                                    </div>

                                </div>

                            @endforeach

                        </div>

                    </div>

                @endforeach

            @endif

            {{-- ========================================= --}}
            {{-- ACTIONS                                   --}}
            {{-- ========================================= --}}
            <div class="flex flex-wrap justify-center gap-3">

                <a
                    href="{{ route('practice.intro') }}"
                    class="inline-block rounded bg-amber-600 px-6 py-3 text-white hover:bg-amber-700 transition"
                >
                    Practice Now
                </a>

                <a
                    href="{{ route('dashboard') }}"
                    class="inline-block rounded bg-gray-200 px-6 py-3 text-gray-800 hover:bg-gray-300 transition"
                >
                    Back to Dashboard
                </a>

            </div>

        </div>

    </div>
</x-app-layout>