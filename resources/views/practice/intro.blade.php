<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Practice Mode') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="mb-4 p-4 bg-blue-100 text-blue-800 rounded-lg">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-lg">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">

                    <h3 class="text-lg font-medium mb-4">
                        Adaptive Practice
                    </h3>

                    <p class="text-sm text-gray-600 mb-6">
                        Questions are selected adaptively based on your
                        current mastery. Competencies that need more
                        improvement have a greater chance of appearing,
                        while all eligible competencies remain available.
                        Your Board Readiness Estimate is updated as you
                        answer questions.
                    </p>

                    <form method="POST" action="{{ route('practice.start') }}">
                        @csrf

                        {{-- Session length --}}
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Number of questions
                            </label>

                            <div class="flex flex-wrap gap-4">
                                @foreach ($allowedLengths as $length)
                                    <label class="flex items-center">
                                        <input type="radio" name="length" value="{{ $length }}"
                                            @checked((int) old('length', 10) === $length) class="mr-2">

                                        <span>
                                            {{ $length }} questions
                                        </span>
                                    </label>
                                @endforeach
                            </div>

                            @error('length')
                                <p class="mt-1 text-sm text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        {{-- Domain filter --}}
                        <div class="mb-6">
                            <label for="domain_filter_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Focus area (optional)
                            </label>

                            <select name="domain_filter_id" id="domain_filter_id"
                                class="block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">
                                    All domains (adaptive)
                                </option>

                                @foreach ($domains as $domain)
                                    <option value="{{ $domain->domain_id }}" @selected((string) old('domain_filter_id') === (string) $domain->domain_id)>
                                        {{ $domain->domain_name }}
                                    </option>
                                @endforeach
                            </select>

                            @error('domain_filter_id')
                                <p class="mt-1 text-sm text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror

                            <p class="mt-2 text-xs text-gray-500">
                                For the current development question bank,
                                All Domains is recommended for testing.
                            </p>
                        </div>

                        <x-primary-button>
                            Start Practice
                        </x-primary-button>
                    </form>

                </div>
            </div>

        </div>
    </div>
</x-app-layout>
