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

<div class="grid gap-5 md:grid-cols-2">
    <div class="md:col-span-2">
        <label class="text-sm font-bold text-slate-700">
            Competency
        </label>

        <select
            name="competency_id"
            required
            class="mt-2 w-full rounded-xl border-slate-300"
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
        </select>
    </div>

    <div class="md:col-span-2">
        <label class="text-sm font-bold text-slate-700">
            Title
        </label>

        <input
            name="title"
            value="{{ old('title', $pearl?->title) }}"
            required
            maxlength="200"
            class="mt-2 w-full rounded-xl border-slate-300"
        >
    </div>

    <div class="md:col-span-2">
        <label class="text-sm font-bold text-slate-700">
            Mnemonic summary
        </label>

        <textarea
            name="mnemonic_summary"
            rows="3"
            class="mt-2 w-full rounded-xl border-slate-300"
        >{{ old('mnemonic_summary', $pearl?->mnemonic_summary) }}</textarea>
    </div>

    <div class="md:col-span-2">
        <label class="text-sm font-bold text-slate-700">
            Detailed lesson
        </label>

        <textarea
            name="lesson_content"
            rows="12"
            class="mt-2 w-full rounded-xl border-slate-300"
        >{{ old('lesson_content', $pearl?->lesson_content) }}</textarea>

        <p class="mt-1 text-xs text-slate-500">
            Plain text only. HTML is intentionally escaped.
        </p>
    </div>

    @foreach ([
        'learning_objectives_text' => ['Learning objectives', $objectives],
        'key_points_text' => ['Key points', $keyPoints],
        'common_pitfalls_text' => ['Common pitfalls', $pitfalls],
        'source_references_text' => ['Source references', $sources],
    ] as $name => [$label, $value])
        <div>
            <label class="text-sm font-bold text-slate-700">
                {{ $label }}
            </label>

            <textarea
                name="{{ $name }}"
                rows="6"
                class="mt-2 w-full rounded-xl border-slate-300"
            >{{ $value }}</textarea>

            <p class="mt-1 text-xs text-slate-500">
                Enter one item per line.
            </p>
        </div>
    @endforeach

    <div class="md:col-span-2">
        <label class="text-sm font-bold text-slate-700">
            Recall prompts
        </label>

        <textarea
            name="recall_prompts_text"
            rows="6"
            class="mt-2 w-full rounded-xl border-slate-300"
        >{{ $recallPrompts }}</textarea>

        <p class="mt-1 text-xs text-slate-500">
            One per line using: Question | Answer
        </p>
    </div>

    <div>
        <label class="text-sm font-bold text-slate-700">
            Estimated reading time
        </label>

        <input
            type="number"
            name="estimated_read_seconds"
            min="30"
            max="3600"
            value="{{ old(
                'estimated_read_seconds',
                $pearl?->estimated_read_seconds ?? 120,
            ) }}"
            required
            class="mt-2 w-full rounded-xl border-slate-300"
        >
    </div>

    <div>
        <label class="text-sm font-bold text-slate-700">
            Display order
        </label>

        <input
            type="number"
            name="order_index"
            min="1"
            value="{{ old('order_index', $pearl?->order_index ?? 1) }}"
            required
            class="mt-2 w-full rounded-xl border-slate-300"
        >
    </div>

    <div>
        <label class="text-sm font-bold text-slate-700">
            Rights status
        </label>

        <select
            name="rights_status"
            required
            class="mt-2 w-full rounded-xl border-slate-300"
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
        </select>
    </div>

    <div class="space-y-3">
        <label class="flex items-center gap-2">
            <input
                type="checkbox"
                name="is_ai_generated"
                value="1"
                @checked(old('is_ai_generated', $pearl?->is_ai_generated))
            >
            <span class="text-sm font-bold">
                AI-assisted content
            </span>
        </label>

        <label class="flex items-center gap-2">
            <input
                type="checkbox"
                name="ai_enrichment_allowed"
                value="1"
                @checked(old(
                    'ai_enrichment_allowed',
                    $pearl?->ai_enrichment_allowed,
                ))
            >
            <span class="text-sm font-bold">
                AI enrichment is authorized
            </span>
        </label>
    </div>

    @foreach ([
        'generation_provider' => 'AI provider',
        'generation_model' => 'AI model',
        'prompt_version' => 'Prompt version',
    ] as $name => $label)
        <div>
            <label class="text-sm font-bold text-slate-700">
                {{ $label }}
            </label>

            <input
                name="{{ $name }}"
                value="{{ old(
                    $name,
                    data_get(
                        $pearl?->generation_metadata,
                        str_replace(
                            ['generation_', 'prompt_version'],
                            ['', 'prompt_version'],
                            $name,
                        ),
                    ),
                ) }}"
                class="mt-2 w-full rounded-xl border-slate-300"
            >
        </div>
    @endforeach

    @if ($pearl)
        <div class="md:col-span-2">
            <label class="text-sm font-bold text-slate-700">
                Reason for change
            </label>

            <textarea
                name="reason"
                rows="3"
                required
                class="mt-2 w-full rounded-xl border-slate-300"
            >{{ old('reason') }}</textarea>
        </div>
    @endif
</div>

@if ($errors->any())
    <div class="mt-5 rounded-xl border border-red-200 bg-red-50 p-4">
        <ul class="list-disc pl-5 text-sm text-red-800">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<button
    class="mt-6 rounded-xl bg-primary px-5 py-3 font-bold text-white"
>
    {{ $pearl ? 'Save Changes' : 'Create Draft' }}
</button>
