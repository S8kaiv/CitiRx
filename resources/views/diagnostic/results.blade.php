<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Diagnostic Results') }}
        </h2>
    </x-slot>

    <div class="py-12">

        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Overall score --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">

                <div class="p-6 text-center">

                    <div class="text-5xl font-bold text-indigo-600">
                        {{ $session->correct_items }}
                        /
                        {{ $session->total_items }}
                    </div>

                    <div class="mt-2 text-sm text-gray-600">
                        Correct answers
                    </div>

                    @if ($session->total_items > 0)

                        <div class="mt-2 text-lg font-semibold text-gray-700">
                            {{
                                round(
                                    100
                                    * $session->correct_items
                                    / $session->total_items
                                )
                            }}%
                        </div>

                    @endif

                    @if ($session->completed_at)

                        <div class="mt-2 text-xs text-gray-500">
                            Completed
                            {{ $session->completed_at->diffForHumans() }}
                        </div>

                    @endif

                </div>

            </div>

            {{-- Domain results --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">

                <div class="p-6">

                    <h3 class="text-lg font-medium mb-4">
                        Performance by Domain
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
                                        %
                                    </th>

                                </tr>

                            </thead>

                            <tbody>

                                @foreach ($perDomain as $domain)

                                    @php
                                        $percentage =
                                            $domain['total'] > 0
                                                ? round(
                                                    100
                                                    * $domain['correct']
                                                    / $domain['total']
                                                )
                                                : 0;
                                    @endphp

                                    <tr class="border-b">

                                        <td class="py-3">
                                            {{ $domain['domain_name'] }}
                                        </td>

                                        <td class="py-3 text-right">
                                            {{ $domain['correct'] }}
                                            /
                                            {{ $domain['total'] }}
                                        </td>

                                        <td class="py-3 text-right font-semibold">
                                            {{ $percentage }}%
                                        </td>

                                    </tr>

                                @endforeach

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

            {{-- Initial mastery --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">

                <div class="p-6">

                    <h3 class="text-lg font-medium mb-2">
                        Your Initial Mastery Estimates
                    </h3>

                    <p class="text-sm text-gray-600 mb-4">
                        These values were established from your
                        diagnostic performance. They form the
                        starting point of the Knowledge Tracing
                        model and will change as you practice.
                    </p>

                    <ul class="space-y-2 text-sm">

                        @foreach ($masteryResults as $result)

                            @php
                                $masteryPercentage =
                                    round(
                                        100
                                        * $result['mastery']
                                    );
                            @endphp

                            <li class="flex justify-between items-center border-b py-3">

                                <div>

                                    <div class="font-medium">
                                        {{ $result['competency_title'] }}
                                    </div>

                                    <div class="text-xs text-gray-500">
                                        {{ $result['domain_name'] }}
                                    </div>

                                </div>

                                <div class="text-right">

                                    <div class="font-semibold">
                                        {{ $masteryPercentage }}%
                                    </div>

                                    <div class="text-xs text-gray-500">
                                        {{ $result['correct'] }}
                                        /
                                        {{ $result['total'] }}
                                        correct
                                    </div>

                                </div>

                            </li>

                        @endforeach

                    </ul>

                </div>

            </div>

            {{-- Continue --}}
            <div class="text-center">

                <a
                    href="{{ route('dashboard') }}"
                    class="inline-block px-6 py-3 bg-indigo-600 text-white rounded hover:bg-indigo-700"
                >
                    Back to Dashboard
                </a>

            </div>

        </div>

    </div>

</x-app-layout>