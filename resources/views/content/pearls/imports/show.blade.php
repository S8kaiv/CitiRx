<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            title="Micro Pearl import preview"
            description="Review every draft before making database changes."
        >
            <x-slot:actions>
                <x-ui.button
                    variant="secondary"
                    :href="route('content.pearls.imports.create')"
                >
                    Back to Imports
                </x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="mx-auto max-w-6xl space-y-5 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <x-ui.alert variant="success">
                    {{ session('status') }}
                </x-ui.alert>
            @endif

            @if ($errors->any())
                <x-ui.alert variant="danger">
                    <ul class="list-disc space-y-1 pl-5">
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
                <dl class="grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <dt class="font-bold text-ink-muted">File</dt>
                        <dd class="mt-1 break-words font-semibold text-ink">
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

                    <div>
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

                <div class="overflow-x-auto">
                    <table class="min-w-[900px] w-full text-sm">
                        <thead class="bg-surface-2 text-left text-ink-muted">
                            <tr>
                                <th class="px-4 py-3 font-bold sm:px-6">CSV row</th>
                                <th class="px-4 py-3 font-bold">Competency</th>
                                <th class="px-4 py-3 font-bold">Title</th>
                                <th class="px-4 py-3 font-bold">Order</th>
                                <th class="px-4 py-3 font-bold">Rights</th>
                                <th class="px-4 py-3 font-bold">AI-assisted</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-line">
                            @foreach ($import->preview_rows as $row)
                                <tr>
                                    <td class="px-4 py-4 text-ink-muted sm:px-6">
                                        {{ $row['row_number'] }}
                                    </td>

                                    <td class="px-4 py-4 font-mono font-semibold text-primary-ink">
                                        {{ $row['competency_code'] }}
                                    </td>

                                    <td class="max-w-lg px-4 py-4 font-semibold text-ink">
                                        {{ $row['title'] }}
                                    </td>

                                    <td class="px-4 py-4 text-ink-muted">
                                        {{ $row['order_index'] }}
                                    </td>

                                    <td class="px-4 py-4">
                                        <x-ui.badge>
                                            {{ str($row['rights_status'])->replace('_', ' ')->title() }}
                                        </x-ui.badge>
                                    </td>

                                    <td class="px-4 py-4 text-ink-muted">
                                        {{ $row['is_ai_generated'] ? 'Yes' : 'No' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
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

                            <x-ui.button type="submit">
                                Import {{ $import->row_count }} Drafts
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
                            >
                                Cancel Import
                            </x-ui.button>
                        </form>
                    </x-ui.card>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
