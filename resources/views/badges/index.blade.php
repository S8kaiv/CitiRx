<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Badges') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Summary --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-center">

                    <div class="text-sm text-gray-500 uppercase tracking-wide">
                        Badges Unlocked
                    </div>

                    <div class="mt-2 text-4xl font-bold text-indigo-600">
                        {{ $userBadges->count() }}

                        <span class="text-2xl text-gray-400">
                            /
                            {{ $badges->count() }}
                        </span>
                    </div>

                    <div class="mt-2 text-sm text-gray-600">
                        Total XP earned from badges:

                        <strong>
                            +{{ $badgeXpTotal }}
                        </strong>
                    </div>

                </div>
            </div>

            {{-- Badge grid --}}
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">

                @foreach ($badges as $badge)
                    @php
                        $userBadge =
                            $userBadges->get(
                                $badge->badge_id
                            );

                        $isUnlocked =
                            $userBadge !== null;
                    @endphp

                    <div
                        class="overflow-hidden rounded-lg border shadow-sm
                            {{
                                $isUnlocked
                                    ? 'border-indigo-200 bg-white'
                                    : 'border-gray-200 bg-gray-50 opacity-70'
                            }}"
                    >
                        <div class="p-5">

                            <div class="flex items-start justify-between">

                                <div class="text-3xl">
                                    {{ $isUnlocked ? '🏆' : '🔒' }}
                                </div>

                                <div
                                    class="text-right text-xs font-semibold
                                        {{
                                            $isUnlocked
                                                ? 'text-indigo-600'
                                                : 'text-gray-400'
                                        }}"
                                >
                                    +{{ $badge->xp_reward }} XP
                                </div>

                            </div>

                            <h3 class="mt-3 font-semibold text-gray-900">
                                {{ $badge->badge_name }}
                            </h3>

                            <p class="mt-1 text-sm text-gray-600">
                                {{ $badge->description }}
                            </p>

                            @if ($isUnlocked)

                                <p class="mt-3 text-xs text-indigo-600 font-medium">
                                    Unlocked
                                    {{ $userBadge->unlocked_at->diffForHumans() }}
                                </p>

                            @else

                                <p class="mt-3 text-xs text-gray-400">
                                    Not yet unlocked
                                </p>

                            @endif

                        </div>
                    </div>

                @endforeach

            </div>

            <div class="text-center">

                <a
                    href="{{ route('dashboard') }}"
                    class="inline-block px-6 py-3 bg-gray-200 text-gray-800 rounded hover:bg-gray-300"
                >
                    Back to Dashboard
                </a>

            </div>

        </div>
    </div>
</x-app-layout>