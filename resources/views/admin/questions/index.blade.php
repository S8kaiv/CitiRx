<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="font-display text-xl font-extrabold text-slate-900">
                    Question Bank
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Review, revise, freeze, and retire assessment questions.
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-5 sm:px-6 lg:px-8">
            @if (session('status'))
                <div
                    role="status"
                    class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800"
                >
                    {{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div
                    role="alert"
                    class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-800"
                >
                    {{ session('error') }}
                </div>
            @endif

            <form
                method="GET"
                action="{{ route('admin.questions.index') }}"
                class="grid grid-cols-1 gap-3 rounded-3xl border-2 border-slate-200 bg-white p-5 shadow-sm md:grid-cols-2 xl:grid-cols-6"
            >
                <div class="xl:col-span-2">
                    <label
                        for="q"
                        class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500"
                    >
                        Search
                    </label>

                    <input
                        id="q"
                        type="search"
                        name="q"
                        maxlength="100"
                        value="{{ $filters['q'] }}"
                        placeholder="Search question text..."
                        class="w-full rounded-xl border-slate-300 text-sm focus:border-primary focus:ring-primary"
                    >
                </div>

                <div>
                    <label
                        for="form"
                        class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500"
                    >
                        Form
                    </label>

                    <select
                        id="form"
                        name="form"
                        class="w-full rounded-xl border-slate-300 text-sm focus:border-primary focus:ring-primary"
                    >
                        <option value="">All forms</option>

                        <option
                            value="pre_test_a"
                            @selected($filters['form'] === 'pre_test_a')
                        >
                            Form A
                        </option>

                        <option
                            value="post_test_b"
                            @selected($filters['form'] === 'post_test_b')
                        >
                            Form B
                        </option>
                    </select>
                </div>

                <div>
                    <label
                        for="domain_id"
                        class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500"
                    >
                        Domain
                    </label>

                    <select
                        id="domain_id"
                        name="domain_id"
                        class="w-full rounded-xl border-slate-300 text-sm focus:border-primary focus:ring-primary"
                    >
                        <option value="">All domains</option>

                        @foreach ($domains as $domain)
                            <option
                                value="{{ $domain->domain_id }}"
                                @selected(
                                    (string) $filters['domain_id']
                                    === (string) $domain->domain_id
                                )
                            >
                                {{ $domain->domain_number }}.
                                {{ $domain->domain_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label
                        for="status"
                        class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500"
                    >
                        Status
                    </label>

                    <select
                        id="status"
                        name="status"
                        class="w-full rounded-xl border-slate-300 text-sm focus:border-primary focus:ring-primary"
                    >
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
                            <option
                                value="{{ $value }}"
                                @selected($filters['status'] === $value)
                            >
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button
                        type="submit"
                        class="flex-1 rounded-xl border-2 border-b-4 border-primary/30 bg-primary px-4 py-2 text-sm font-bold text-white transition hover:bg-[#4A2FC4]"
                    >
                        Filter
                    </button>

                    <a
                        href="{{ route('admin.questions.index') }}"
                        class="rounded-xl border-2 border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-50"
                    >
                        Clear
                    </a>
                </div>
            </form>

            <div class="overflow-hidden rounded-3xl border-2 border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Form</th>
                                <th class="px-4 py-3">Domain</th>
                                <th class="px-4 py-3">Question</th>
                                <th class="px-4 py-3">State</th>
                                <th class="px-4 py-3">Availability</th>
                                <th class="px-4 py-3 text-right">Action</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            @forelse ($questions as $question)
                                @php
                                    $isValidated =
                                        $question->research_validated_at !== null
                                        && $question->research_validated_by !== null;

                                    $hasValidationIssue =
                                        ($question->research_validated_at === null)
                                        !== ($question->research_validated_by === null);

                                    $isLocked = $question->contentIsFrozen();

                                    $isPlaceholder = str_starts_with(
                                        $question->question_text,
                                        '[DEVELOPMENT MOCK - NOT VALIDATED]',
                                    );
                                @endphp

                                <tr class="align-top hover:bg-slate-50/70">
                                    <td class="whitespace-nowrap px-4 py-4">
                                        <div class="font-bold text-slate-800">
                                            {{ $question->research_form ?? 'Practice' }}
                                        </div>

                                        @if ($question->form_position !== null)
                                            <div class="text-xs text-slate-500">
                                                Position {{ $question->form_position }}
                                            </div>
                                        @endif
                                    </td>

                                    <td class="px-4 py-4 text-xs text-slate-600">
                                        <div class="font-semibold">
                                            {{ $question->competency?->domain?->domain_name ?? 'Unknown domain' }}
                                        </div>

                                        <div class="mt-1">
                                            {{ $question->competency?->competency_code }}
                                        </div>
                                    </td>

                                    <td class="max-w-xl px-4 py-4">
                                        <p class="line-clamp-3 font-medium text-slate-900">
                                            {{ $question->question_text }}
                                        </p>

                                        @if ($isPlaceholder)
                                            <span class="mt-2 inline-flex rounded-full bg-amber-100 px-2 py-1 text-[10px] font-bold uppercase text-amber-800">
                                                Development placeholder
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-4">
                                        <div class="flex flex-col items-start gap-1">
                                            @if ($hasValidationIssue)
                                                <span class="rounded-full bg-red-100 px-2 py-1 text-xs font-bold text-red-700">
                                                    Validation issue
                                                </span>
                                            @elseif ($isValidated)
                                                <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-bold text-emerald-700">
                                                    Validated
                                                </span>
                                            @else
                                                <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-bold text-slate-600">
                                                    Unvalidated
                                                </span>
                                            @endif

                                            @if ($question->first_served_at)
                                                <span class="rounded-full bg-amber-100 px-2 py-1 text-xs font-bold text-amber-800">
                                                    Served
                                                </span>
                                            @endif

                                            <span class="rounded-full px-2 py-1 text-xs font-bold {{ $isLocked ? 'bg-violet-100 text-violet-700' : 'bg-blue-100 text-blue-700' }}">
                                                {{ $isLocked ? 'Locked' : 'Editable' }}
                                            </span>
                                        </div>
                                    </td>

                                    <td class="px-4 py-4">
                                        @if ($question->is_active)
                                            <span class="font-bold text-emerald-700">
                                                Active
                                            </span>
                                        @else
                                            <span class="font-bold text-red-700">
                                                Inactive
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-4 text-right">
                                        <a
                                            href="{{ route('admin.questions.show', [
                                                'question' => $question->question_id,
                                            ]) }}"
                                            class="inline-flex rounded-xl border-2 border-slate-200 px-3 py-2 text-xs font-bold text-primary hover:bg-primary-tint"
                                        >
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="6"
                                        class="px-4 py-12 text-center text-slate-500"
                                    >
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
