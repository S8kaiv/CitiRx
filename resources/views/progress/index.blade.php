<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Level Progress') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">

            {{-- Current status --}}
            <section class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-center">

                    <p class="text-sm font-semibold uppercase tracking-wide text-gray-500">
                        Current Level
                    </p>

                    <p class="mt-3 text-5xl font-bold text-[#6D4AFF]">
                        Level {{ $currentLevel->level_number }}
                    </p>

                    <span class="mt-3 inline-flex rounded-full bg-teal-50 px-4 py-1 text-sm font-semibold text-teal-700">
                        {{ $currentLevel->tier->tier_name }} Tier
                    </span>

                    <p class="mt-4 text-lg font-semibold text-gray-800">
                        {{ number_format((int) $user->total_xp) }} XP
                    </p>

                    @if ($nextLevel)
                        <div class="mx-auto mt-6 max-w-md">

                            <div class="mb-2 flex justify-between text-xs font-medium text-gray-500">
                                <span>
                                    Level {{ $currentLevel->level_number }}
                                </span>

                                <span>
                                    Level {{ $nextLevel->level_number }}
                                </span>
                            </div>

                            <div
                                role="progressbar"
                                aria-label="Progress toward Level {{ $nextLevel->level_number }}"
                                aria-valuemin="0"
                                aria-valuemax="100"
                                aria-valuenow="{{ $progressPercent }}"
                                class="h-3 overflow-hidden rounded-full bg-gray-200"
                            >
                                <div
                                    class="h-full rounded-full bg-gradient-to-r from-[#6D4AFF] to-[#0EA5A4] transition-all"
                                    style="width: {{ $progressPercent }}%"
                                ></div>
                            </div>

                            <p class="mt-3 text-sm text-gray-600">
                                <strong>
                                    {{ number_format($xpToNext) }} XP
                                </strong>
                                needed for Level
                                {{ $nextLevel->level_number }}
                                &mdash;
                                {{ $nextLevel->tier->tier_name }}
                            </p>

                            <p class="mt-1 text-xs text-gray-500">
                                {{ number_format($progressPercent, 1) }}%
                                of this level completed
                            </p>
                        </div>
                    @else
                        <p class="mt-6 font-semibold text-teal-700">
                            Maximum level reached.
                        </p>

                        <p class="mt-1 text-sm text-gray-600">
                            Keep practicing to strengthen your mastery
                            and board readiness.
                        </p>
                    @endif

                </div>
            </section>

            {{-- Level ladder --}}
            <section class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">

                    <h3 class="text-lg font-semibold text-gray-900">
                        Level Ladder
                    </h3>

                    <p class="mt-1 text-sm text-gray-600">
                        Earn XP through Practice Mode and achievements.
                    </p>

                    <div class="mt-5 space-y-2">
                        @foreach ($levels as $level)
                            @php
                                $isCurrent =
                                    (int) $level->level_number
                                    === (int) $currentLevel->level_number;

                                $isReached =
                                    (int) $level->min_xp
                                    <= (int) $user->total_xp;
                            @endphp

                            <div
                                @class([
                                    'flex items-center justify-between rounded-lg border px-4 py-3',
                                    'border-[#6D4AFF] bg-[#6D4AFF]/10' => $isCurrent,
                                    'border-teal-100 bg-teal-50' => $isReached && ! $isCurrent,
                                    'border-gray-100 bg-gray-50 opacity-60' => ! $isReached,
                                ])
                            >
                                <div class="flex items-center gap-3">

                                    <span
                                        class="w-6 text-center text-lg"
                                        aria-hidden="true"
                                    >
                                        @if ($isCurrent)
                                            &#127919;
                                        @elseif ($isReached)
                                            &#10003;
                                        @else
                                            &#128274;
                                        @endif
                                    </span>

                                    <div>
                                        <p class="font-semibold text-gray-900">
                                            Level {{ $level->level_number }}
                                        </p>

                                        <p class="text-xs text-gray-500">
                                            {{ $level->tier->tier_name }}
                                        </p>
                                    </div>
                                </div>

                                <p class="text-sm font-medium text-gray-600">
                                    {{ number_format((int) $level->min_xp) }}
                                    XP
                                </p>
                            </div>
                        @endforeach
                    </div>

                </div>
            </section>

            <div class="flex flex-wrap justify-center gap-3">
                <a
                    href="{{ route('practice.intro') }}"
                    class="inline-flex items-center justify-center rounded-md bg-[#6D4AFF] px-6 py-3 font-semibold text-white transition hover:bg-[#4A2FC4]"
                >
                    Earn More XP
                </a>

                <a
                    href="{{ route('dashboard') }}"
                    class="inline-flex items-center justify-center rounded-md bg-gray-200 px-6 py-3 font-semibold text-gray-800 transition hover:bg-gray-300"
                >
                    Back to Dashboard
                </a>
            </div>

        </div>
    </div>
</x-app-layout>