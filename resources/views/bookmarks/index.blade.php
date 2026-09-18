<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Bookmarks') }}
        </h2>
    </x-slot>

    <div class="py-12">

        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- ======================================== --}}
            {{-- STATUS                                   --}}
            {{-- ======================================== --}}
            @if (session('status'))
                <div role="status" class="rounded-lg border border-[#6D4AFF] bg-[#F0EDFF] p-4 text-[#4A2FC4]">
                    {{ session('status') }}
                </div>
            @endif

            {{-- ======================================== --}}
            {{-- SUMMARY                                  --}}
            {{-- ======================================== --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">

                <div class="p-6 text-center">

                    <div class="text-sm text-gray-500 uppercase tracking-wide">
                        Bookmarked Questions
                    </div>

                    <div class="mt-2 text-4xl font-bold text-[#6D4AFF]">
                        {{ $total }}
                    </div>

                    <p class="mt-2 text-sm text-gray-600">
                        Star questions during Practice to save them for later review.
                    </p>

                </div>

            </div>

            {{-- ======================================== --}}
            {{-- EMPTY STATE                              --}}
            {{-- ======================================== --}}
            @if ($grouped->isEmpty())

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">

                    <div class="p-12 text-center">

                        <div class="text-5xl mb-3">
                            ☆
                        </div>

                        <h3 class="text-lg font-semibold text-gray-900">
                            No bookmarks yet
                        </h3>

                        <p class="mt-2 text-sm text-gray-600">
                            Click the star icon on a Practice question
                            to save it here.
                        </p>

                    </div>

                </div>
            @else
                {{-- ==================================== --}}
                {{-- DOMAIN GROUPS                        --}}
                {{-- ==================================== --}}
                @foreach ($grouped as $group)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">

                        {{-- Domain --}}
                        <div class="border-b border-gray-100 px-6 py-4">

                            <span
                                class="inline-flex rounded-full bg-teal-50 px-3 py-1 text-xs font-semibold text-teal-700">
                                {{ $group['domain_name'] }}
                            </span>

                            <p class="mt-2 text-xs text-gray-500">

                                {{ $group['bookmarks']->count() }}

                                {{ \Illuminate\Support\Str::plural('bookmark', $group['bookmarks']->count()) }}

                            </p>

                        </div>

                        <div class="divide-y divide-gray-100">

                            @foreach ($group['bookmarks'] as $bookmark)
                                @php
                                    $q = $bookmark->question;

                                    $correctChoice = $q->correctChoice;
                                @endphp

                                <div class="p-6">

                                    {{-- Question --}}
                                    <div class="flex items-start justify-between gap-4">

                                        <div class="flex-1">

                                            {{-- Competency --}}
                                            <div class="text-xs font-medium text-teal-700 mb-2">
                                                {{ $q->competency->title }}
                                            </div>

                                            {{-- Question --}}
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $q->question_text }}
                                            </div>

                                            {{-- Correct answer --}}
                                            @if ($correctChoice)
                                                <div class="mt-3 text-sm text-green-700">

                                                    <strong>
                                                        Correct answer:
                                                    </strong>

                                                    {{ $correctChoice->choice_letter }}.

                                                    {{ $correctChoice->choice_text }}

                                                </div>
                                            @endif

                                            {{-- Rationale --}}
                                            @if ($q->hypercorrection_rationale)
                                                <div class="mt-3 rounded-lg bg-gray-50 p-3 text-sm text-gray-600">

                                                    <strong>
                                                        Rationale:
                                                    </strong>

                                                    {{ $q->hypercorrection_rationale }}

                                                </div>
                                            @endif

                                        </div>

                                        {{-- Remove bookmark --}}
                                        <form method="POST"
                                            action="{{ route('bookmarks.destroy', [
                                                'bookmark' => $bookmark->bookmark_id,
                                            ]) }}"
                                            class="shrink-0">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit"
                                                class="text-sm font-medium text-[#F0524F] hover:text-red-700">
                                                Remove
                                            </button>

                                        </form>

                                    </div>

                                    {{-- ============================== --}}
                                    {{-- PERSONAL NOTES                 --}}
                                    {{-- ============================== --}}
                                    <form method="POST"
                                        action="{{ route('bookmarks.updateNotes', [
                                            'bookmark' => $bookmark->bookmark_id,
                                        ]) }}"
                                        class="mt-5">
                                        @csrf
                                        @method('PATCH')

                                        <input type="hidden" name="bookmark_id" value="{{ $bookmark->bookmark_id }}">

                                        <label for="notes-{{ $bookmark->bookmark_id }}"
                                            class="block text-xs font-medium text-gray-600 mb-1">
                                            Personal notes
                                        </label>

                                        <textarea id="notes-{{ $bookmark->bookmark_id }}" name="personal_notes" rows="3" maxlength="2000"
                                            class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#6D4AFF] focus:ring-[#6D4AFF]"
                                            placeholder="Write a note to yourself...">{{ old('bookmark_id') === $bookmark->bookmark_id ? old('personal_notes') : $bookmark->personal_notes }}</textarea>

                                        @if (old('bookmark_id') === $bookmark->bookmark_id)
                                            @error('personal_notes')
                                                <p class="mt-1 text-sm text-[#F0524F]">
                                                    {{ $message }}
                                                </p>
                                            @enderror
                                        @endif

                                        <div class="mt-2">

                                            <button type="submit"
                                                class="rounded-md bg-[#6D4AFF] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#4A2FC4]">
                                                Save Notes
                                            </button>

                                        </div>

                                    </form>

                                </div>
                            @endforeach

                        </div>

                    </div>
                @endforeach

            @endif

            {{-- ======================================== --}}
            {{-- ACTIONS                                  --}}
            {{-- ======================================== --}}
            <div class="flex flex-wrap justify-center gap-3">

                <a href="{{ route('practice.intro') }}"
                    class="inline-flex items-center justify-center rounded-md bg-[#6D4AFF] px-6 py-3 font-semibold text-white transition hover:bg-[#4A2FC4]">
                    Practice Now
                </a>

                <a href="{{ route('dashboard') }}"
                    class="inline-flex items-center justify-center rounded-md bg-gray-200 px-6 py-3 font-semibold text-gray-800 transition hover:bg-gray-300">
                    Back to Dashboard
                </a>

            </div>

        </div>

    </div>

</x-app-layout>
