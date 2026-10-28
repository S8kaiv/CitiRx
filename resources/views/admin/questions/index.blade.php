<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-display text-xl sm:text-2xl lg:text-3xl font-extrabold tracking-tight text-slate-900">
                Question Bank
            </h2>
            <p class="hidden sm:block text-xs font-semibold uppercase tracking-wider text-muted-ink">
                Review, revise, freeze and retire assessment questions
            </p>
        </div>
    </x-slot>

    <div class="py-6 font-sans text-slate-900 antialiased">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div role="status" class="rounded-2xl border-2 border-b-4 border-emerald-300 bg-emerald-50 p-4 font-display text-sm font-bold text-emerald-800 shadow-2xs">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div role="alert" class="rounded-2xl border-2 border-b-4 border-rose-300 bg-rose-50 p-4 font-display text-sm font-bold text-rose-800 shadow-2xs">
                    {{ session('error') }}
                </div>
            @endif

            <form method="GET" action="{{ route('admin.questions.index') }}"
                class="grid grid-cols-1 gap-3 rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-5 sm:p-6 shadow-sm md:grid-cols-2 xl:grid-cols-6">
                <div class="xl:col-span-2">
                    <label for="q" class="mb-1 block text-xs font-extrabold uppercase tracking-wider text-muted-ink">Search</label>
                    <input id="q" type="search" name="q" maxlength="100" value="{{ $filters['q'] }}"
                        placeholder="Search question text..."
                        class="w-full rounded-xl border-2 border-slate-200 text-sm font-medium focus:border-primary focus:ring-primary">
                </div>

                <div>
                    <label for="form" class="mb-1 block text-xs font-extrabold uppercase tracking-wider text-muted-ink">Form</label>
                    <select id="form" name="form"
                        class="w-full rounded-xl border-2 border-slate-200 text-sm font-medium focus:border-primary focus:ring-primary">
                        <option value="">All forms</option>
                        <option value="pre_test_a" @selected($filters['form'] === 'pre_test_a')>Form A</option>
                        <option value="post_test_b" @selected($filters['form'] === 'post_test_b')>Form B</option>
                    </select>
                </div>

                <div>
                    <label for="domain_id" class="mb-1 block text-xs font-extrabold uppercase tracking-wider text-muted-ink">Domain</label>
                    <select id="domain_id" name="domain_id"
                        class="w-full rounded-xl border-2 border-slate-200 text-sm font-medium focus:border-primary focus:ring-primary">
                        <option value="">All domains</option>
                        @foreach ($domains as $domain)
                            <option value="{{ $domain->domain_id }}"
                                @selected((string) $filters['domain_id'] === (string) $domain->domain_id)>
                                {{ $domain->domain_number }}. {{ $domain->domain_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="status" class="mb-1 block text-xs font-extrabold uppercase tracking-wider text-muted-ink">Status</label>
                    <select id="status" name="status"
                        class="w-full rounded-xl border-2 border-slate-200 text-sm font-medium focus:border-primary focus:ring-primary">
                        <option value="">Any status</option>
                        @foreach ([
                            'editable' => 'Editable',
                            'locked' => 'Locked',
                            'validated' => 'Validated',
                            'unvalidated' => 'Unvalidated',
                            'validation_issue' => 'Validation issue',
                            'served' => 'Served',
                            'unserved' => 'Never served',
                            'active' => 'Active',
                            'inactive' => 'Inactive',
                            'placeholder' => 'Placeholders',
                        ] as $value => $label)
                            <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" style="--lip: rgb(var(--color-primary-lip));"
                        class="btn-press flex-1 rounded-xl bg-primary px-4 py-2.5 font-display text-sm font-bold text-white shadow-md">
                        Filter
                    </button>

                    <a href="{{ route('admin.questions.index') }}" style="--lip: #CBD5E1;"
                        class="btn-press rounded-xl border-2 border-b-4 border-slate-300 bg-white px-4 py-2 font-display text-sm font-bold text-slate-700 shadow-2xs transition hover:bg-slate-50">
                        Clear
                    </a>
                </div>
            </form>

            <div class="overflow-hidden rounded-3xl border-2 border-b-4 border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs font-extrabold uppercase tracking-wider text-muted-ink">
                            <tr>
                                <th class="px-4 py-4">Form</th>
                                <th class="px-4 py-4">Domain</th>
                                <th class="px-4 py-4">Question</th>
                                <th class="px-4 py-4">State</th>
                                <th class="px-4 py-4">Availability</th>
                                <th class="px-4 py-4 text-right">Action</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            @forelse ($questions as $question)
                                @php
                                    $isValidated = $question->research_validated_at !== null && $question->research_validated_by !== null;

                                    $hasValidationIssue = ($question->research_validated_at === null) !== ($question->research_validated_by === null);

                                    $isLocked = $question->contentIsFrozen();

                                    $isPlaceholder = str_starts_with($question->question_text, '[DEVELOPMENT MOCK - NOT VALIDATED]');
                                @endphp

                                <tr class="align-top hover:bg-slate-50/70">
                                    <td class="whitespace-nowrap px-4 py-4">
                                        <div class="font-display font-black text-slate-800">{{ $question->research_form ?? 'Practice' }}</div>

                                        @if ($question->form_position !== null)
                                            <div class="text-xs text-muted-ink">Position {{ $question->form_position }}</div>
                                        @endif
                                    </td>

                                    <td class="px-4 py-4 text-xs text-slate-600">
                                        <div class="font-semibold">{{ $question->competency?->domain?->domain_name ?? 'Unknown domain' }}</div>
                                        <div class="mt-1 font-mono">{{ $question->competency?->competency_code }}</div>
                                    </td>

                                    <td class="max-w-xl px-4 py-4">
                                        <p class="line-clamp-3 font-medium text-slate-900">{{ $question->question_text }}</p>

                                        @if ($isPlaceholder)
                                            <span class="mt-2 inline-flex rounded-full bg-amber-100 px-2 py-1 text-[10px] font-extrabold uppercase text-amber-800">
                                                Development placeholder
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-4">
                                        <div class="flex flex-col items-start gap-1">
                                            @if ($hasValidationIssue)
                                                <span class="rounded-full bg-rose-100 px-2 py-1 font-display text-xs font-bold text-rose-700">Validation issue</span>
                                            @elseif ($isValidated)
                                                <span class="rounded-full bg-emerald-100 px-2 py-1 font-display text-xs font-bold text-emerald-700">Validated</span>
                                            @else
                                                <span class="rounded-full bg-slate-100 px-2 py-1 font-display text-xs font-bold text-slate-600">Unvalidated</span>
                                            @endif

                                            @if ($question->first_served_at)
                                                <span class="rounded-full bg-amber-100 px-2 py-1 font-display text-xs font-bold text-amber-800">Served</span>
                                            @endif

                                            <span class="rounded-full px-2 py-1 font-display text-xs font-bold {{ $isLocked ? 'bg-violet-100 text-violet-700' : 'bg-sky-100 text-sky-700' }}">
                                                {{ $isLocked ? 'Locked' : 'Editable' }}
                                            </span>
                                        </div>
                                    </td>

                                    <td class="px-4 py-4">
                                        @if ($question->is_active)
                                            <span class="rounded-full bg-emerald-100 px-2.5 py-1 font-display text-xs font-bold text-emerald-700">Active</span>
                                        @else
                                            <span class="rounded-full bg-rose-100 px-2.5 py-1 font-display text-xs font-bold text-rose-700">Inactive</span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-4 text-right">
                                        <a href="{{ route('admin.questions.show', ['question' => $question->question_id]) }}"
                                            style="--lip: #CBD5E1;"
                                            class="btn-press inline-flex rounded-xl border-2 border-b-4 border-slate-300 bg-white px-3 py-1.5 font-display text-xs font-bold text-primary shadow-2xs transition hover:bg-primary-tint">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-12 text-center text-xs font-semibold text-muted-ink">
                                        No questions match the selected filters.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-100 px-5 py-4">
                    {{ $questions->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
