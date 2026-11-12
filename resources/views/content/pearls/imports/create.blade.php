<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            title="Import Micro Pearl lessons"
            description="Upload a reviewed CSV, run a dry preview, and create private drafts."
        >
            <x-slot:actions>
                <div class="flex flex-wrap gap-2">
                    <x-ui.button
                        variant="secondary"
                        :href="route('content.pearls.imports.template')"
                    >
                        Download CSV Template
                    </x-ui.button>

                    <x-ui.button
                        variant="ghost"
                        :href="route('content.pearls.index')"
                    >
                        Back to Pearls
                    </x-ui.button>
                </div>
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <div class="pt-6 pb-6 sm:pb-8">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <x-ui.alert variant="success">
                    {{ session('status') }}
                </x-ui.alert>
            @endif

            @if ($errors->any())
                <x-ui.alert variant="danger">
                    <p class="font-bold">
                        The CSV could not be previewed.
                    </p>

                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </x-ui.alert>
            @endif

            <x-ui.alert variant="warning">
                Imported lessons are always created as private drafts.
                CSV uploads cannot approve, review, publish, retire, or
                replace existing Micro Pearls.
            </x-ui.alert>

            <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_20rem]">
                <x-ui.card>
                    <form
                        method="POST"
                        action="{{ route('content.pearls.imports.preview') }}"
                        enctype="multipart/form-data"
                        class="space-y-5"
                    >
                        @csrf

                        <div>
                            <label
                                for="csv"
                                class="block text-sm font-bold text-ink"
                            >
                                Micro Pearl CSV
                            </label>

                            <input
                                id="csv"
                                name="csv"
                                type="file"
                                accept=".csv,text/csv"
                                required
                                class="mt-2 block min-h-12 w-full rounded-2xl
                                    border border-line bg-surface px-4 py-3
                                    text-sm text-ink file:mr-4 file:rounded-xl
                                    file:border-0 file:bg-primary-tint
                                    file:px-4 file:py-2 file:font-bold
                                    file:text-primary-ink"
                            >

                            <p class="mt-2 text-sm text-ink-muted">
                                UTF-8 CSV only. Maximum size: 2 MB.
                                Maximum rows: 200.
                            </p>
                        </div>

                        <x-ui.button type="submit">
                            Validate and Preview
                        </x-ui.button>
                    </form>
                </x-ui.card>

                <x-ui.card>
                    <h2 class="font-display text-base font-extrabold text-ink">
                        List formatting
                    </h2>

                    <div class="mt-3 space-y-3 text-sm leading-relaxed text-ink-muted">
                        <p>
                            Separate objectives, key points, pitfalls,
                            prompts, and references with:
                        </p>

                        <code class="block overflow-x-auto rounded-xl bg-surface-2 p-3 text-xs text-ink">
                            First value || Second value
                        </code>

                        <p>
                            Recall prompts must use:
                        </p>

                        <code class="block overflow-x-auto rounded-xl bg-surface-2 p-3 text-xs text-ink">
                            Question | Answer || Question | Answer
                        </code>

                        <p>
                            AI-generated rows require provider, model,
                            and prompt-version metadata.
                        </p>
                    </div>
                </x-ui.card>
            </div>

            <x-ui.card :padded="false">
                <div class="border-b border-line px-4 py-4 sm:px-6">
                    <h2 class="font-display text-base font-extrabold text-ink">
                        Your recent imports
                    </h2>
                </div>

                <div class="overflow-x-auto">
                    <div class="rx-admin-table-scroll" role="region" aria-label="Scrollable data table" tabindex="0">
                    <table class="min-w-[760px] w-full text-sm">
                        <thead class="bg-surface-2 text-left text-ink-muted">
                            <tr>
                                <th scope="col" class="px-4 py-3 font-bold sm:px-6">
                                    File
                                </th>

                                <th scope="col" class="px-4 py-3 font-bold">
                                    Status
                                </th>

                                <th scope="col" class="px-4 py-3 font-bold">
                                    Rows
                                </th>

                                <th scope="col" class="px-4 py-3 font-bold">
                                    Expires
                                </th>

                                <th scope="col" class="px-4 py-3 text-right font-bold sm:px-6">
                                    Action
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-line">
                            @forelse ($imports as $import)
                                @php
                                    $variant = match ($import->status) {
                                        'committed' => 'success',
                                        'failed', 'expired' => 'danger',
                                        'cancelled' => 'neutral',
                                        default => 'warning',
                                    };
                                @endphp

                                <tr>
                                    <td class="px-4 py-4 font-semibold text-ink sm:px-6">
                                        {{ $import->original_filename }}
                                    </td>

                                    <td class="px-4 py-4">
                                        <x-ui.badge :variant="$variant">
                                            {{ str($import->status)->replace('_', ' ')->title() }}
                                        </x-ui.badge>
                                    </td>

                                    <td class="px-4 py-4 text-ink-muted">
                                        {{ $import->row_count }}
                                    </td>

                                    <td class="px-4 py-4 text-ink-muted">
                                        {{ $import->expires_at->format('M j, Y g:i A') }}
                                    </td>

                                    <td class="px-4 py-4 text-right sm:px-6">
                                        <x-ui.button
                                            variant="secondary"
                                            :href="route(
                                                'content.pearls.imports.show',
                                                ['import' => $import],
                                            )"
                                        >
                                            View
                                        </x-ui.button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="5"
                                        class="px-6 py-10 text-center text-ink-muted"
                                    >
                                        No Micro Pearl imports yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>
                </div>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
