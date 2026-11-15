<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Import Micro Pearl lessons" />
    </x-slot>

    <div class="rx-pearl-import mx-auto max-w-6xl space-y-4 px-4 py-4 sm:px-6 lg:space-y-6 lg:py-8">
        <x-ui.button variant="ghost" :href="route('content.pearls.index')">
            Back to Pearls
        </x-ui.button>

        @if (session('status'))
            <x-ui.alert variant="success">
                {{ session('status') }}
            </x-ui.alert>
        @endif

        @if ($errors->any())
            <x-ui.alert variant="danger">
                <p class="font-bold">The CSV could not be previewed.</p>
                <ul class="mt-2 list-disc space-y-1 pl-5 [overflow-wrap:anywhere]">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif

        <div class="grid items-start gap-4 lg:grid-cols-[minmax(0,1fr)_20rem] lg:gap-5">
            <x-ui.card class="min-w-0">
                <form
                    method="POST"
                    action="{{ route('content.pearls.imports.preview') }}"
                    enctype="multipart/form-data"
                    class="space-y-4"
                >
                    @csrf

                    <div>
                        <h2 class="font-display text-base font-extrabold text-ink">Upload lessons</h2>
                        <p class="mt-1 text-sm leading-relaxed text-ink-muted">
                            Preview your CSV before importing the lessons as private drafts.
                        </p>
                    </div>

                    <div>
                        <label for="csv" class="block text-sm font-bold text-ink">Micro Pearl CSV</label>
                        <input
                            id="csv"
                            name="csv"
                            type="file"
                            accept=".csv,text/csv"
                            aria-describedby="pearl-csv-help"
                            aria-invalid="{{ $errors->has('csv') ? 'true' : 'false' }}"
                            required
                            class="ui-field-control mt-2 min-h-12 w-full min-w-0 max-w-full cursor-pointer p-2 text-base
                                file:mr-2 file:min-h-11 file:cursor-pointer file:rounded-lg file:border-0
                                file:bg-primary-tint file:px-3 file:font-bold file:text-primary-ink"
                        >

                        <p id="pearl-csv-help" class="mt-2 text-sm leading-relaxed text-ink-muted">
                            CSV UTF-8 only. Up to 200 lessons and 2 MB.
                        </p>
                    </div>

                    <x-ui.button type="submit" class="w-full sm:w-auto">
                        Validate and Preview
                    </x-ui.button>
                </form>
            </x-ui.card>

            <x-ui.card class="min-w-0">
                <h2 class="font-display text-base font-extrabold text-ink">Prepare a CSV</h2>

                <p class="mt-2 text-sm leading-relaxed text-ink-muted">
                    Follow the Faculty Guide in the workbook. Enter one lesson per row in the Lessons tab, then save that tab as CSV UTF-8 (Comma delimited).
                </p>

                <div class="mt-4 grid gap-2 sm:flex sm:flex-wrap lg:grid">
                    <x-ui.button :href="route('content.pearls.imports.workbook')" class="w-full sm:w-auto lg:w-full">
                        Download Faculty Workbook
                    </x-ui.button>

                    <x-ui.button
                        variant="secondary"
                        :href="route('content.pearls.imports.template')"
                        class="w-full sm:w-auto lg:w-full"
                    >
                        Download CSV Template
                    </x-ui.button>
                </div>

                <details class="mt-3 border-t border-line text-sm text-ink-muted">
                    <summary class="min-h-11 cursor-pointer select-none py-3 font-bold text-ink">
                        CSV formatting examples
                    </summary>

                    <div class="space-y-3 leading-relaxed">
                        <p>Keep the headers in order. Use an existing competency code, an unused display order for that competency, and at least one source reference.</p>
                        <p>Separate objectives, key points, pitfalls, prompts, and references with <code>||</code>.</p>
                        <p>Write each recall prompt as <code>Question | Answer</code>. Separate multiple pairs with <code>||</code>.</p>
                    </div>
                </details>
            </x-ui.card>
        </div>

        <x-ui.card :padded="false">
            <div class="border-b border-line px-4 py-4 sm:px-6">
                <h2 class="font-display text-base font-extrabold text-ink">Your recent imports</h2>
            </div>

            @if ($imports->isEmpty())
                <p class="px-4 py-6 text-sm text-ink-muted sm:px-6">No Micro Pearl imports yet.</p>
            @else
                <ul class="divide-y divide-line" role="list">
                    @foreach ($imports as $import)
                        @php
                            $variant = match ($import->status) {
                                'committed' => 'success',
                                'failed', 'expired' => 'danger',
                                'cancelled' => 'neutral',
                                default => 'warning',
                            };
                        @endphp

                        <li class="grid min-w-0 items-center gap-3 px-4 py-4 sm:px-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.6fr)_auto] lg:gap-5">
                            <p class="min-w-0 text-sm font-semibold text-ink [overflow-wrap:anywhere]">
                                {{ $import->original_filename }}
                            </p>

                            <dl class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-3">
                                <div class="min-w-0">
                                    <dt class="text-ink-muted">Status</dt>
                                    <dd class="mt-1">
                                        <x-ui.badge :variant="$variant">
                                            {{ str($import->status)->replace('_', ' ')->title() }}
                                        </x-ui.badge>
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-ink-muted">Lessons</dt>
                                    <dd class="mt-1 font-semibold text-ink">{{ $import->row_count }}</dd>
                                </div>

                                <div class="col-span-2 sm:col-span-1">
                                    <dt class="text-ink-muted">Preview expires</dt>
                                    <dd class="mt-1 text-ink">
                                        <time datetime="{{ $import->expires_at->toIso8601String() }}">
                                            {{ $import->expires_at->format('M j, Y g:i A') }}
                                        </time>
                                    </dd>
                                </div>
                            </dl>

                            <x-ui.button
                                variant="secondary"
                                :href="route('content.pearls.imports.show', ['import' => $import])"
                                :aria-label="'View import for '.$import->original_filename"
                                class="w-full sm:w-auto sm:justify-self-start"
                            >
                                View import
                            </x-ui.button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>
    </div>
</x-app-layout>
