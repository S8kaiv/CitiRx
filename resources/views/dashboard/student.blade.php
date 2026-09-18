<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Student Dashboard') }}
        </h2>
    </x-slot>

    @php
        $readinessStyles = match ($readinessBand) {
            'board_ready' => [
                'label' => 'Board Ready',
                'text' => 'text-green-700',
                'background' => 'bg-green-50',
                'border' => 'border-green-200',
            ],

            'approaching' => [
                'label' => 'Approaching Readiness',
                'text' => 'text-blue-700',
                'background' => 'bg-blue-50',
                'border' => 'border-blue-200',
            ],

            'developing' => [
                'label' => 'Developing',
                'text' => 'text-yellow-700',
                'background' => 'bg-yellow-50',
                'border' => 'border-yellow-200',
            ],

            'at_risk' => [
                'label' => 'At Risk',
                'text' => 'text-red-700',
                'background' => 'bg-red-50',
                'border' => 'border-red-200',
            ],

            default => [
                'label' => 'Unavailable',
                'text' => 'text-gray-700',
                'background' => 'bg-gray-50',
                'border' => 'border-gray-200',
            ],
        };
    @endphp

    <div class="py-12">
        <div class="max-w-7xl mx-auto space-y-6 sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="rounded-lg bg-green-100 p-4 text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-lg bg-red-100 p-4 text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Student information --}}
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="text-lg">
                        Welcome back, {{ $user->fullName() }}.
                    </p>

                    <p class="mt-2 text-sm text-gray-600">
                        Role: <strong>Student</strong>
                    </p>

                    @if ($user->cohort)
                        <p class="text-sm text-gray-600">
                            Cohort:
                            {{ $user->cohort->cohort_name }}
                        </p>
                    @endif
                </div>
            </div>

            @if (!$user->is_diagnostic_completed)
                {{-- Diagnostic required --}}
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900">
                            Diagnostic Test
                        </h3>

                        <p class="mt-2 text-sm text-gray-600">
                            Complete the diagnostic test before starting
                            adaptive Practice Mode.
                        </p>

                        <div class="mt-5">
                            @if ($inProgressDiagnostic)
                                <a href="{{ route('diagnostic.take', [
                                    'session' => $inProgressDiagnostic->session_id,
                                ]) }}"
                                    class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                                    Resume Diagnostic
                                </a>
                            @else
                                <a href="{{ route('diagnostic.intro') }}"
                                    class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                                    Start Diagnostic
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @else
                {{-- Readiness --}}
                <div
                    class="overflow-hidden border shadow-sm sm:rounded-lg
                        {{ $readinessStyles['background'] }}
                        {{ $readinessStyles['border'] }}">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900">
                            Board Readiness Estimate
                        </h3>

                        @if ($user->predicted_readiness_pct !== null && $readinessBand !== null)
                            <div class="mt-3 flex flex-wrap items-end gap-3">
                                <span
                                    class="text-4xl font-bold
                                        {{ $readinessStyles['text'] }}">
                                    {{ number_format((float) $user->predicted_readiness_pct, 2) }}%
                                </span>

                                <span
                                    class="pb-1 text-sm font-semibold
                                        {{ $readinessStyles['text'] }}">
                                    {{ $readinessStyles['label'] }}
                                </span>
                            </div>

                            <a href="{{ route('readiness.show') }}"
                                class="mt-5 inline-flex text-sm font-semibold text-indigo-600 hover:text-indigo-800">
                                View readiness breakdown
                            </a>
                        @else
                            <p class="mt-3 text-sm text-gray-600">
                                Your diagnostic is complete, but your
                                readiness estimate is currently unavailable.
                            </p>
                        @endif
                    </div>
                </div>

                {{-- Badges --}}
                <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">

                    <div class="p-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">
                                Achievements
                            </h3>

                            <p class="mt-1 text-sm text-gray-600">
                                {{ $unlockedBadgeCount }}
                                of
                                {{ $totalBadgeCount }}
                                badges unlocked
                            </p>
                        </div>

                        <a href="{{ route('badges.index') }}"
                            class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 transition">
                            View Badges
                        </a>

                    </div>

                </div>

                {{-- RxVault --}}
                <div class="mt-6 overflow-hidden bg-white shadow-sm sm:rounded-lg">

                    <div class="p-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                        <div>

                            <h3 class="text-lg font-semibold text-gray-900">
                                Rx Vault
                            </h3>

                            <p class="mt-1 text-sm text-gray-600">

                                @if ($activeVaultCount > 0)
                                    {{ $activeVaultCount }}

                                    {{ \Illuminate\Support\Str::plural('question', $activeVaultCount) }}

                                    to review from Practice.
                                @else
                                    No questions to review right now.
                                @endif

                            </p>

                        </div>

                        <a href="{{ route('vault.index') }}"
                            class="inline-flex items-center justify-center rounded-md bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700 transition">
                            Open Vault
                        </a>

                    </div>

                </div>

                {{-- =============================================== --}}
                {{-- BOOKMARKS                                       --}}
                {{-- =============================================== --}}
                <div class="mt-6 overflow-hidden bg-white shadow-sm sm:rounded-lg">

                    <div class="p-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                        <div>

                            <h3 class="text-lg font-semibold text-gray-900">
                                Bookmarks
                            </h3>

                            <p class="mt-1 text-sm text-gray-600">

                                @if ($bookmarkCount > 0)
                                    {{ $bookmarkCount }}

                                    {{ \Illuminate\Support\Str::plural('question', $bookmarkCount) }}

                                    starred for later review.
                                @else
                                    No bookmarked questions yet.
                                @endif

                            </p>

                        </div>

                        <a href="{{ route('bookmarks.index') }}"
                            class="inline-flex items-center justify-center rounded-md bg-[#6D4AFF] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#4A2FC4]">
                            Open Bookmarks
                        </a>

                    </div>

                </div>

                {{-- Level and XP --}}
                <div class="mt-6 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="flex flex-col gap-4 p-6 sm:flex-row sm:items-center sm:justify-between">

                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">
                                Level &amp; Tier
                            </h3>

                            <p class="mt-1 text-sm text-gray-600">
                                <strong class="text-[#6D4AFF]">
                                    Level {{ $user->current_level ?? 1 }}
                                </strong>

                                @if ($user->level?->tier)
                                    &mdash;
                                    {{ $user->level->tier->tier_name }}
                                @endif

                                &middot;
                                {{ number_format((int) $user->total_xp) }} XP
                            </p>
                        </div>

                        <a href="{{ route('progress.index') }}"
                            class="inline-flex items-center justify-center rounded-md bg-[#6D4AFF] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#4A2FC4]">
                            View Progress
                        </a>

                    </div>
                </div>

                {{-- Practice Mode --}}
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900">
                            Practice Mode
                        </h3>

                        <p class="mt-2 text-sm text-gray-600">
                            Practice questions are selected using your current
                            mastery levels and updated as you progress.
                        </p>

                        <div class="mt-5">
                            @if ($inProgressPractice)
                                <a href="{{ route('practice.show', [
                                    'session' => $inProgressPractice->session_id,
                                ]) }}"
                                    class="inline-flex items-center rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">
                                    Resume Practice
                                </a>
                            @else
                                <a href="{{ route('practice.intro') }}"
                                    class="inline-flex items-center rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">
                                    Start Practice
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
