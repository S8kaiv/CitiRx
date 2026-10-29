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
                    class="flex items-center gap-3 p-4 border-2 border-b-4 shadow-sm rounded-2xl border-weak/40 bg-weak-tint font-display text-xs font-bold text-weak-ink">
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
                    <div>
                        <label
                            class="block font-display text-xs font-bold uppercase tracking-wider text-ink mb-2.5">
                            Session Length
                        </label>

                        <div class="grid w-full grid-cols-3 gap-3">
                            @foreach ($allowedLengths as $length)
                                <label class="relative block cursor-pointer group">
                                    <input type="radio" name="length" value="{{ $length }}"
                                        @checked((int) old('length', $selectedLength) === $length) class="peer sr-only">

                                    <div
                                        class="flex flex-col items-center justify-center px-2 py-3 text-center transition-all bg-surface border-2 border-b-4 border-line rounded-xl hover:border-line-strong hover:bg-surface-2/80 peer-checked:border-primary peer-checked:border-b-primary-lip peer-checked:bg-primary-tint/30 shadow-xs">
                                        <span
                                            class="font-display text-lg font-extrabold leading-none text-ink peer-checked:text-primary">
                                            {{ $length }}
                                        </span>
                                        <span
                                            class="font-display text-[10px] font-bold uppercase tracking-wider text-ink-muted peer-checked:text-primary mt-1">
                                            Items
                                        </span>
                                    </div>
                                </label>
                            @endforeach
                        </div>

                        @error('length')
                            <p class="mt-2 text-xs font-semibold text-weak-ink">
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
                            <p class="mt-2 text-xs font-semibold text-weak-ink">
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
