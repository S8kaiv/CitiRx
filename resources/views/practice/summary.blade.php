<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Practice Summary') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Main score --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-center">

                    <div class="text-sm text-gray-500 uppercase tracking-wide">
                        Session Score
                    </div>

                    <div class="mt-2 text-5xl font-bold text-indigo-600">
                        {{ $session->correct_items }}
                        /
                        {{ $session->total_items }}
                    </div>

                    <div class="mt-2 text-sm text-gray-600">
                        Raw accuracy:
                        <strong>
                            {{ number_format($rawAccuracy, 2) }}%
                        </strong>
                    </div>

                    <div class="mt-2 text-sm text-gray-600">
                        Eligible accuracy:
                        <strong>
                            {{ number_format($eligibleAccuracy, 2) }}%
                        </strong>

                        <span class="text-xs text-gray-500">
                            (
                            {{ $eligibleCorrect }}
                            /
                            {{ $eligibleCount }}
                            non-speed-flagged responses
                            )
                        </span>
                    </div>

                    <div class="mt-4 text-sm text-gray-700">
                        <strong>
                            XP earned this session:
                        </strong>

                        +{{ $session->xp_awarded }}
                    </div>

                </div>
            </div>

            {{-- Per-domain breakdown --}}
            @if (count($perDomain) > 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">

                        <h3 class="text-lg font-medium mb-4">
                            Breakdown by Domain
                        </h3>

                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">

                                <thead>
                                    <tr class="text-left text-gray-600 border-b">
                                        <th class="py-2">
                                            Domain
                                        </th>

                                        <th class="py-2 text-right">
                                            Correct
                                        </th>

                                        <th class="py-2 text-right">
                                            Raw %
                                        </th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($perDomain as $domain)
                                        @php
                                            $percentage =
                                                $domain['total'] > 0
                                                    ? round((100 * $domain['correct']) / $domain['total'])
                                                    : 0;
                                        @endphp

                                        <tr class="border-b">
                                            <td class="py-2">
                                                {{ $domain['domain_name'] }}
                                            </td>

                                            <td class="py-2 text-right">
                                                {{ $domain['correct'] }}
                                                /
                                                {{ $domain['total'] }}
                                            </td>

                                            <td class="py-2 text-right font-semibold">
                                                {{ $percentage }}%
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>

                            </table>
                        </div>

                    </div>
                </div>
            @endif

            {{-- Readiness --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-center">

                    <div class="text-sm text-gray-500 uppercase tracking-wide">
                        Current Board Readiness Estimate
                    </div>

                    <div class="mt-2 text-4xl font-bold text-indigo-600">
                        {{ number_format($currentReadiness, 2) }}%
                    </div>

                    <p class="mt-2 text-xs text-gray-500">
                        This estimate reflects your current competency
                        mastery after this Practice session.
                    </p>

                    <a href="{{ route('readiness.show') }}"
                        class="inline-block mt-4 px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 text-sm">
                        View Full Breakdown
                    </a>

                </div>
            </div>

            {{-- Actions --}}
            <div class="flex flex-wrap justify-center gap-4">

                <a href="{{ route('practice.intro') }}"
                    class="inline-block px-6 py-3 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                    Practice Again
                </a>

                <a href="{{ route('dashboard') }}"
                    class="inline-block px-6 py-3 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">
                    Back to Dashboard
                </a>

            </div>

        </div>
    </div>
</x-app-layout>
