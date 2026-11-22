<x-app-layout>
    <x-slot name="header"><x-ui.page-header :title="'Edit '.$version->version_code" description="Save questions as you work; submit the complete version for Dean approval." /></x-slot>
    @php($locked = in_array($version->status, ['published','retired']) || $version->first_served_at || $version->sessions()->exists() || $version->questions()->whereNotNull('first_served_at')->exists())
    <div class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6">
        @if(session('status'))<x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>@endif
        @foreach(['version','expected_lock_version'] as $field)@error($field)<x-ui.alert variant="danger">{{ $message }}</x-ui.alert>@enderror @endforeach
        <div class="flex flex-wrap gap-3"><x-ui.button variant="secondary" :href="route('research-editor.index')">All versions</x-ui.button>
            @if(auth()->user()->role === 'admin')<x-ui.button variant="secondary" :href="route('admin.research-form-imports.index')">Bulk CSV entry</x-ui.button><x-ui.button variant="secondary" :href="route('admin.research-forms.index')">Publication controls</x-ui.button>@endif
        </div>
        <x-ui.card><h2 class="font-bold text-ink">Blueprint progress</h2><p class="mt-2 text-sm text-ink-muted">{{ $report['active_questions'] ?? $version->questions()->where('is_active',true)->count() }}/{{ $report['required_questions'] }} active questions. Content, source rights, and allocation are checked before review.</p>
            <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">@foreach($report['competency_rows'] as $row)<p class="rounded-xl border border-line p-3 text-sm {{ $row['matches'] ? 'text-success-ink' : 'text-ink' }}">{{ $row['competency_code'] }}: {{ $row['actual'] }}/{{ $row['expected'] }}</p>@endforeach</div>
            @if($report['errors'])<details class="mt-4"><summary class="cursor-pointer font-semibold text-warning-ink">Issues to resolve before review</summary><ul class="mt-3 list-disc space-y-2 pl-5 text-sm text-ink">@foreach($report['errors'] as $issue)<li>{{ $issue }}</li>@endforeach</ul></details>@endif
        </x-ui.card>
        @if($revisionNote)<x-ui.alert variant="warning"><strong>Dean revision notes:</strong> {{ $revisionNote }}</x-ui.alert>@endif
        @if($locked)
            <x-ui.alert>This version is read-only. Create a revised draft to change its content.</x-ui.alert>
            <form method="POST" action="{{ route('research-editor.copy', $version) }}" data-confirm="Create a separate revised draft? The original version and its assessment records will be preserved.">
                @csrf<input type="hidden" name="expected_lock_version" value="{{ $version->lock_version }}">
                <x-ui.button type="submit" variant="secondary">Create revised draft</x-ui.button>
            </form>
        @endif
        <form method="GET" class="flex items-end gap-3"><x-ui.field type="number" name="position" label="Question position" min="1" :max="$report['required_questions']" :value="$position" /><x-ui.button type="submit" variant="secondary">Open question</x-ui.button></form>
        <x-ui.card><form method="POST" action="{{ route('research-editor.save', $version) }}" class="space-y-5">@csrf @method('PUT')
            <input type="hidden" name="expected_lock_version" value="{{ $version->lock_version }}"><input type="hidden" name="form_position" value="{{ $position }}">
            <fieldset @disabled($locked) class="space-y-5">
                <legend class="mb-4 text-lg font-bold text-ink">Question {{ $position }}</legend>
                <x-ui.field name="competency_id" label="Competency" type="select" required>@foreach($domains as $domain)<optgroup label="{{ $domain->domain_name }}">@foreach($domain->competencies as $competency)<option value="{{ $competency->competency_id }}" @selected(old('competency_id', $question?->competency_id) === $competency->competency_id)>{{ $competency->competency_code }} · {{ $competency->title }}</option>@endforeach</optgroup>@endforeach</x-ui.field>
                <x-ui.field name="question_text" label="Question text" type="textarea" :value="$question?->question_text" required />
                @error('choices')<p class="text-sm text-danger-ink" role="alert">{{ $message }}</p>@enderror
                @foreach(['A','B','C','D'] as $letter)<x-ui.field :name="'choices['.$letter.']'" :label="'Choice '.$letter" :value="$question?->choices->firstWhere('choice_letter', $letter)?->choice_text" :error="$errors->first('choices.'.$letter)" required />@endforeach
                <x-ui.field name="correct_choice" label="Correct answer" type="select" required>@foreach(['A','B','C','D'] as $letter)<option value="{{ $letter }}" @selected(old('correct_choice', $question?->choices->firstWhere('is_correct', true)?->choice_letter) === $letter)>{{ $letter }}</option>@endforeach</x-ui.field>
                <x-ui.field name="hypercorrection_rationale" label="Rationale" type="textarea" :value="$question?->hypercorrection_rationale" />
                <x-ui.field name="cognitive_level" label="Cognitive level" type="select">@foreach(\App\Models\Question::COGNITIVE_LEVELS as $level)<option value="{{ $level }}" @selected(old('cognitive_level', $question?->cognitive_level) === $level)>{{ ucfirst($level) }}</option>@endforeach</x-ui.field>
                <x-ui.field name="source_reference" label="Source reference" type="textarea" :value="$question?->source_reference" />
                <x-ui.field name="copyright_permission" label="Source rights" type="select">@foreach(['pending','permission confirmed','public domain','licensed'] as $right)<option value="{{ $right }}" @selected(old('copyright_permission', $question?->copyright_permission ?? 'pending') === $right)>{{ ucfirst($right) }}</option>@endforeach</x-ui.field>
                <p class="text-sm text-ink-muted">Saving returns an unserved reviewed version to Draft and clears its previous approval.</p><x-ui.button type="submit">Save question</x-ui.button>
            </fieldset>
        </form></x-ui.card>
        @if($version->status === 'draft' && !$locked)<form method="POST" action="{{ route('research-editor.submit', $version) }}" data-confirm="Send the complete version to the Dean for review?">@csrf<input type="hidden" name="expected_lock_version" value="{{ $version->lock_version }}"><x-ui.button type="submit">Submit complete form for review</x-ui.button></form>@endif
    </div>
</x-app-layout>
