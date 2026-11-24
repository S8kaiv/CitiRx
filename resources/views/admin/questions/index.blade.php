<x-app-layout surface="admin">
    <x-slot name="header">
        <x-admin.page-header title="Question Bank" />
    </x-slot>

    @php
        $selectedSubject = $domains->firstWhere('domain_id', $filters['domain_id']);
        $showSubjects = ! request()->query->has('page')
            && request()->query('view') !== 'all'
            && ! collect($filters)->contains(fn ($value) => filled($value));
        $bankContext = array_filter([
            ...$filters,
            'view' => 'all',
            'page' => $questions->currentPage(),
        ], fn ($value) => filled($value));
    @endphp

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <nav aria-label="Question bank tools" class="flex flex-wrap items-center justify-between gap-3">
            <x-ui.button :href="route('admin.practice-questions.create')">
                Add practice question
            </x-ui.button>

            <div class="flex flex-wrap gap-2">
                <x-ui.button variant="secondary" :href="route('admin.practice-questions.index')">Practice questions</x-ui.button>
                <x-ui.button variant="ghost" :href="route('research-editor.index')">Research form editor</x-ui.button>
                <x-ui.button variant="ghost" :href="route('admin.research-form-imports.index')">Import research CSV</x-ui.button>
            </div>
        </nav>

        <form method="GET" action="{{ route('admin.questions.index') }}#question-results"
            class="space-y-4 rounded-2xl border border-line bg-surface p-4 sm:p-5">
            <h2 class="font-display text-base font-bold text-ink">Find questions</h2>
            <input type="hidden" name="view" value="all">

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5 [&>*]:min-w-0">
                <div class="xl:col-span-2">
                    <x-ui.field name="q" label="Question text" type="search"
                        :value="$filters['q']" maxlength="100" placeholder="Search question text" />
                </div>

                <x-ui.field name="domain_id" label="Subject" type="select">
                    <option value="">All subjects</option>
                    @foreach ($domains as $domain)
                        <option value="{{ $domain->domain_id }}"
                            @selected((string) $filters['domain_id'] === (string) $domain->domain_id)>
                            {{ $domain->domain_name }}
                        </option>
                    @endforeach
                </x-ui.field>

                <x-ui.field name="form" label="Research form" type="select">
                    <option value="">All forms</option>
                    <option value="pre_test_a" @selected($filters['form'] === 'pre_test_a')>Form A</option>
                    <option value="post_test_b" @selected($filters['form'] === 'post_test_b')>Form B</option>
                </x-ui.field>

                <x-ui.field name="status" label="Status" type="select">
                    <option value="">Any status</option>
                    @foreach ([
                        'editable' => 'Editable',
                        'locked' => 'Locked',
                        'validated' => 'Validated',
                        'unvalidated' => 'Unvalidated',
                        'awaiting_validation' => 'Awaiting validation',
                        'validation_issue' => 'Validation issue',
                        'served' => 'Served',
                        'unserved' => 'Never served',
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'placeholder' => 'Placeholders',
                    ] as $value => $label)
                        <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                    @endforeach
                </x-ui.field>
            </div>

            <div class="flex flex-wrap gap-2">
                <x-ui.button type="submit" variant="secondary">Apply filters</x-ui.button>
                <x-ui.button variant="ghost" :href="route('admin.questions.index', ['view' => 'all']).'#question-results'">
                    Clear filters
                </x-ui.button>
            </div>
        </form>

        @if ($showSubjects)
            <section aria-labelledby="question-subjects" class="space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 id="question-subjects" class="font-display text-xl font-extrabold text-ink">Browse by subject</h2>
                    <x-ui.button variant="secondary" :href="route('admin.questions.index', ['view' => 'all']).'#question-results'">
                        View all questions
                    </x-ui.button>
                </div>

                <ul class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    @forelse ($domains as $domain)
                        <li class="min-w-0">
                            <a href="{{ route('admin.questions.index', ['domain_id' => $domain->domain_id]) }}#question-results"
                                class="flex h-full items-center gap-3 rounded-xl border border-line bg-surface p-4 hover:border-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                                <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-primary-tint text-primary-ink">
                                    <x-admin.icon name="questions" class="size-5" aria-hidden="true" />
                                </span>
                                <span class="min-w-0 flex-1 break-words font-display text-base font-bold leading-snug text-ink">
                                    {{ $domain->domain_name }}
                                </span>
                                <x-admin.icon name="arrow" class="size-4 shrink-0 text-ink-muted" aria-hidden="true" />
                            </a>
                        </li>
                    @empty
                        <li class="col-span-full p-4 text-sm text-ink-muted">No subjects are configured yet.</li>
                    @endforelse
                </ul>
            </section>
        @else
            <section id="question-results" aria-labelledby="question-results-title" class="scroll-mt-32 space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 id="question-results-title" class="min-w-0 break-words font-display text-xl font-extrabold text-ink">
                        {{ $selectedSubject?->domain_name ?? 'All questions' }}
                    </h2>
                    <x-ui.button variant="ghost" :href="route('admin.questions.index')">All subjects</x-ui.button>
                </div>

                <div class="rounded-2xl border border-line bg-surface">
                    @if ($questions->hasPages())
                        <div class="border-b border-line">
                            <x-admin.pager :paginator="$questions" label="Question results at top" />
                        </div>
                    @endif

                    <ul class="divide-y divide-line">
                        @forelse ($questions as $question)
                            @php
                                $formName = match ($question->research_form) {
                                    'pre_test_a' => 'Form A',
                                    'post_test_b' => 'Form B',
                                    default => $question->research_form ?? 'Practice',
                                };
                                $domainName = $question->competency?->domain?->domain_name;
                                $isValidated = $question->research_validated_at !== null && $question->research_validated_by !== null;
                                $hasValidationIssue = ($question->research_validated_at === null) !== ($question->research_validated_by === null);
                                $isPlaceholder = str_starts_with($question->question_text, '[DEVELOPMENT MOCK - NOT VALIDATED]');
                            @endphp

                            <li class="grid gap-3 p-4 sm:p-5 md:grid-cols-[minmax(0,1fr)_auto] md:items-center">
                                <div class="min-w-0 space-y-2 break-words">
                                    <p class="text-sm text-ink-muted">
                                        <span class="font-bold text-ink">{{ $formName }}</span>
                                        @if ($question->form_position !== null)
                                            · Item {{ $question->form_position }}
                                        @endif
                                        @if ($question->formVersion)
                                            · {{ $question->formVersion->version_code }}
                                            ({{ $question->formVersion->is_active ? 'current' : 'historical' }})
                                        @endif
                                        @if (! $selectedSubject)
                                            · {{ $domainName ?? 'Unknown subject' }}
                                        @endif
                                        @if ($question->competency?->competency_code)
                                            · {{ $question->competency->competency_code }}
                                        @endif
                                    </p>

                                    <p class="line-clamp-3 text-base font-semibold leading-relaxed text-ink">
                                        {{ $question->question_text }}
                                    </p>

                                    <div class="flex flex-wrap gap-1.5">
                                        @if ($isPlaceholder)
                                            <x-ui.badge variant="warning">Development placeholder</x-ui.badge>
                                        @endif

                                        @if ($hasValidationIssue)
                                            <x-ui.badge variant="danger">Validation issue</x-ui.badge>
                                        @elseif ($isValidated)
                                            <x-ui.badge variant="success">Validated</x-ui.badge>
                                        @else
                                            <x-ui.badge>Unvalidated</x-ui.badge>
                                        @endif

                                        <x-ui.badge>{{ $question->contentIsFrozen() ? 'Locked' : 'Editable' }}</x-ui.badge>

                                        @if ($question->first_served_at)
                                            <x-ui.badge>Served</x-ui.badge>
                                        @endif

                                        <x-ui.badge :variant="$question->is_active ? 'neutral' : 'warning'">
                                            {{ $question->is_active ? 'Active' : 'Inactive' }}
                                        </x-ui.badge>
                                    </div>
                                </div>

                                <x-ui.button variant="secondary" class="justify-self-start"
                                    :href="route('admin.questions.show', ['question' => $question->question_id, 'bank' => $bankContext])">
                                    View question
                                </x-ui.button>
                            </li>
                        @empty
                            <li class="p-6 text-sm text-ink-muted">
                                {{ $questions->total() ? 'There are no questions on this page.' : 'No questions match these filters.' }}
                            </li>
                        @endforelse
                    </ul>

                    <div class="border-t border-line">
                        <x-admin.pager :paginator="$questions" label="Question results" />
                    </div>
                </div>
            </section>
        @endif
    </div>
</x-app-layout>
