@php
    $objectives = old(
        'learning_objectives_text',
        implode("\n", $pearl?->learning_objectives ?? []),
    );

    $keyPoints = old(
        'key_points_text',
        implode("\n", $pearl?->key_points ?? []),
    );

    $pitfalls = old(
        'common_pitfalls_text',
        implode("\n", $pearl?->common_pitfalls ?? []),
    );

    $sources = old(
        'source_references_text',
        implode("\n", $pearl?->source_references ?? []),
    );

    $recallPrompts = old(
        'recall_prompts_text',
        collect($pearl?->recall_prompts ?? [])
            ->map(
                fn ($prompt) => ($prompt['question'] ?? '')
                    .' | '
                    .($prompt['answer'] ?? ''),
            )
            ->implode("\n"),
    );
@endphp

@if ($pearl)
    <input
        type="hidden"
        name="edit_version"
        value="{{ $pearl->edit_version }}"
    >
@endif

<div class="space-y-8">
    <section aria-labelledby="pearl-content-heading">
        <div class="border-b border-line pb-3">
            <h2
                id="pearl-content-heading"
                class="font-display text-lg font-extrabold text-ink"
            >
                Lesson Content
            </h2>

            <p class="mt-1 text-sm text-ink-muted">
                Define the Pearl's competency, lesson, and learner-facing summary.
            </p>
        </div>

        <div class="mt-5 grid gap-5 md:grid-cols-2">
            <div class="md:col-span-2">
                <x-ui.field
                    name="competency_id"
                    label="Competency"
                    type="select"
                    required
                >
                    @foreach ($domains as $domain)
                        <optgroup label="Domain {{ $domain->domain_number }} — {{ $domain->domain_name }}">
                            @foreach ($domain->competencies as $competency)
                                <option
                                    value="{{ $competency->competency_id }}"
                                    @selected(
                                        old(
                                            'competency_id',
                                            $pearl?->competency_id,
                                        ) === $competency->competency_id
                                    )
                                >
                                    {{ $competency->competency_code }}
                                    — {{ $competency->title }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </x-ui.field>
            </div>

            <div class="md:col-span-2">
                <x-ui.field
                    name="title"
                    label="Title"
                    :value="$pearl?->title"
                    maxlength="200"
                    required
                />
            </div>

            <div class="md:col-span-2">
                <x-ui.field
                    name="mnemonic_summary"
                    label="Mnemonic summary"
                    type="textarea"
                    :rows="3"
                    :value="$pearl?->mnemonic_summary"
                    hint="Use a concise memory aid. Do not include unsupported clinical claims."
                />
            </div>

            <div class="md:col-span-2">
                <x-ui.field
                    name="lesson_content"
                    label="Detailed lesson"
                    type="textarea"
                    :rows="12"
                    :value="$pearl?->lesson_content"
                    hint="Plain text only. HTML is intentionally escaped."
                />
            </div>
        </div>
    </section>

    <section aria-labelledby="pearl-structure-heading">
        <div class="border-b border-line pb-3">
            <h2
                id="pearl-structure-heading"
                class="font-display text-lg font-extrabold text-ink"
            >
                Learning Structure
            </h2>

            <p class="mt-1 text-sm text-ink-muted">
                Enter one item per line so the lesson remains scannable and reusable.
            </p>
        </div>

        <div class="mt-5 grid gap-5 md:grid-cols-2">
            @foreach ([
                'learning_objectives_text' => [
                    'Learning objectives',
                    $objectives,
                ],
                'key_points_text' => [
                    'Key points',
                    $keyPoints,
                ],
                'common_pitfalls_text' => [
                    'Common pitfalls',
                    $pitfalls,
                ],
                'source_references_text' => [
                    'Source references',
                    $sources,
                ],
            ] as $name => [$label, $value])
                <x-ui.field
                    :name="$name"
                    :label="$label"
                    type="textarea"
                    :rows="6"
                    :value="$value"
                    hint="Enter one item per line."
                />
            @endforeach

            <div class="md:col-span-2">
                <x-ui.field
                    name="recall_prompts_text"
                    label="Recall prompts"
                    type="textarea"
                    :rows="6"
                    :value="$recallPrompts"
                    hint="Enter one prompt per line using: Question | Answer"
                />
            </div>
        </div>
    </section>

    <section aria-labelledby="pearl-governance-heading">
        <div class="border-b border-line pb-3">
            <h2
                id="pearl-governance-heading"
                class="font-display text-lg font-extrabold text-ink"
            >
                Publishing and Governance
            </h2>

            <p class="mt-1 text-sm text-ink-muted">
                Record source rights, display order, and estimated reading time.
            </p>
        </div>

        <div class="mt-5 grid gap-5 md:grid-cols-2">
            <x-ui.field
                name="estimated_read_seconds"
                label="Estimated reading time (seconds)"
                type="number"
                :value="$pearl?->estimated_read_seconds ?? 120"
                min="30"
                max="3600"
                required
            />

            <x-ui.field
                name="order_index"
                label="Display order"
                type="number"
                :value="$pearl?->order_index ?? 1"
                min="1"
                required
            />

            <x-ui.field
                name="rights_status"
                label="Rights status"
                type="select"
                required
            >
                @foreach ([
                    'pending' => 'Pending',
                    'original' => 'Original content',
                    'authorized' => 'Use authorized',
                    'public_domain' => 'Public domain',
                ] as $value => $label)
                    <option
                        value="{{ $value }}"
                        @selected(
                            old(
                                'rights_status',
                                $pearl?->rights_status ?? 'pending',
                            ) === $value
                        )
                    >
                        {{ $label }}
                    </option>
                @endforeach
            </x-ui.field>

            @if ($pearl)
                <details class="md:col-span-2 rounded-2xl border border-line bg-surface-2 p-4">
                    <summary class="cursor-pointer font-bold text-ink">
                        Add an internal edit note
                        <span class="font-normal text-ink-muted">(optional)</span>
                    </summary>
                    <div class="mt-4">
                        <x-ui.field
                            name="reason"
                            label="Internal Edit Note"
                            type="textarea"
                            :rows="3"
                            hint="Use this only when additional context would help future reviewers."
                        />
                    </div>
                </details>
            @endif
        </div>
    </section>
</div>

@if ($errors->any())
    <x-ui.alert variant="danger" class="mt-6">
        <div>
            <p class="font-bold">
                Review the following fields:
            </p>

            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </x-ui.alert>
@endif

<x-ui.button type="submit" class="mt-6">
    {{ $pearl ? 'Save Changes' : 'Create Draft' }}
</x-ui.button>
