<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-xl font-bold text-slate-900 leading-tight">
            {{ __('Practice Mode') }}
        </h2>
    </x-slot>

    {{-- Added pt-10 pb-16 for breathing room below the header bar --}}
    <div class="pt-10 pb-16 font-sans text-slate-900 antialiased">
        <div class="max-w-xl mx-auto px-4 sm:px-6">

            {{-- Flash alerts --}}
            @if (session('status'))
                <div class="mb-5 rounded-xl border border-strong/30 bg-strong-tint p-3.5 text-sm font-medium text-strong-ink">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-5 rounded-xl border border-weak/30 bg-weak-tint p-3.5 text-sm font-medium text-weak-ink">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Centered Form Card --}}
            <div class="overflow-hidden rounded-2xl border border-muted-line bg-white p-6 shadow-sm sm:p-8">
                
                <h3 class="font-display text-xl font-bold text-slate-900">
                    Adaptive Practice
                </h3>

                <p class="mt-2 text-sm leading-relaxed text-muted-ink">
                    Questions are selected adaptively based on your current mastery. Competencies that need more improvement have a greater chance of appearing, while all eligible competencies remain available. Your Board Readiness Estimate is updated as you answer questions.
                </p>

                <form method="POST" action="{{ route('practice.start') }}" class="mt-6 space-y-6">
                    @csrf

                    {{-- Single-line 10 / 20 / 30 selector --}}
                    <div>
                        <label class="block font-display text-xs font-bold uppercase tracking-wider text-slate-700 mb-2.5">
                            Number of questions
                        </label>

                        {{-- flex flex-row ensures all 3 stay on one single line --}}
                        <div class="flex flex-row items-center gap-3 w-full">
                            @foreach ($allowedLengths as $length)
                                <label class="flex-1 min-w-0 cursor-pointer">
                                    <input type="radio" 
                                           name="length" 
                                           value="{{ $length }}"
                                           @checked((int) old('length', 10) === $length)
                                           class="peer sr-only">

                                    <div class="w-full text-center rounded-xl border border-muted-line bg-white py-2.5 px-2 transition-all hover:border-primary/50 peer-checked:border-primary peer-checked:bg-primary-tint peer-checked:text-primary shadow-sm">
                                        <span class="font-display text-sm font-bold text-slate-800 peer-checked:text-primary">
                                            {{ $length }}
                                        </span>
                                        <span class="font-sans text-xs text-muted-ink peer-checked:text-primary font-medium ml-1">
                                            questions
                                        </span>
                                    </div>
                                </label>
                            @endforeach
                        </div>

                        @error('length')
                            <p class="mt-1.5 text-xs font-medium text-weak-ink">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Focus Area Dropdown --}}
                    <div>
                        <label for="domain_filter_id" class="block font-display text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                            Focus area (optional)
                        </label>

                        <select name="domain_filter_id" 
                                id="domain_filter_id"
                                class="block w-full rounded-xl border border-muted-line bg-white px-3.5 py-2.5 text-sm font-medium text-slate-900 shadow-sm transition focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                            <option value="">
                                All domains (adaptive)
                            </option>

                            @foreach ($domains as $domain)
                                <option value="{{ $domain->domain_id }}" 
                                        @selected((string) old('domain_filter_id') === (string) $domain->domain_id)>
                                    {{ $domain->domain_name }}
                                </option>
                            @endforeach
                        </select>

                        @error('domain_filter_id')
                            <p class="mt-1.5 text-xs font-medium text-weak-ink">
                                {{ $message }}
                            </p>
                        @enderror

                        <p class="mt-2 text-xs text-muted-ink">
                            For the current development question bank, All Domains is recommended for testing.
                        </p>
                    </div>

                    {{-- Primary Submit Button --}}
                    <div class="pt-2">
                        <button type="submit"
                                style="--lip: #4A2FC4;"
                                class="btn-press inline-flex items-center justify-center rounded-xl bg-primary px-7 py-3 font-display text-sm font-bold text-white transition hover:bg-primary/95">
                            Start Practice
                        </button>
                    </div>

                </form>

            </div>

        </div>
    </div>
</x-app-layout>