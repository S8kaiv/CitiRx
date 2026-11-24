<x-app-layout>
    @php
        $statusVariant = match ($pearl->content_status) {
            'published' => 'success',
            'in_review' => 'warning',
            'retired' => 'danger',
            default => 'neutral',
        };

        $listContext = array_filter(
            \Illuminate\Support\Arr::only(request()->array('list'), ['q', 'status', 'domain_id', 'view', 'page']),
            fn ($value) => is_scalar($value) && filled($value),
        );

        $listUrl = route('content.pearls.index', $listContext);
    @endphp

    <x-slot name="header">
        <x-ui.page-header :title="$pearl->title"
            :description="$pearl->competency
                ? $pearl->competency->competency_code.' · '.$pearl->competency->title
                : 'Unassigned competency'" />
    </x-slot>

    <div class="pt-6 pb-6 sm:pb-8">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <x-ui.button variant="ghost" :href="$listUrl.($listContext ? '#pearl-results' : '')">
                    {{ $listContext ? 'Back to pearl results' : 'Back to Micro Pearls' }}
                </x-ui.button>

                <x-ui.badge :variant="$statusVariant">
                    {{ str($pearl->content_status)->replace('_', ' ')->title() }}
                </x-ui.badge>
            </div>
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

            @if ($pearl->content_status !== 'draft')
                <x-ui.card>
                    <h2 class="text-xl font-bold text-ink">Review lesson content</h2>
                    <p class="mt-3 whitespace-pre-line break-words text-sm text-ink">{{ $pearl->mnemonic_summary }}</p>
                    <div class="mt-5 whitespace-pre-line break-words text-ink">{{ $pearl->lesson_content }}</div>
                    @foreach(['learning_objectives' => 'Learning objectives', 'key_points' => 'Key points', 'common_pitfalls' => 'Common pitfalls'] as $field => $label)
                        <details class="mt-4 rounded-xl border border-line p-4"><summary class="cursor-pointer font-bold text-ink">{{ $label }}</summary><ul class="mt-3 list-disc space-y-2 pl-5 text-sm text-ink">@foreach($pearl->$field ?? [] as $line)<li>{{ $line }}</li>@endforeach</ul></details>
                    @endforeach
                    <details class="mt-4 rounded-xl border border-line p-4"><summary class="cursor-pointer font-bold text-ink">Recall questions and answers</summary><dl class="mt-3 space-y-3 text-sm text-ink">@foreach($pearl->recall_prompts ?? [] as $prompt)<div><dt class="font-bold">{{ $prompt['question'] ?? '' }}</dt><dd class="mt-1">{{ $prompt['answer'] ?? '' }}</dd></div>@endforeach</dl></details>
                    <details open class="mt-4 rounded-xl border border-line p-4"><summary class="cursor-pointer font-bold text-ink">Sources and rights</summary><p class="mt-3 text-sm text-ink">Rights: {{ $pearl->rights_status }}</p><ul class="mt-2 list-disc space-y-2 pl-5 text-sm text-ink">@foreach($pearl->source_references ?? [] as $reference)<li class="break-words">{{ $reference }}</li>@endforeach</ul></details>
                </x-ui.card>
            @endif

            @if ($pearl->content_status === 'draft')
                <x-ui.card>
                    <form
                        method="POST"
                        action="{{ route(
                            'content.pearls.update',
                            ['pearl' => $pearl->pearl_id],
                        ) }}"
                    >
                        @csrf
                        @method('PUT')

                        @include('content.pearls._form')
                    </form>
                </x-ui.card>

                <x-ui.card class="border-warning-strong/30 bg-warning-tint">
                    <div class="mb-5">
                        <h2 class="font-display text-lg font-extrabold text-warning-ink">
                            Submit for Review
                        </h2>

                        <p class="mt-1 text-sm leading-relaxed text-warning-ink/80">
                            Faculty drafts need approval from a different reviewer. Submission locks editing until the Pearl is returned or published.
                        </p>
                    </div>

                    <form
                        method="POST"
                        action="{{ route(
                            'content.pearls.submit-review',
                            ['pearl' => $pearl->pearl_id],
                        ) }}"
                        data-confirm="Submit this saved draft for independent review? You can edit it again if it is returned for revision."
                        class="space-y-4"
                    >
                        @csrf
                        @method('PATCH')

                        <input
                            type="hidden"
                            name="edit_version"
                            value="{{ $pearl->edit_version }}"
                        >

                        <x-ui.field
                            name="reason"
                            id="submit-review-reason"
                            label="Reviewer Note (optional)"
                            type="textarea"
                            :rows="3"
                            placeholder="Add anything the reviewer should check closely."
                        />

                        <x-ui.button type="submit">
                            Submit for Review
                        </x-ui.button>
                    </form>
                </x-ui.card>

                @if (in_array(auth()->user()->role, ['admin', 'dean'], true) && $pearl->created_by === auth()->id())
                    <x-ui.card class="border-success-strong/30 bg-success-tint">
                        <div class="mb-5">
                            <h2 class="font-display text-lg font-extrabold text-success-ink">
                                Publish Your Pearl
                            </h2>

                            <p class="mt-1 text-sm text-success-ink/80">
                                Administrators and deans can publish their own completed drafts without a separate approval. Sources and rights are checked before publication. Confirm with your current password.
                            </p>
                        </div>

                        <form
                            method="POST"
                            action="{{ route(
                                'content.pearls.publish',
                                ['pearl' => $pearl->pearl_id],
                            ) }}"
                            data-confirm="Publish this completed lesson to the student library?"
                            class="space-y-4"
                        >
                            @csrf
                            @method('PATCH')

                            <input
                                type="hidden"
                                name="edit_version"
                                value="{{ $pearl->edit_version }}"
                            >

                            <x-ui.field
                                name="published_at"
                                label="Publication date (optional)"
                                type="datetime-local"
                            />

                            <x-ui.field
                                name="current_password"
                                label="Current password"
                                type="password"
                                required
                                autocomplete="current-password"
                            />

                            <x-ui.button type="submit">
                                Publish Pearl
                            </x-ui.button>
                        </form>
                    </x-ui.card>
                @endif
            @elseif ($pearl->content_status === 'in_review')
                <x-ui.alert variant="warning">
                    Review the lesson, sources, rights status, and learner-facing content before choosing an outcome.
                </x-ui.alert>

                <div class="grid gap-6 lg:grid-cols-2">
                    <x-ui.card>
                        <div class="mb-5">
                            <h2 class="font-display text-lg font-extrabold text-ink">
                                Return for Revision
                            </h2>

                            <p class="mt-1 text-sm text-ink-muted">
                                Return the Pearl to draft with clear, actionable corrections.
                            </p>
                        </div>

                        <form
                            method="POST"
                            action="{{ route(
                                'content.pearls.return-draft',
                                ['pearl' => $pearl->pearl_id],
                            ) }}"
                            data-confirm="Return this lesson to its author for revision?"
                            class="space-y-4"
                        >
                            @csrf
                            @method('PATCH')

                            <input
                                type="hidden"
                                name="edit_version"
                                value="{{ $pearl->edit_version }}"
                            >

                            <x-ui.field
                                name="reason"
                                id="return-draft-reason"
                                label="Required corrections"
                                type="textarea"
                                :rows="5"
                                required
                                placeholder="Describe the changes required before approval."
                            />

                            <x-ui.button type="submit" variant="secondary">
                                Return to Draft
                            </x-ui.button>
                        </form>
                    </x-ui.card>

                    @if (in_array(auth()->user()->role, ['admin', 'dean'], true) || $pearl->created_by !== auth()->id())
                    <x-ui.card class="border-success-strong/30 bg-success-tint">
                        <div class="mb-5">
                            <h2 class="font-display text-lg font-extrabold text-success-ink">
                                {{ in_array(auth()->user()->role, ['admin', 'dean'], true) && $pearl->created_by === auth()->id()
                                    ? 'Publish Your Pearl'
                                    : 'Approve Publication' }}
                            </h2>

                            <p class="mt-1 text-sm text-success-ink/80">
                                Confirm with your current password. Leave the date blank to publish immediately.
                            </p>
                        </div>

                        <form
                            method="POST"
                            action="{{ route(
                                'content.pearls.publish',
                                ['pearl' => $pearl->pearl_id],
                            ) }}"
                            data-confirm="Approve and publish this independently reviewed lesson?"
                            class="space-y-4"
                        >
                            @csrf
                            @method('PATCH')

                            <input
                                type="hidden"
                                name="edit_version"
                                value="{{ $pearl->edit_version }}"
                            >

                            <x-ui.field
                                name="published_at"
                                label="Publication date (optional)"
                                type="datetime-local"
                            />

                            <x-ui.field
                                name="current_password"
                                label="Current password"
                                type="password"
                                required
                                autocomplete="current-password"
                            />

                            <x-ui.button type="submit">
                                {{ in_array(auth()->user()->role, ['admin', 'dean'], true) && $pearl->created_by === auth()->id()
                                    ? 'Publish Pearl'
                                    : 'Approve Publication' }}
                            </x-ui.button>
                        </form>
                    </x-ui.card>
                    @else
                        <x-ui.alert variant="info">
                            Your Pearl is awaiting approval from another faculty member or an administrator.
                        </x-ui.alert>
                    @endif
                </div>
            @elseif ($pearl->content_status === 'published')
                <x-ui.card class="border-danger-strong/30 bg-danger-tint">
                    <div class="mb-5">
                        <h2 class="font-display text-lg font-extrabold text-danger-ink">
                            Retire Published Pearl
                        </h2>

                        <p class="mt-1 max-w-3xl text-sm leading-relaxed text-danger-ink/80">
                            Retirement removes this Pearl from the student library. Confirm the decision with a reason and your current password.
                        </p>
                    </div>

                    <form
                        method="POST"
                        action="{{ route(
                            'content.pearls.retire',
                            ['pearl' => $pearl->pearl_id],
                        ) }}"
                        data-confirm="Retire this lesson from the student library? Its edit history will be kept."
                        class="space-y-4"
                    >
                        @csrf
                        @method('PATCH')

                        <input
                            type="hidden"
                            name="edit_version"
                            value="{{ $pearl->edit_version }}"
                        >

                        <x-ui.field
                            name="reason"
                            id="retire-reason"
                            label="Retirement reason"
                            type="textarea"
                            :rows="4"
                            required
                            placeholder="Explain why this lesson must be retired."
                        />

                        <x-ui.field
                            name="current_password"
                            label="Current password"
                            type="password"
                            required
                            autocomplete="current-password"
                        />

                        <x-ui.button type="submit" variant="danger">
                            Retire Pearl
                        </x-ui.button>
                    </form>
                </x-ui.card>
            @else
                <x-ui.alert variant="info">
                    This retired Pearl is read-only. Its immutable edit history remains available below.
                </x-ui.alert>
            @endif

            <x-ui.card id="pearl-history" class="scroll-mt-32">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="font-display text-lg font-extrabold text-ink">
                            Immutable Edit History
                        </h2>

                        <p class="mt-1 text-sm text-ink-muted">
                            Every content and workflow change is retained for accountability.
                        </p>
                    </div>

                    <x-ui.badge variant="neutral">
                        Version {{ $pearl->edit_version }}
                    </x-ui.badge>
                </div>

                <div class="mt-5 space-y-3">
                    @forelse ($history as $edit)
                        <article class="rounded-2xl border border-line bg-surface-2 p-4">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <h3 class="font-bold text-ink">
                                    {{ str($edit->action)
                                        ->replace('_', ' ')
                                        ->title() }}
                                </h3>

                                <span class="text-xs font-semibold tabular-nums text-ink-muted">
                                    Version {{ $edit->version_before }}
                                    → {{ $edit->version_after }}
                                </span>
                            </div>

                            <p class="mt-1 text-sm text-ink-muted">
                                {{ $edit->editor?->fullName() ?? 'Former user' }}
                                <span aria-hidden="true">·</span>
                                {{ $edit->edited_at->format('M j, Y g:i A') }}
                            </p>

                            @if ($edit->reason)
                                <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-ink">
                                    {{ $edit->reason }}
                                </p>
                            @endif
                        </article>
                    @empty
                        <p class="rounded-2xl bg-surface-2 p-5 text-sm text-ink-muted">
                            No history is available.
                        </p>
                    @endforelse
                </div>

                @if ($history->hasPages())
                    <div class="mt-4 border-t border-line">
                        <x-admin.pager :paginator="$history" label="Micro Pearl edit history" />
                    </div>
                @endif

            </x-ui.card>
        </div>
    </div>
</x-app-layout>
