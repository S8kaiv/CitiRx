@php
    $objectives = old('learning_objectives_text', implode("\n", $pearl?->learning_objectives ?? []));
    $keyPoints = old('key_points_text', implode("\n", $pearl?->key_points ?? []));
    $pitfalls = old('common_pitfalls_text', implode("\n", $pearl?->common_pitfalls ?? []));
    $sources = old('source_references_text', implode("\n", $pearl?->source_references ?? []));
    $prompts = old('recall_prompts', $pearl?->recall_prompts ?? []) ?? [];
    $prompts = count($prompts) ? $prompts : [['question' => '', 'answer' => '']];
@endphp
@if($pearl)<input type="hidden" name="edit_version" value="{{ $pearl->edit_version }}">@endif
<p class="mb-5 text-sm text-ink-muted">Save an incomplete draft with a title and competency. Complete the lesson and source checks before submitting it for review.</p>
<div class="space-y-4" x-data="{ prompts: @js($prompts) }">
    <details open class="rounded-2xl border border-line p-4 sm:p-5">
        <summary class="min-h-11 cursor-pointer font-bold text-ink">Lesson details</summary>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2"><x-ui.field name="title" label="Title" :value="$pearl?->title" required maxlength="200" /></div>
            <div class="sm:col-span-2"><x-ui.field name="competency_id" label="Competency" type="select" required><option value="">Choose a competency</option>@foreach($domains as $domain)<optgroup label="{{ $domain->domain_name }}">@foreach($domain->competencies as $competency)<option value="{{ $competency->competency_id }}" @selected(old('competency_id', $pearl?->competency_id) === $competency->competency_id)>{{ $competency->competency_code }} · {{ $competency->title }}</option>@endforeach</optgroup>@endforeach</x-ui.field></div>
            <x-ui.field name="estimated_read_seconds" label="Estimated reading time (seconds)" type="number" min="30" max="3600" :value="$pearl?->estimated_read_seconds ?? 180" />
            <x-ui.field name="order_index" label="Order within competency" type="number" min="1" max="65535" :value="$pearl?->order_index" hint="Leave blank for the next available position." />
        </div>
    </details>
    <details @if($errors->hasAny(['mnemonic_summary','lesson_content','learning_objectives_text','key_points_text','common_pitfalls_text'])) open @endif class="rounded-2xl border border-line p-4 sm:p-5">
        <summary class="min-h-11 cursor-pointer font-bold text-ink">Learning content</summary>
        <div class="mt-4 space-y-4">
            <x-ui.field name="mnemonic_summary" label="Short summary or mnemonic" type="textarea" :rows="3" :value="$pearl?->mnemonic_summary" />
            <x-ui.field name="lesson_content" label="Lesson" type="textarea" :rows="10" :value="$pearl?->lesson_content" />
            <x-ui.field name="learning_objectives_text" label="Learning objectives" type="textarea" :value="$objectives" hint="One objective per line." />
            <x-ui.field name="key_points_text" label="Key points" type="textarea" :value="$keyPoints" hint="One point per line." />
            <x-ui.field name="common_pitfalls_text" label="Common pitfalls (optional)" type="textarea" :value="$pitfalls" />
        </div>
    </details>
    <details @if($errors->has('recall_prompts') || collect($errors->keys())->contains(fn($key) => str_starts_with($key, 'recall_prompts.'))) open @endif class="rounded-2xl border border-line p-4 sm:p-5">
        <summary class="min-h-11 cursor-pointer font-bold text-ink">Recall questions and answers</summary>
        <div class="mt-4 space-y-4">
            @foreach($errors->getMessages() as $key => $messages)@if(str_starts_with($key, 'recall_prompts'))@foreach($messages as $message)<p class="text-sm text-danger-ink" role="alert">{{ $message }}</p>@endforeach @endif @endforeach
            <input type="hidden" name="recall_prompts_present" value="1">
            <template x-for="(prompt, index) in prompts" :key="index"><fieldset class="space-y-3 border-b border-line pb-4">
                <legend class="font-semibold text-ink" x-text="'Recall prompt ' + (index + 1)"></legend>
                <label class="block text-sm font-bold text-ink" :for="'recall-question-' + index">Question</label><textarea class="ui-field-control" rows="2" :id="'recall-question-' + index" :name="'recall_prompts[' + index + '][question]'" x-model="prompt.question" maxlength="2000"></textarea>
                <label class="block text-sm font-bold text-ink" :for="'recall-answer-' + index">Answer</label><textarea class="ui-field-control" rows="3" :id="'recall-answer-' + index" :name="'recall_prompts[' + index + '][answer]'" x-model="prompt.answer" maxlength="4000"></textarea>
                <x-ui.button type="button" variant="secondary" x-on:click="prompts.splice(index, 1)">Remove prompt</x-ui.button>
            </fieldset></template>
            <x-ui.button type="button" variant="secondary" x-bind:disabled="prompts.length >= 30" x-on:click="prompts.push({ question: '', answer: '' })">Add recall prompt</x-ui.button>
        </div>
    </details>
    <details @if($errors->hasAny(['source_references_text','rights_status'])) open @endif class="rounded-2xl border border-line p-4 sm:p-5">
        <summary class="min-h-11 cursor-pointer font-bold text-ink">Sources and rights</summary>
        <div class="mt-4 space-y-4"><x-ui.field name="source_references_text" label="References" type="textarea" :value="$sources" hint="One reference per line; include enough detail for the reviewer to locate it." />
            <x-ui.field name="rights_status" label="Rights status" type="select">@foreach(['pending' => 'Pending', 'original' => 'Original content', 'authorized' => 'Use authorized', 'public_domain' => 'Public domain'] as $value => $label)<option value="{{ $value }}" @selected(old('rights_status', $pearl?->rights_status ?? 'pending') === $value)>{{ $label }}</option>@endforeach</x-ui.field>
            <x-ui.field name="reason" label="Internal note (optional)" type="textarea" :rows="2" />
        </div>
    </details>
</div>
<x-ui.button type="submit" class="mt-6">Save draft</x-ui.button>
