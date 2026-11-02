<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            title="Micro Pearl Administration"
            description="Create, review, publish, and retire governed learning content."
        >
            <x-slot:actions>
                <x-ui.button :href="route('content.pearls.create')">
                    Create Draft
                </x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <x-ui.alert variant="success">
                    {{ session('status') }}
                </x-ui.alert>
            @endif

            @if (session('error'))
                <x-ui.alert variant="danger">
                    {{ session('error') }}
                </x-ui.alert>
            @endif

            <x-ui.card>
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="max-w-xl">
                        <h2 class="font-display text-base font-extrabold text-ink">
                            Content Workflow
                        </h2>

                        <p class="mt-1 text-sm leading-relaxed text-ink-muted">
                            Draft lessons remain private until a different reviewer approves publication.
                        </p>
                    </div>

                    <ol
                        class="flex flex-wrap items-center gap-2 text-xs font-bold"
                        aria-label="Micro Pearl publishing workflow"
                    >
                        <li>
                            <x-ui.badge>1. Draft</x-ui.badge>
                        </li>

                        <li class="text-ink-muted" aria-hidden="true">→</li>

                        <li>
                            <x-ui.badge variant="warning">2. In Review</x-ui.badge>
                        </li>

                        <li class="text-ink-muted" aria-hidden="true">→</li>

                        <li>
                            <x-ui.badge variant="success">3. Published</x-ui.badge>
                        </li>

                        <li class="text-ink-muted" aria-hidden="true">→</li>

                        <li>
                            <x-ui.badge variant="danger">4. Retired</x-ui.badge>
                        </li>
                    </ol>
                </div>
            </x-ui.card>

            <x-ui.card>
                <form
                    method="GET"
                    action="{{ route('content.pearls.index') }}"
                    class="grid items-end gap-4 md:grid-cols-[minmax(0,1fr)_minmax(12rem,0.45fr)_auto]"
                >
                    <x-ui.field
                        name="q"
                        label="Search"
                        :value="$search"
                        placeholder="Search title or summary"
                    />

                    <x-ui.field
                        name="status"
                        label="Status"
                        type="select"
                    >
                        <option value="">All statuses</option>

                        @foreach ([
                            'draft',
                            'in_review',
                            'published',
                            'retired',
                        ] as $option)
                            <option
                                value="{{ $option }}"
                                @selected($status === $option)
                            >
                                {{ str($option)->replace('_', ' ')->title() }}
                            </option>
                        @endforeach
                    </x-ui.field>

                    <div class="flex flex-wrap gap-2">
                        <x-ui.button type="submit">
                            Filter
                        </x-ui.button>

                        @if ($search !== '' || $status !== '')
                            <x-ui.button
                                variant="secondary"
                                :href="route('content.pearls.index')"
                            >
                                Clear
                            </x-ui.button>
                        @endif
                    </div>
                </form>
            </x-ui.card>

            <x-ui.card :padded="false">
                <div class="overflow-x-auto">
                    <table class="min-w-[880px] w-full text-sm">
                        <thead class="bg-surface-2 text-left text-ink-muted">
                            <tr>
                                <th scope="col" class="px-4 py-3 font-bold sm:px-6">Pearl</th>
                                <th scope="col" class="px-4 py-3 font-bold">Competency</th>
                                <th scope="col" class="px-4 py-3 font-bold">Status</th>
                                <th scope="col" class="px-4 py-3 font-bold">Author</th>
                                <th scope="col" class="px-4 py-3 text-center font-bold">Version</th>
                                <th scope="col" class="px-4 py-3 text-right font-bold sm:px-6">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-line">
                            @forelse ($pearls as $pearl)
                                @php
                                    $statusVariant = match ($pearl->content_status) {
                                        'published' => 'success',
                                        'in_review' => 'warning',
                                        'retired' => 'danger',
                                        default => 'neutral',
                                    };
                                @endphp

                                <tr>
                                    <td class="max-w-md px-4 py-4 sm:px-6">
                                        <div class="font-bold text-ink">
                                            {{ $pearl->title }}
                                        </div>

                                        <div class="mt-1 flex flex-wrap items-center gap-2">
                                            <span class="text-xs text-ink-muted">
                                                {{ $pearl->competency->domain->domain_name }}
                                            </span>

                                            @if ($pearl->is_ai_generated)
                                                <x-ui.badge variant="info">
                                                    AI-assisted
                                                </x-ui.badge>
                                            @endif
                                        </div>
                                    </td>

                                    <td class="px-4 py-4">
                                        <span class="font-semibold text-ink">
                                            {{ $pearl->competency->competency_code }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-4">
                                        <x-ui.badge :variant="$statusVariant">
                                            {{ str($pearl->content_status)
                                                ->replace('_', ' ')
                                                ->title() }}
                                        </x-ui.badge>
                                    </td>

                                    <td class="px-4 py-4 text-ink-muted">
                                        {{ $pearl->creator?->fullName() ?? 'Legacy record' }}
                                    </td>

                                    <td class="px-4 py-4 text-center font-semibold tabular-nums text-ink">
                                        {{ $pearl->edit_version }}
                                    </td>

                                    <td class="px-4 py-4 text-right sm:px-6">
                                        <x-ui.button
                                            variant="secondary"
                                            :href="route(
                                                'content.pearls.edit',
                                                ['pearl' => $pearl->pearl_id],
                                            )"
                                        >
                                            Manage
                                        </x-ui.button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="6"
                                        class="px-6 py-12 text-center"
                                    >
                                        <div class="mx-auto max-w-md">
                                            <span
                                                class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-primary-tint text-primary-ink"
                                                aria-hidden="true"
                                            >
                                                <x-admin.icon
                                                    name="pearls"
                                                    class="h-6 w-6"
                                                />
                                            </span>

                                            <p class="mt-4 font-display text-lg font-extrabold text-ink">
                                                {{ $search !== '' || $status !== ''
                                                    ? 'No Micro Pearls match these filters.'
                                                    : 'No Micro Pearls have been created yet.' }}
                                            </p>

                                            <p class="mt-2 text-sm leading-relaxed text-ink-muted">
                                                {{ $search !== '' || $status !== ''
                                                    ? 'Clear the filters to review all available content.'
                                                    : 'Create the first governed lesson draft, add its sources and rights information, then submit it for independent review.' }}
                                            </p>

                                            <div class="mt-5 flex flex-wrap justify-center gap-2">
                                                @if ($search !== '' || $status !== '')
                                                    <x-ui.button
                                                        variant="secondary"
                                                        :href="route('content.pearls.index')"
                                                    >
                                                        Clear Filters
                                                    </x-ui.button>
                                                @endif

                                                <x-ui.button :href="route('content.pearls.create')">
                                                    Create First Draft
                                                </x-ui.button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($pearls->hasPages())
                    <div class="border-t border-line px-4 py-4 sm:px-6">
                        {{ $pearls->links() }}
                    </div>
                @endif
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
