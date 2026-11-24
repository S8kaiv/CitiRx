<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Add practice question" description="Create a question for adaptive practice." />
    </x-slot>

    <div class="mx-auto max-w-5xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        <nav aria-label="Practice question navigation" class="flex flex-wrap items-center gap-2">
            @if ($routePrefix === 'admin.practice-questions')
                <x-ui.button variant="ghost" :href="route('admin.questions.index')">Question Bank</x-ui.button>
            @endif

            <x-ui.button variant="ghost" :href="route($routePrefix.'.index')">Practice Questions</x-ui.button>
            <x-ui.button variant="secondary" :href="route($routePrefix.'.imports.create')">Import practice CSV</x-ui.button>
        </nav>

        <x-ui.alert>
            Saved questions enter the adaptive practice pool immediately. Content locks after the question is served.
        </x-ui.alert>

        <x-ui.card>
            <form method="POST" action="{{ route($routePrefix.'.store') }}" class="space-y-6"
                data-confirm="Save this question and make it available for adaptive practice?">
                @csrf

                <fieldset class="min-w-0 space-y-4">
                    <legend class="mb-3 font-display text-lg font-extrabold text-ink">Question details</legend>

                    <div class="grid gap-4 sm:grid-cols-2 [&>*]:min-w-0">
                        <x-ui.field name="competency_id" label="Subject and competency" type="select" required>
                            <option value="">Choose a competency</option>
                            @foreach ($domains as $domain)
                                <optgroup label="{{ $domain->domain_number }}. {{ $domain->domain_name }}">
                                    @foreach ($domain->competencies as $competency)
                                        <option value="{{ $competency->competency_id }}"
                                            @selected((string) old('competency_id') === (string) $competency->competency_id)>
                                            {{ $competency->competency_code }} — {{ $competency->title }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </x-ui.field>

                        <x-ui.field name="cognitive_level" label="Cognitive level (optional)" type="select">
                            <option value="">Not assigned</option>
                            @foreach (\App\Models\Question::COGNITIVE_LEVELS as $level)
                                <option value="{{ $level }}" @selected(old('cognitive_level') === $level)>{{ ucfirst($level) }}</option>
                            @endforeach
                        </x-ui.field>
                    </div>

                    <x-ui.field name="question_text" label="Question text" type="textarea" :rows="4" maxlength="2000" required />
                </fieldset>

                <fieldset class="min-w-0 space-y-4">
                    <legend class="mb-3 font-display text-lg font-extrabold text-ink">Answers and rationale</legend>

                    <div class="grid gap-4 sm:grid-cols-2 [&>*]:min-w-0">
                        @foreach (['A', 'B', 'C', 'D'] as $letter)
                            <x-ui.field :name="'choices['.$letter.'][text]'" :id="'choice-'.$letter"
                                :label="'Choice '.$letter" type="textarea" :rows="2" maxlength="1000" required />
                        @endforeach
                    </div>

                    @error('choices')
                        <p class="text-sm text-danger-ink" role="alert">{{ $message }}</p>
                    @enderror

                    <x-ui.field name="correct_choice" label="Correct answer" type="select" required>
                        <option value="">Choose the correct answer</option>
                        @foreach (['A', 'B', 'C', 'D'] as $letter)
                            <option value="{{ $letter }}" @selected(old('correct_choice') === $letter)>Choice {{ $letter }}</option>
                        @endforeach
                    </x-ui.field>

                    <x-ui.field name="hypercorrection_rationale" label="Answer rationale"
                        hint="Explain why the correct answer is right and address likely misconceptions."
                        type="textarea" :rows="4" maxlength="2000" required />
                </fieldset>

                <fieldset class="min-w-0 space-y-4">
                    <legend class="mb-3 font-display text-lg font-extrabold text-ink">Source and permission</legend>

                    <x-ui.field name="source_reference" label="Source reference"
                        hint="For an original item, identify the author and supporting references. Otherwise include the title, author, edition or URL, and relevant page."
                        type="textarea" :rows="3" maxlength="2000" required />

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
