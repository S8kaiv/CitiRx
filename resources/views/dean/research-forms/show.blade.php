<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="'Review '.$version->version_code" description="Dean review · whole-version approval" />
    </x-slot>

    <div class="mx-auto max-w-5xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        <x-ui.button variant="secondary" :href="route('dean.research-forms.index')">
            Back to review list
        </x-ui.button>

        @if (session('status'))
            <x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>
        @endif
        @if (session('error'))
            <x-ui.alert variant="danger">{{ session('error') }}</x-ui.alert>
        @endif
        @if ($errors->any())
            <x-ui.alert variant="danger">{{ $errors->first() }}</x-ui.alert>
        @endif

        <x-ui.card>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="font-display text-xl font-extrabold text-ink">Form readiness</h2>
                    <p class="mt-1 text-sm text-ink-muted">
                        {{ $questions->count() }}/{{ $report['required_questions'] }} questions ·
                        {{ $report['fully_validated'] }} approved ·
                        {{ str($version->status)->replace('_', ' ')->title() }}
                    </p>
                </div>
                <x-ui.badge :variant="$report['errors'] === [] ? 'success' : 'warning'">
                    {{ $report['errors'] === [] ? 'Structurally ready' : 'Needs corrections' }}
                </x-ui.badge>
            </div>

            @if ($report['errors'] !== [])
                <ul class="mt-4 list-inside list-disc space-y-1 text-sm text-danger-ink">
                    @foreach ($report['errors'] as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif

            <p class="mt-4 text-sm text-ink-muted">
                Expand each item to check its answer, rationale, source, and rights. The final approval applies to the entire unchanged form.
            </p>
        </x-ui.card>

        <x-ui.card>
            <h2 class="font-display text-xl font-extrabold text-ink">Blueprint allocation: {{ $report['required_questions'] }} questions</h2>
            <p class="mt-2 text-sm text-ink-muted">
                This allocation follows the current six-domain TOS, rounded to whole questions. It is provisional until you approve the complete form.
            </p>

            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-line text-ink-muted">
                        <tr>
                            <th scope="col" class="py-2 pr-4">Domain</th>
                            <th scope="col" class="py-2 pr-4">Target</th>
                            <th scope="col" class="py-2">Imported</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line text-ink">
                        @foreach ($report['domain_rows'] as $row)
                            <tr>
                                <th scope="row" class="py-2 pr-4 font-semibold">{{ $row['domain_number'] }}. {{ $row['domain_name'] }}</th>
                                <td class="py-2 pr-4">{{ $row['expected'] }}</td>
                                <td class="py-2 {{ $row['matches'] ? 'text-success-ink' : 'text-danger-ink' }}">{{ $row['actual'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <details class="mt-4 rounded-xl border border-line p-3">
                <summary class="cursor-pointer font-semibold text-ink">View all competency targets</summary>
                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                    @foreach ($report['competency_rows'] as $row)
                        <p class="rounded-lg bg-surface-2 px-3 py-2 text-sm text-ink">
                            <strong>{{ $row['competency_code'] }}</strong> · {{ $row['actual'] }}/{{ $row['expected'] }} questions
                        </p>
                    @endforeach
                </div>
            </details>
        </x-ui.card>

        <div class="space-y-3" aria-label="Research form questions">
            @foreach ($questions as $question)
                <details class="rounded-2xl border border-line bg-surface p-4 sm:p-5">
                    <summary class="cursor-pointer font-bold text-ink">
                        {{ $question->form_position }}. {{ $question->question_text }}
                    </summary>

                    <div class="mt-4 space-y-4 border-t border-line pt-4 text-sm text-ink">
                        <p class="text-ink-muted">
                            Domain {{ $question->competency?->domain?->domain_number }} ·
                            {{ $question->competency?->competency_code }} ·
                            {{ str($question->cognitive_level)->title() }}
                        </p>

                        <ol class="space-y-2">
                            @foreach ($question->choices as $choice)
                                <li class="rounded-xl border border-line p-3 {{ $choice->is_correct ? 'bg-success-tint text-success-ink' : 'bg-surface-2' }}">
                                    <strong>{{ $choice->choice_letter }}.</strong>
                                    {{ $choice->choice_text }}
                                    @if ($choice->is_correct) <span class="font-bold">(Correct)</span> @endif
                                    @if ($choice->distractor_fallacy_note)
                                        <span class="mt-1 block text-xs">Distractor note: {{ $choice->distractor_fallacy_note }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ol>

                        <p><strong>Rationale:</strong> {{ $question->hypercorrection_rationale }}</p>
                        <p><strong>Source:</strong> {{ $question->source_reference ?: 'Missing' }}</p>
                        <p><strong>Rights:</strong> {{ $question->copyright_permission ?: 'Unresolved' }}</p>
                    </div>
                </details>
            @endforeach
        </div>

        @if ($version->status === \App\Models\ResearchFormVersion::STATUS_IN_REVIEW)
            <div class="grid gap-5 lg:grid-cols-2">
                <x-ui.card class="border-success-strong/30 bg-success-tint">
                    <h2 class="font-display text-lg font-extrabold text-success-ink">Approve all {{ $report['required_questions'] }} questions</h2>
                    <p class="mt-2 text-sm text-success-ink">
                        Approval is all or nothing. The system checks the blueprint, every question, its source and rights, and whether anything changed while you reviewed it.
                    </p>

                    <form method="POST" action="{{ route('dean.research-forms.approve', $version) }}" data-confirm="Approve this complete, unchanged form, including its answers, sources, rights, and blueprint allocation?" class="mt-5 space-y-4">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="expected_lock_version" value="{{ $version->lock_version }}">
                        <input type="hidden" name="expected_digest" value="{{ $digest }}">

                        <input type="hidden" name="review_confirmed" value="1">
                        @error('review')<p class="text-danger-ink" role="alert">{{ $message }}</p>@enderror
                        <x-ui.button type="submit">Approve complete form</x-ui.button>
                    </form>
                </x-ui.card>

                <x-ui.card>
                    <h2 class="font-display text-lg font-extrabold text-ink">Return for revision</h2>
                    <p class="mt-2 text-sm text-ink-muted">Tell the authors which positions or concepts need correction.</p>

                    <form method="POST" action="{{ route('dean.research-forms.return', $version) }}" data-confirm="Return this version to Draft with these revision notes?" class="mt-5 space-y-4">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="expected_lock_version" value="{{ $version->lock_version }}">
                        <x-ui.field name="reason" label="Required corrections" type="textarea" :rows="5" required />
                        <x-ui.button type="submit" variant="secondary">Return to draft</x-ui.button>
                    </form>
                </x-ui.card>
            </div>
        @endif
    </div>
</x-app-layout>
