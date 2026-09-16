<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Diagnostic Test') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">

            @if (session('error'))
                <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-lg">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    <h3 class="text-lg font-medium mb-4">
                        Establish your baseline
                    </h3>

                    <p class="mb-4">
                        This diagnostic covers all six PhLE
                        Table of Specifications domains.
                        Your answers will establish an initial
                        mastery estimate for each competency
                        and help personalize your review.
                    </p>

                    <ul class="list-disc list-inside mb-6 text-sm text-gray-600 space-y-1">
                        <li>
                            Up to 10 questions per domain
                            (60 total when the question bank is complete)
                        </li>

                        <li>
                            No time limit — answer at your own pace
                        </li>

                        <li>
                            Your question set can be resumed later,
                            but answers are saved only when you submit
                        </li>

                        <li>
                            You can only complete the diagnostic once
                        </li>
                    </ul>

                    <form
                        method="POST"
                        action="{{ route('diagnostic.start') }}"
                    >
                        @csrf

                        <x-primary-button>
                            Begin Diagnostic
                        </x-primary-button>
                    </form>

                </div>
            </div>

        </div>
    </div>
</x-app-layout>