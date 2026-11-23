<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Add practice question" description="Create one question for adaptive practice, not for the diagnostic or mock-board research forms.">
            <x-slot:actions>
                <x-ui.button variant="secondary" :href="route($routePrefix.'.imports.create')">Import practice CSV</x-ui.button>
                <x-ui.button variant="secondary" :href="route($routePrefix.'.index')">Back to practice questions</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <div class="mx-auto max-w-4xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        <x-ui.alert>Saving makes this question eligible for adaptive practice immediately. Review the answer and rationale before confirming. Question content becomes locked after it is served.</x-ui.alert>

        <x-ui.card>
            <form method="POST" action="{{ route($routePrefix.'.store') }}" class="space-y-6"
                data-confirm="Save this question and make it available for adaptive practice?">
                @csrf
                <x-ui.field name="competency_id" label="Subject and competency" type="select" required>
                    <option value="">Choose a competency</option>
                    @foreach ($domains as $domain)
                        <optgroup label="{{ $domain->domain_number }}. {{ $domain->domain_name }}">
                            @foreach ($domain->competencies as $competency)
                                <option value="{{ $competency->competency_id }}" @selected(old('competency_id') === $competency->competency_id)>{{ $competency->competency_code }} — {{ $competency->title }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </x-ui.field>
                <x-ui.field name="question_text" label="Question" type="textarea" :rows="5" maxlength="2000" required />

                <fieldset class="space-y-4">
                    <legend class="font-bold text-ink">Answer choices</legend>
                    <p class="text-sm text-ink-muted">Enter four different choices, then select the one correct answer.</p>
                    @foreach (['A', 'B', 'C', 'D'] as $letter)
                        <x-ui.field :name="'choices['.$letter.'][text]'" :id="'choice-'.$letter" :label="'Choice '.$letter" type="textarea" :rows="2" maxlength="1000" required />
                    @endforeach
                    @error('choices')<p class="text-sm text-danger-ink" role="alert">{{ $message }}</p>@enderror
                    <x-ui.field name="correct_choice" label="Correct answer" type="select" required>
                        <option value="">Choose the correct answer</option>
                        @foreach (['A', 'B', 'C', 'D'] as $letter)
                            <option value="{{ $letter }}" @selected(old('correct_choice') === $letter)>Choice {{ $letter }}</option>
                        @endforeach
                    </x-ui.field>
                </fieldset>

                <x-ui.field name="hypercorrection_rationale" label="Answer rationale" hint="Explain why the correct answer is right and address likely misconceptions." type="textarea" :rows="4" maxlength="2000" required />
                <x-ui.field name="cognitive_level" label="Cognitive level (optional)" type="select">
                    <option value="">Not assigned</option>
                    @foreach (\App\Models\Question::COGNITIVE_LEVELS as $level)
                        <option value="{{ $level }}" @selected(old('cognitive_level') === $level)>{{ ucfirst($level) }}</option>
                    @endforeach
                </x-ui.field>

                <fieldset class="space-y-4 border-t border-line pt-5">
                    <legend class="font-bold text-ink">Sources and permission</legend>
                    <x-ui.field name="source_reference" label="Source reference" hint="For an original item, identify the author and supporting references. Otherwise include the title, author, edition or URL, and relevant page." type="textarea" :rows="3" maxlength="2000" required />
                    <x-ui.field name="copyright_permission" label="Source-rights status" type="select" required>
                        <option value="">Choose the applicable permission</option>
                        @foreach (['permission confirmed' => 'Original content / permission confirmed', 'public domain' => 'Public domain', 'licensed' => 'Licensed for this use'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('copyright_permission') === $value)>{{ $label }}</option>
                        @endforeach
                    </x-ui.field>
                </fieldset>

                <div class="flex flex-wrap gap-3 border-t border-line pt-5">
                    <x-ui.button type="submit">Save practice question</x-ui.button>
                    <x-ui.button variant="secondary" :href="route($routePrefix.'.index')">Cancel</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-app-layout>
