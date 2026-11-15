<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Micro Pearl import preview" />
    </x-slot>

    <div class="rx-pearl-import mx-auto max-w-6xl space-y-4 px-4 py-4 sm:px-6 lg:space-y-6 lg:py-8">
        <x-ui.button variant="ghost" :href="route('content.pearls.imports.create')">
            Back to Imports
        </x-ui.button>

        @if (session('status'))
            <x-ui.alert variant="success">
                {{ session('status') }}
            </x-ui.alert>
        @endif

        @if ($errors->any())
            <x-ui.alert variant="danger">
                <ul class="list-disc space-y-1 pl-5 [overflow-wrap:anywhere]">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif

        @if (! $import->canCommit())
            <x-ui.alert variant="warning">
                This preview is no longer available for committing.
                Upload and preview the CSV again if necessary.
            </x-ui.alert>
        @endif

        <x-ui.card>
            <dl class="grid grid-cols-2 gap-4 text-sm lg:grid-cols-4">
                <div class="col-span-2 min-w-0 lg:col-span-1">
                    <dt class="font-bold text-ink-muted">File</dt>
                    <dd class="mt-1 font-semibold text-ink [overflow-wrap:anywhere]">
                        {{ $import->original_filename }}
                    </dd>
                </div>

                <div>
                    <dt class="font-bold text-ink-muted">Status</dt>
                    <dd class="mt-1 font-semibold text-ink">
                        {{ str($import->status)->replace('_', ' ')->title() }}
                    </dd>
                </div>

                <div>
                    <dt class="font-bold text-ink-muted">Lesson rows</dt>
                    <dd class="mt-1 font-semibold text-ink">
                        {{ $import->row_count }}
                    </dd>
                </div>

                <div class="col-span-2 lg:col-span-1">
                    <dt class="font-bold text-ink-muted">Preview expires</dt>
                    <dd class="mt-1 font-semibold text-ink">
                        {{ $import->expires_at->format('M j, Y g:i A') }}
                    </dd>
                </div>
            </dl>
        </x-ui.card>

        <x-ui.card :padded="false">
            <div class="border-b border-line px-4 py-4 sm:px-6">
                <h2 class="font-display text-base font-extrabold text-ink">
                    Drafts to create
                </h2>

                <p class="mt-1 text-sm text-ink-muted">
                    Full lesson content will be reparsed and validated
                    again when you commit.
                </p>
            </div>

            <ol class="divide-y divide-line" role="list">
                @foreach ($import->preview_rows as $row)
                    <li class="grid min-w-0 gap-2 px-4 py-3 sm:px-6 sm:py-4 lg:grid-cols-[minmax(0,1fr)_15rem] lg:items-center lg:gap-5">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold text-ink-muted">
                                CSV row {{ $row['row_number'] }} · {{ $row['competency_code'] }}
                            </p>

                            <h3 class="mt-1 font-semibold text-ink [overflow-wrap:anywhere]">
                                {{ $row['title'] }}
                            </h3>
                        </div>

                        <dl class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
                            <div class="flex items-center gap-1.5">
                                <dt class="text-ink-muted">Order</dt>
                                <dd class="font-semibold text-ink">{{ $row['order_index'] }}</dd>
                            </div>

                            <div class="flex items-center gap-1.5">
                                <dt class="text-ink-muted">Rights</dt>
                                <dd>
                                    <x-ui.badge>
                                        {{ str($row['rights_status'])->replace('_', ' ')->title() }}
                                    </x-ui.badge>
                                </dd>
                            </div>
                        </dl>
                    </li>
                @endforeach
            </ol>
        </x-ui.card>

        @if ($import->canCommit())
            <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_auto]">
                <x-ui.card>
                    <form
                        method="POST"
                        action="{{ route(
                            'content.pearls.imports.commit',
                            ['import' => $import],
                        ) }}"
                        class="space-y-5"
                    >
                        @csrf

                        <input
                            type="hidden"
                            name="expected_lock_version"
                            value="{{ $import->lock_version }}"
                        >

                        <x-ui.field
                            name="reason"
                            label="Import reason"
                            type="textarea"
                            :rows="3"
                            required
                            hint="Explain where these lessons came from and why they are being imported."
                        />

                        <x-ui.field
                            name="current_password"
                            label="Current password"
                            type="password"
                            required
                            autocomplete="current-password"
                            hint="Required because this action creates multiple content records."
                        />

                        <x-ui.button type="submit" class="w-full sm:w-auto">
                            Import {{ $import->row_count }} {{ str('Draft')->plural($import->row_count) }}
                        </x-ui.button>
                    </form>
                </x-ui.card>

                <x-ui.card class="lg:w-72">
                    <h2 class="font-display text-base font-extrabold text-ink">
                        Cancel preview
                    </h2>

                    <p class="mt-2 text-sm leading-relaxed text-ink-muted">
                        This deletes the private uploaded CSV without
                        creating any lessons.
                    </p>

                    <form
                        method="POST"
                        action="{{ route(
                            'content.pearls.imports.destroy',
                            ['import' => $import],
                        ) }}"
                        class="mt-4"
                    >
                        @csrf
                        @method('DELETE')

                        <x-ui.button
                            type="submit"
                            variant="danger"
                            class="w-full sm:w-auto"
                        >
                            Cancel Import
                        </x-ui.button>
                    </form>
                </x-ui.card>
            </div>
        @endif
    </div>
</x-app-layout>
