<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Micro Pearl Administration" />
    </x-slot>

    @php
        $showSubjects = ! request()->query->has('page')
            && request()->query('view') !== 'all'
            && $search === ''
            && $status === ''
            && $domain === null;

        $listContext = array_filter([
            'q' => $search,
            'status' => $status,
            'domain_id' => $domain?->domain_id,
            'view' => 'all',
            'page' => $pearls->currentPage(),
        ], fn ($value) => filled($value));
    @endphp

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        @if (session('status'))
            <x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>
        @endif

        @if (session('error'))
            <x-ui.alert variant="danger">{{ session('error') }}</x-ui.alert>
        @endif

        <nav aria-label="Micro Pearl actions" class="flex flex-wrap items-center gap-2">
            <x-ui.button :href="route('content.pearls.create')">Create Draft</x-ui.button>

            <x-ui.button variant="secondary" :href="route('content.pearls.imports.create')">
                Import CSV
            </x-ui.button>

            <x-ui.button variant="ghost"
                :href="route('content.pearls.index', ['status' => 'in_review']).'#pearl-results'">
                Review queue
            </x-ui.button>
        </nav>

        <form method="GET" action="{{ route('content.pearls.index') }}#pearl-results"
            class="space-y-4 rounded-2xl border border-line bg-surface p-4 sm:p-5">
            <h2 class="font-display text-base font-bold text-ink">Find Micro Pearls</h2>
            <input type="hidden" name="view" value="all">

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_minmax(0,1fr)] [&>*]:min-w-0">
                <div class="sm:col-span-2 lg:col-span-1">
                    <x-ui.field name="q" label="Title or summary" type="search"
                        :value="$search" placeholder="Search Micro Pearls" />
                </div>

                <x-ui.field name="domain_id" label="Subject" type="select">
                    <option value="">All subjects</option>
                    @foreach ($domains as $subject)
                        <option value="{{ $subject->domain_id }}" @selected($domain?->domain_id === $subject->domain_id)>
                            {{ $subject->domain_name }}
                        </option>
                    @endforeach
                </x-ui.field>

                <x-ui.field name="status" label="Status" type="select">
                    <option value="">All statuses</option>
                    @foreach (['draft', 'in_review', 'published', 'retired'] as $option)
                        <option value="{{ $option }}" @selected($status === $option)>
                            {{ str($option)->replace('_', ' ')->title() }}
                        </option>
                    @endforeach
                </x-ui.field>
            </div>

            <div class="flex flex-wrap gap-2">
                <x-ui.button type="submit" variant="secondary">Apply filters</x-ui.button>

                @if ($search !== '' || $status !== '' || $domain !== null)
                    <x-ui.button variant="ghost"
                        :href="route('content.pearls.index', ['view' => 'all']).'#pearl-results'">
                        Clear filters
                    </x-ui.button>
                @endif
            </div>
        </form>

        @if ($showSubjects)
            <x-admin.subject-grid :domains="$domains" index-route="content.pearls.index"
                item-label="Micro Pearls" icon="pearls" />
        @else
            <section id="pearl-results" aria-labelledby="pearl-results-title" class="scroll-mt-32 space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 id="pearl-results-title"
                        class="min-w-0 break-words font-display text-xl font-extrabold text-ink sm:text-2xl">
                        {{ $domain?->domain_name ?? ($status === 'in_review' ? 'Review queue' : 'Micro Pearls') }}
                    </h2>

                    <x-ui.button variant="ghost" :href="route('content.pearls.index')">
                        All subjects
                    </x-ui.button>
                </div>

                <x-ui.card :padded="false">
                    @if ($pearls->hasPages())
                        <div class="border-b border-line">
                            <x-admin.pager :paginator="$pearls" label="Micro Pearl results at top" />
                        </div>
                    @endif

                    <form method="POST" action="{{ route('content.pearls.bulk-publish') }}" data-bulk
                        x-data="{ selectedCount: 0 }"
                        x-init="selectedCount = $el.querySelectorAll('[data-row-select]:checked').length">
                        @csrf

                        @if ($publishableIds !== [])
                            <div class="space-y-3 border-b border-line px-4 py-4 sm:px-6">
                                <p class="text-sm text-ink-muted">Open Manage to review a pearl before publishing it.</p>
                                <x-ui.selection-controls />

                                <x-ui.button type="button" variant="secondary" data-bulk-submit aria-haspopup="dialog" disabled
                                    x-on:click="selectedCount = $el.form.querySelectorAll('[data-row-select]:checked').length; $dispatch('open-modal', 'pearl-bulk-publish')">
                                    Publish selected
                                </x-ui.button>
                            </div>

                            <x-modal name="pearl-bulk-publish" max-width="md" describedby="pearl-bulk-publish-description"
                                :show="$errors->bulkPublish->has('current_password')" focusable>
                                <div class="space-y-4 p-6">
                                    <h2 id="pearl-bulk-publish-title" class="text-lg font-bold">
                                        Publish selected Micro Pearls
                                    </h2>

                                    <p id="pearl-bulk-publish-description" class="text-sm text-ink-muted">
                                        <span x-text="selectedCount"></span> selected. Publish these lessons to the student library now?
                                    </p>

                                    <x-ui.field name="current_password" id="bulk-publish-password" type="password"
                                        label="Current password" autocomplete="current-password"
                                        :error="$errors->bulkPublish->first('current_password')" required />

                                    <div class="flex flex-wrap justify-end gap-3">
                                        <x-ui.button type="button" variant="secondary"
                                            x-on:click="$dispatch('close-modal', 'pearl-bulk-publish')">
                                            Cancel
                                        </x-ui.button>

                                        <x-ui.button type="submit" data-bulk-submit>Publish now</x-ui.button>
                                    </div>
                                </div>
                            </x-modal>
                        @endif

                        @if ($errors->bulkPublish->hasAny(['pearls', 'pearls.*']))
                            <p class="px-4 py-3 text-sm text-danger-ink" role="alert">
                                {{ $errors->bulkPublish->first('pearls') ?: $errors->bulkPublish->first('pearls.*') }}
                            </p>
                        @endif

                        <ul class="divide-y divide-line">
                            @forelse ($pearls as $pearl)
                                @php
                                    $statusVariant = match ($pearl->content_status) {
                                        'published' => 'success',
                                        'in_review' => 'warning',
                                        'retired' => 'danger',
                                        default => 'neutral',
                                    };
                                @endphp

                                <li class="grid gap-3 p-4 sm:p-5 md:grid-cols-[minmax(0,1fr)_auto] md:items-center">
                                    <div class="min-w-0 space-y-2 [overflow-wrap:anywhere]">
                                        <h3 class="text-base font-semibold leading-snug text-ink sm:text-lg">
                                            {{ $pearl->title }}
                                        </h3>

                                        <div class="flex flex-wrap gap-x-4 gap-y-1 text-sm leading-5 text-ink-muted">
                                            <span>
                                                {{ $pearl->competency?->domain?->domain_name ?? 'Unassigned subject' }}
                                                · {{ $pearl->competency?->competency_code ?? 'No competency' }}
                                            </span>
                                            <span>{{ $pearl->creator?->fullName() ?? 'Legacy record' }}</span>
                                            <span class="tabular-nums">Version {{ $pearl->edit_version }}</span>
                                        </div>

                                        <div class="flex flex-wrap gap-1.5">
                                            <x-ui.badge :variant="$statusVariant">
                                                {{ str($pearl->content_status)->replace('_', ' ')->title() }}
                                            </x-ui.badge>

                                            @if ($pearl->is_ai_generated)
                                                <x-ui.badge variant="info">AI-assisted</x-ui.badge>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-3">
                                        @if (in_array($pearl->pearl_id, $publishableIds, true))
                                            <label class="inline-flex min-h-11 items-center gap-2 text-sm font-semibold text-ink">
                                                <input type="checkbox" class="rounded border-line" data-row-select
                                                    name="pearls[{{ $pearl->pearl_id }}]" value="{{ $pearl->edit_version }}"
                                                    aria-label="Select {{ $pearl->title }} for publishing"
                                                    @checked(old('pearls.'.$pearl->pearl_id) === (string) $pearl->edit_version)>
                                                Select
                                            </label>
                                        @endif

                                        <x-ui.button variant="ghost"
                                            :href="route('content.pearls.edit', ['pearl' => $pearl->pearl_id, 'list' => $listContext])"
                                            :aria-label="'Manage '.$pearl->title">
                                            Manage
                                        </x-ui.button>
                                    </div>
                                </li>
                            @empty
                                <li class="p-6 text-sm text-ink-muted">No Micro Pearls match these filters.</li>
                            @endforelse
                        </ul>
                    </form>

                    <div class="border-t border-line">
                        <x-admin.pager :paginator="$pearls" label="Micro Pearl results" />
                    </div>
                </x-ui.card>
            </section>
        @endif
    </div>
</x-app-layout>
