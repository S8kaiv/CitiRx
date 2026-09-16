<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Board Readiness') }}
        </h2>
    </x-slot>

    @php
        $band = $breakdown['band'];

        $bandColor = match ($band) {
            'board_ready' => 'text-green-600',
            'approaching' => 'text-blue-600',
            'developing'  => 'text-amber-600',
            default       => 'text-red-600',
        };

        $bandLabel = match ($band) {
            'board_ready' => 'Board Ready',
            'approaching' => 'Approaching',
            'developing'  => 'Developing',
            default       => 'At Risk',
        };
    @endphp

    <div class="py-12">

        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Overall readiness --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">

                <div class="p-6 text-center">

                    <div class="text-sm text-gray-500 uppercase tracking-wide">
                        Board Readiness Estimate
                    </div>

                    <div class="mt-2 text-6xl font-bold {{ $bandColor }}">
                        {{ number_format($breakdown['total'], 2) }}%
                    </div>

                    <div class="mt-2 text-sm font-semibold {{ $bandColor }}">
                        {{ $bandLabel }}
                    </div>

                    <p class="mt-3 text-xs text-gray-500 max-w-xl mx-auto">
                        This estimate is based on your competency mastery
                        levels weighted according to the current PRC
                        Table of Specifications distribution.
                    </p>

                </div>

            </div>


            {{-- Domain breakdown --}}
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
                                        Domain Mastery
                                    </th>

                                    <th class="py-2 text-right">
                                        PRC Weight
                                    </th>

                                    <th class="py-2 text-right">
                                        Contribution
                                    </th>

                                </tr>

                            </thead>

                            <tbody>

                                @foreach ($breakdown['domains'] as $domain)

                                    <tr class="border-b align-top">

                                        <td class="py-3">

                                            <div class="font-medium">
                                                {{ $domain['domain_name'] }}
                                            </div>

                                            <ul class="mt-1 text-xs text-gray-500 space-y-0.5">

                                                @foreach ($domain['competencies'] as $competency)

                                                    <li>
                                                        {{ $competency['title'] }}
                                                        —
                                                        {{
                                                            number_format(
                                                                $competency['mastery'] * 100,
                                                                1
                                                            )
                                                        }}%
                                                    </li>

                                                @endforeach

                                            </ul>

                                        </td>

                                        <td class="py-3 text-right font-medium">
                                            {{
                                                number_format(
                                                    $domain['mastery'] * 100,
                                                    2
                                                )
                                            }}%
                                        </td>

                                        <td class="py-3 text-right text-gray-600">
                                            {{
                                                number_format(
                                                    $domain['weight'],
                                                    2
                                                )
                                            }}%
                                        </td>

                                        <td class="py-3 text-right font-semibold">
                                            {{
                                                number_format(
                                                    $domain['contribution'],
                                                    2
                                                )
                                            }}%
                                        </td>

                                    </tr>

                                @endforeach


                                {{-- Total --}}
                                <tr class="border-t-2 border-gray-300 font-bold">

                                    <td
                                        class="py-3 text-right"
                                        colspan="3"
                                    >
                                        Board Readiness Estimate
                                    </td>

                                    <td class="py-3 text-right {{ $bandColor }}">
                                        {{
                                            number_format(
                                                $breakdown['total'],
                                                2
                                            )
                                        }}%
                                    </td>

                                </tr>

                            </tbody>

                        </table>

                    </div>

                    <p class="mt-4 text-xs text-gray-500">
                        Each domain contribution is calculated as
                        (domain mastery × PRC weight) ÷ total PRC
                        weight × 100.
                    </p>

                    <p class="mt-2 text-xs text-gray-500">
                        Readiness bands are application display
                        categories and are not official PhLE
                        passing-score classifications.
                    </p>

                </div>

            </div>


            {{-- Back --}}
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