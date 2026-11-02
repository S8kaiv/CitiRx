<x-app-layout>
    <x-slot name="header">
        @php
            $statusVariant = match ($pearl->content_status) {
                'published' => 'success',
                'in_review' => 'warning',
                'retired' => 'danger',
                default => 'neutral',
            };
        @endphp

        <x-ui.page-header
            :title="$pearl->title"
            :description="$pearl->competency->competency_code
                .' · '
                .$pearl->competency->title"
        >
            <x-slot:actions>
                <x-ui.badge :variant="$statusVariant">
                    {{ str($pearl->content_status)
                        ->replace('_', ' ')
                        ->title() }}
                </x-ui.badge>

                <x-ui.button
                    variant="secondary"
                    :href="route('content.pearls.index')"
                >
                    Back to Pearl Management
                </x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
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
                            Submission locks draft editing until a different reviewer returns or approves the Pearl.
                        </p>
                    </div>

                    <form
                        method="POST"
                        action="{{ route(
                            'content.pearls.submit-review',
                            ['pearl' => $pearl->pearl_id],
                        ) }}"
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
                            label="Submission reason"
                            type="textarea"
                            :rows="3"
                            required
                            placeholder="Explain why this content is ready for review."
                        />

                        <x-ui.button type="submit">
                            Submit for Review
                        </x-ui.button>
                    </form>
                </x-ui.card>
            @elseif ($pearl->content_status === 'in_review')
                <x-ui.alert variant="warning">
                    Review the lesson, sources, rights status, and AI disclosure before choosing an outcome.
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

                    <x-ui.card class="border-success-strong/30 bg-success-tint">
                        <div class="mb-5">
                            <h2 class="font-display text-lg font-extrabold text-success-ink">
                                Approve Publication
                            </h2>

                            <p class="mt-1 text-sm text-success-ink/80">
                                Confirm approval with your current password. Leave the date blank to publish immediately.
                            </p>
                        </div>

                        <form
                            method="POST"
                            action="{{ route(
                                'content.pearls.publish',
                                ['pearl' => $pearl->pearl_id],
                            ) }}"
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
                                name="reason"
                                label="Approval reason"
                                type="textarea"
                                :rows="4"
                                required
                                placeholder="Document why this Pearl is approved for learners."
                            />

                            <x-ui.field
                                name="current_password"
                                label="Current password"
                                type="password"
                                required
                                autocomplete="current-password"
                            />

                            <x-ui.button type="submit">
                                Approve Publication
                            </x-ui.button>
                        </form>
                    </x-ui.card>
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

            <x-ui.card>
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
                    @forelse ($pearl->edits as $edit)
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
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
