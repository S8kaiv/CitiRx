<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="font-display text-xl font-black leading-tight text-ink">
                    {{ __('Practice Setup') }}
                </h1>
                <p class="mt-1 font-display text-[11px] font-bold uppercase tracking-wider text-ink-muted">
                    Adaptive Bayesian Knowledge Tracing
                </p>
            </div>

        </div>
    </x-slot>

    <div class="pt-8 pb-16 font-sans antialiased text-ink">
        <div class="max-w-xl px-4 mx-auto space-y-6 sm:px-6">

            {{-- Flash Alert Messages --}}
            @if (session('status'))
                <div role="status"
                    class="flex items-center gap-3 p-4 border-2 border-b-4 shadow-sm rounded-2xl border-primary/30 bg-primary-tint/60 font-display text-xs font-bold text-primary">
                    <span class="text-base">ℹ️</span>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div role="alert"
                    class="flex items-center gap-3 p-4 border-2 border-b-4 shadow-sm rounded-2xl border-danger-strong/40 bg-danger-tint font-display text-xs font-bold text-danger-ink">
                    <span class="text-base">⚠️</span>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            {{-- Main Setup Card --}}
            <div
                class="p-6 overflow-hidden bg-surface border-2 border-b-4 border-line shadow-sm rounded-2xl sm:p-8 space-y-6">

                {{-- Standard Intro Text --}}
                <div>
                    <h3 class="font-display text-lg font-bold text-ink">
                        Adaptive Practice
                    </h3>
                    <p class="mt-1 text-xs leading-relaxed text-ink-muted sm:text-sm">
                        Questions are weighted based on your Bayesian mastery. Competencies requiring reinforcement are
                        prioritized, while previously mastered topics cycle in to ensure long-term retention.
                    </p>
                </div>

                <form method="POST" action="{{ route('practice.start') }}" class="space-y-6">
                    @csrf

                    {{-- Question Count Selector (Tactile 3D Radio Cards) --}}
                    {{-- Session Length Selector --}}
                    <div>
                        <fieldset>
                            <legend
                                class="mb-2.5 block font-display text-xs font-bold uppercase tracking-wider text-ink"
                            >
                                Session Length
                            </legend>

                            <div class="grid w-full grid-cols-3 gap-3">
                                @foreach ($allowedLengths as $length)
                                    <label class="ui-length-choice">
                                        <input
                                            type="radio"
                                            name="length"
                                            value="{{ $length }}"
                                            @checked(
                                                (int) old(
                                                    'length',
                                                    $selectedLength,
                                                ) === $length
                                            )
                                            class="ui-length-choice-input"
                                        >

                                        <span class="ui-length-choice-card">
                                            <span class="ui-length-choice-value">
                                                {{ $length }}
                                            </span>

                                            <span class="ui-length-choice-label">
                                                Items
                                            </span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>

                        @error('length')
                            <p class="mt-2 text-xs font-semibold text-danger-ink">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Domain Filter Dropdown --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label for="domain_filter_id"
                                class="font-display text-xs font-bold uppercase tracking-wider text-ink">
                                Domain Focus (Optional)
                            </label>
                            <span
                                class="font-display text-[10px] font-semibold text-ink-muted uppercase tracking-wider">
                                Adaptive Filter
                            </span>
                        </div>

                        <select name="domain_filter_id" id="domain_filter_id"
                            class="block w-full px-3.5 py-2.5 text-sm font-semibold transition bg-surface border-2 border-line rounded-xl text-ink shadow-xs focus:border-primary focus:outline-none focus:ring-0">
                            <option value="">
                                All Domains (Full Table of Specifications)
                            </option>

                            @foreach ($domains as $domain)
                                <option value="{{ $domain->domain_id }}" @selected((string) old('domain_filter_id', $selectedDomainId) === (string) $domain->domain_id)>
                                    {{ $domain->domain_name }} </option>
                            @endforeach
                        </select>

                        @error('domain_filter_id')
                            <p class="mt-2 text-xs font-semibold text-danger-ink">
                                {{ $message }}
                            </p>
                        @enderror

                        <p class="mt-2 text-[11px] text-ink-muted leading-normal">
                            Leaving this set to <strong>All Domains</strong> provides the most balanced calibration
                            toward your Board Readiness score.
                        </p>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="space-y-3 pt-3">
                        <x-ui.button
                            type="submit"
                            class="w-full"
                        >
                            Start Practice Session
                        </x-ui.button>

                        <x-ui.button
                            :href="route('dashboard')"
                            variant="secondary"
                            class="w-full"
                        >
                            Return to Dashboard
                        </x-ui.button>
                    </div>

                </form>

            </div>

        </div>
    </div>
</x-app-layout>
