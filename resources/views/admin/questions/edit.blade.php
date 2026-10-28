<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-display text-xl sm:text-2xl lg:text-3xl font-extrabold tracking-tight text-slate-900">
                    Edit Question
                </h2>
                <p class="hidden sm:block text-xs font-semibold uppercase tracking-wider text-muted-ink">
                    Version {{ $question->edit_version }}
                </p>
            </div>

            <a href="{{ route('admin.questions.show', ['question' => $question->question_id]) }}"
                style="--lip: #CBD5E1;"
                class="btn-press rounded-xl border-2 border-b-4 border-slate-300 bg-white px-4 py-2 font-display text-sm font-bold text-slate-700 shadow-2xs transition hover:bg-slate-50">
                Cancel
            </a>
        </div>
    </x-slot>

    <div class="py-6 font-sans text-slate-900 antialiased">
        <div class="mx-auto max-w-4xl space-y-5 px-4 sm:px-6 lg:px-8">
            @if (session('error'))
                <div role="alert" class="rounded-2xl border-2 border-b-4 border-rose-300 bg-rose-50 p-4 font-display text-sm font-bold text-rose-800 shadow-2xs">
                    {{ session('error') }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.questions.update', ['question' => $question->question_id]) }}"
                class="space-y-6 rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-5 sm:p-6 shadow-sm">
                @csrf
                @method('PUT')

                <input type="hidden" name="edit_version" value="{{ $question->edit_version }}">

                <div class="rounded-2xl border-2 border-slate-100 bg-slate-50 p-4 text-xs font-semibold text-slate-600">
                    {{ $question->research_form ?? 'Practice question' }}

                    @if ($question->form_position !== null)
                        · Position {{ $question->form_position }}
                    @endif

                    · {{ $question->competency?->domain?->domain_name }}
                    · {{ $question->competency?->competency_code }}
                </div>

                <div>
                    <label for="question_text" class="mb-1 block text-xs font-extrabold uppercase tracking-wider text-muted-ink">
                        Question text
                    </label>

                    <textarea id="question_text" name="question_text" rows="5" required maxlength="2000"
                        class="w-full rounded-xl border-2 border-slate-200 text-sm font-medium focus:border-primary focus:ring-primary">{{ old('question_text', $question->question_text) }}</textarea>

                    @error('question_text')
                        <p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                @php
                    $letters = ['A', 'B', 'C', 'D'];

                    $correctLetter = $question->choices->firstWhere('is_correct', true)?->choice_letter;
                @endphp

                <fieldset>
                    <legend class="mb-3 text-xs font-extrabold uppercase tracking-wider text-muted-ink">
                        Answer choices
                    </legend>

                    <div class="space-y-4">
                        @foreach ($letters as $letter)
                            @php
                                $choice = $question->choices->firstWhere('choice_letter', $letter);
                            @endphp

                            <div class="rounded-2xl border-2 border-slate-200 p-4">
                                <div class="flex gap-3">
                                    <label class="mt-3 flex shrink-0 items-center gap-2">
                                        <input type="radio" name="correct_choice" value="{{ $letter }}" required
                                            @checked(old('correct_choice', $correctLetter) === $letter)
                                            class="border-slate-300 text-primary focus:ring-primary">

                                        <span class="font-display font-black text-slate-700">{{ $letter }}</span>
                                    </label>

                                    <div class="flex-1">
                                        <label for="choice_{{ $letter }}" class="sr-only">Choice {{ $letter }}</label>

                                        <textarea id="choice_{{ $letter }}" name="choices[{{ $letter }}][text]" rows="2" required maxlength="1000"
                                            class="w-full rounded-xl border-2 border-slate-200 text-sm font-medium focus:border-primary focus:ring-primary">{{ old("choices.{$letter}.text", $choice?->choice_text) }}</textarea>

                                        @error("choices.{$letter}.text")
                                            <p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @error('correct_choice')
                        <p class="mt-2 text-xs font-bold text-rose-600">{{ $message }}</p>
                    @enderror

                    @error('choices')
                        <p class="mt-2 text-xs font-bold text-rose-600">{{ $message }}</p>
                    @enderror
                </fieldset>

                <div>
                    <label for="hypercorrection_rationale" class="mb-1 block text-xs font-extrabold uppercase tracking-wider text-muted-ink">
                        Rationale
                    </label>

                    <textarea id="hypercorrection_rationale" name="hypercorrection_rationale" rows="4" maxlength="2000"
                        @required($question->research_form !== null)
                        class="w-full rounded-xl border-2 border-slate-200 text-sm font-medium focus:border-primary focus:ring-primary">{{ old('hypercorrection_rationale', $question->hypercorrection_rationale) }}</textarea>

                    @error('hypercorrection_rationale')
                        <p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="cognitive_level" class="mb-1 block text-xs font-extrabold uppercase tracking-wider text-muted-ink">
                        Cognitive level
                    </label>

                    <select id="cognitive_level" name="cognitive_level" @required($question->research_form !== null)
                        class="w-full rounded-xl border-2 border-slate-200 text-sm font-medium focus:border-primary focus:ring-primary">
                        @if ($question->research_form === null)
                            <option value="">Not assigned</option>
                        @endif

                        @foreach (\App\Models\Question::COGNITIVE_LEVELS as $level)
                            <option value="{{ $level }}"
                                @selected(old('cognitive_level', $question->cognitive_level) === $level)>
                                {{ ucfirst($level) }}
                            </option>
                        @endforeach
                    </select>

                    @error('cognitive_level')
                        <p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="change_reason" class="mb-1 block text-xs font-extrabold uppercase tracking-wider text-muted-ink">
                        Reason for this revision
                    </label>

                    <textarea id="change_reason" name="change_reason" rows="3" required minlength="5" maxlength="500"
                        class="w-full rounded-xl border-2 border-slate-200 text-sm font-medium focus:border-primary focus:ring-primary"
                        placeholder="Example: Reworded the stem after faculty review identified ambiguity.">{{ old('change_reason') }}</textarea>

                    @error('change_reason')
                        <p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-wrap items-center gap-3 border-t border-slate-100 pt-5">
                    <button type="submit" style="--lip: rgb(var(--color-primary-lip));"
                        class="btn-press rounded-2xl bg-primary px-6 py-3 font-display text-sm font-bold text-white shadow-md">
                        Save and Record Audit
                    </button>

                    <a href="{{ route('admin.questions.show', ['question' => $question->question_id]) }}"
                        style="--lip: #CBD5E1;"
                        class="btn-press rounded-xl border-2 border-b-4 border-slate-300 bg-white px-5 py-2.5 font-display text-sm font-bold text-slate-700 shadow-2xs transition hover:bg-slate-50">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
