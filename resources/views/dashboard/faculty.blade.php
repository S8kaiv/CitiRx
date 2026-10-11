<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-display text-xl font-extrabold text-slate-900">
                    Faculty Dashboard
                </h2>

                @if ($cohort)
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $cohort->cohort_name }}
                        · AY {{ $cohort->academic_year }}
                    </p>
                @endif
            </div>

            @if ($cohort)
                <a
                    href="{{ route('faculty.interventions.index') }}"
                    class="rounded-xl border-2 border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-50"
                >
                    Intervention History
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
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

            @if ($cohort === null)
                <section class="rounded-3xl border-2 border-slate-200 bg-white p-12 text-center shadow-sm">
                    <h3 class="font-display text-xl font-extrabold text-slate-900">
                        No cohort assigned
                    </h3>

                    <p class="mx-auto mt-2 max-w-md text-sm text-slate-600">
                        Your faculty account is not assigned to a cohort.
                        Contact an administrator before reviewing student progress.
                    </p>
                </section>
            @else
                <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <article class="rounded-3xl border-2 border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">
                            Active Students
                        </p>

                        <p class="mt-2 font-display text-3xl font-black text-slate-900">
                            {{ $summary['total_students'] }}
                        </p>
                    </article>

                    <article class="rounded-3xl border-2 border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">
                            Diagnostic Complete
                        </p>

                        <p class="mt-2 font-display text-3xl font-black text-slate-900">
                            {{ $summary['diagnostic_completed'] }}
                        </p>

                        <p class="mt-1 text-xs text-slate-500">
                            {{ number_format($summary['diagnostic_completion_pct'], 1) }}%
                            of the cohort
                        </p>
                    </article>

                    <article class="rounded-3xl border-2 border-primary/20 bg-primary-tint p-5 shadow-sm">
                        <p class="text-xs font-bold uppercase tracking-wide text-primary">
                            Average Readiness
                        </p>

                        <p class="mt-2 font-display text-3xl font-black text-primary">
                            {{ $summary['avg_readiness'] !== null
                                ? number_format($summary['avg_readiness'], 2).'%'
                                : '—' }}
                        </p>
                    </article>

                    <article class="rounded-3xl border-2 border-amber-200 bg-amber-50 p-5 shadow-sm">
                        <p class="text-xs font-bold uppercase tracking-wide text-amber-800">
                            Open Interventions
                        </p>

                        <p class="mt-2 font-display text-3xl font-black text-amber-900">
                            {{ $interventionCounts['open'] }}
                        </p>

                        <p class="mt-1 text-xs text-amber-700">
                            {{ $interventionCounts['completed'] }} completed
                        </p>
                    </article>
                </section>

                <section class="grid gap-6 lg:grid-cols-2">
                    <article class="rounded-3xl border-2 border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="font-display text-lg font-extrabold text-slate-900">
                            Readiness Distribution
                        </h3>

                        @php
                            $bands = [
                                'board_ready' => [
                                    'Board Ready',
                                    'bg-emerald-500',
                                ],
                                'approaching' => [
                                    'Approaching',
                                    'bg-blue-500',
                                ],
                                'developing' => [
                                    'Developing',
                                    'bg-amber-500',
                                ],
                                'at_risk' => [
                                    'At Risk',
                                    'bg-red-500',
                                ],
                            ];

                            $readinessTotal = collect($bands)
                                ->keys()
                                ->sum(
                                    fn ($key) => $readinessBands[$key],
                                );
                        @endphp

                        @if ($readinessTotal === 0)
                            <p class="mt-4 text-sm text-slate-500">
                                No completed readiness estimates are available.
                            </p>
                        @else
                            <div class="mt-5 space-y-4">
                                @foreach ($bands as $key => [$label, $colour])
                                    @php
                                        $count = $readinessBands[$key];

                                        $percentage = round(
                                            ($count / $readinessTotal) * 100,
                                            1,
                                        );
                                    @endphp

                                    <div>
                                        <div class="mb-1 flex justify-between text-sm">
                                            <span class="font-semibold text-slate-700">
                                                {{ $label }}
                                            </span>

                                            <span class="text-slate-500">
                                                {{ $count }} · {{ $percentage }}%
                                            </span>
                                        </div>

                                        <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                            <div
                                                class="h-full rounded-full {{ $colour }}"
                                                style="width: {{ $percentage }}%"
                                            ></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if ($readinessBands['no_data'] > 0)
                            <p class="mt-4 text-xs text-slate-500">
                                {{ $readinessBands['no_data'] }}
                                {{ \Illuminate\Support\Str::plural(
                                    'student',
                                    $readinessBands['no_data'],
                                ) }}
                                still without a usable readiness estimate.
                            </p>
                        @endif
                    </article>

                    <article class="rounded-3xl border-2 border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="font-display text-lg font-extrabold text-slate-900">
                            Domain Mastery
                        </h3>

                        <div class="mt-4 space-y-3">
                            @foreach ($domainMastery as $domain)
                                <div class="flex items-center justify-between gap-4 rounded-2xl bg-slate-50 p-3">
                                    <div>
                                        <p class="text-sm font-bold text-slate-800">
                                            Domain {{ $domain['domain_number'] }}
                                        </p>

                                        <p class="text-xs text-slate-500">
                                            {{ $domain['domain_name'] }}
                                        </p>
                                    </div>

                                    <div class="text-right">
                                        <p class="font-display text-lg font-black text-slate-900">
                                            {{ $domain['avg_mastery_pct'] !== null
                                                ? number_format($domain['avg_mastery_pct'], 1).'%'
                                                : '—' }}
                                        </p>

                                        <p class="text-xs text-slate-500">
                                            {{ $domain['student_count'] }}
                                            {{ \Illuminate\Support\Str::plural(
                                                'student',
                                                $domain['student_count'],
                                            ) }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </article>
                </section>

                @if ($supportStudents->isNotEmpty())
                    <section class="rounded-3xl border-2 border-amber-200 bg-amber-50 p-6 shadow-sm">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="font-display text-lg font-extrabold text-amber-950">
                                Students Needing Support
                            </h3>

                            <span class="text-xs font-semibold text-amber-700">
                                Up to 20 students
                            </span>
                        </div>

                        <div class="mt-4 overflow-x-auto">
                            <table class="w-full min-w-[640px] text-sm">
                                <thead class="border-b border-amber-200 text-left text-amber-900">
                                    <tr>
                                        <th class="py-2">Student</th>
                                        <th class="py-2">Student ID</th>
                                        <th class="py-2">Diagnostic</th>
                                        <th class="py-2 text-right">Readiness</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($supportStudents as $student)
                                        <tr class="border-b border-amber-100">
                                            <td class="py-3 font-semibold text-slate-900">
                                                {{ $student['name'] }}
                                            </td>

                                            <td class="py-3 text-slate-600">
                                                {{ $student['student_id'] ?? '—' }}
                                            </td>

                                            <td class="py-3">
                                                {{ $student['is_diagnostic_completed']
                                                    ? 'Complete'
                                                    : 'Pending' }}
                                            </td>

                                            <td class="py-3 text-right font-semibold">
                                                {{ $student['predicted_readiness_pct'] !== null
                                                    ? number_format(
                                                        $student['predicted_readiness_pct'],
                                                        2,
                                                    ).'%'
                                                    : '—' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endif

                <section class="rounded-3xl border-2 border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="font-display text-lg font-extrabold text-slate-900">
                        Assign Intervention
                    </h3>

                    <form
                        method="POST"
                        action="{{ route('faculty.interventions.store') }}"
                        class="mt-5 space-y-4"
                    >
                        @csrf

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label
                                    for="target_user_id"
                                    class="block text-sm font-bold text-slate-700"
                                >
                                    Student
                                </label>

                                <select
                                    id="target_user_id"
                                    name="target_user_id"
                                    required
                                    class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-primary focus:ring-primary"
                                >
                                    <option value="">Select a student</option>

                                    @foreach ($targets as $student)
                                        <option
                                            value="{{ $student->user_id }}"
                                            @selected(
                                                old('target_user_id')
                                                    === $student->user_id
                                            )
                                        >
                                            {{ $student->last_name }},
                                            {{ $student->first_name }}
                                            @if ($student->student_id)
                                                ({{ $student->student_id }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>

                                @error('target_user_id')
                                    <p class="mt-1 text-xs font-semibold text-red-600">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <div>
                                <label
                                    for="intervention_type"
                                    class="block text-sm font-bold text-slate-700"
                                >
                                    Type
                                </label>

                                <select
                                    id="intervention_type"
                                    name="intervention_type"
                                    required
                                    class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-primary focus:ring-primary"
                                >
                                    @foreach ($interventionTypes as $value => $label)
                                        <option
                                            value="{{ $value }}"
                                            @selected(
                                                old(
                                                    'intervention_type',
                                                    'advisory',
                                                ) === $value
                                            )
                                        >
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('intervention_type')
                                    <p class="mt-1 text-xs font-semibold text-red-600">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label
                                    for="domain_id"
                                    class="block text-sm font-bold text-slate-700"
                                >
                                    Domain
                                </label>

                                <select
                                    id="domain_id"
                                    name="domain_id"
                                    required
                                    class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-primary focus:ring-primary"
                                >
                                    <option value="">Select a domain</option>

                                    @foreach ($formOptions as $domain)
                                        <option
                                            value="{{ $domain->domain_id }}"
                                            @selected(
                                                (string) old('domain_id')
                                                    === (string) $domain->domain_id
                                            )
                                        >
                                            Domain {{ $domain->domain_number }}
                                            — {{ $domain->domain_name }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('domain_id')
                                    <p class="mt-1 text-xs font-semibold text-red-600">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <div>
                                <label
                                    for="competency_id"
                                    class="block text-sm font-bold text-slate-700"
                                >
                                    Competency
                                    <span class="font-normal text-slate-400">
                                        (optional)
                                    </span>
                                </label>

                                <select
                                    id="competency_id"
                                    name="competency_id"
                                    class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-primary focus:ring-primary"
                                >
                                    <option value="">Domain-wide intervention</option>

                                    @foreach ($formOptions as $domain)
                                        <optgroup
                                            label="Domain {{ $domain->domain_number }} — {{ $domain->domain_name }}"
                                        >
                                            @foreach ($domain->competencies as $competency)
                                                <option
                                                    value="{{ $competency->competency_id }}"
                                                    @selected(
                                                        old('competency_id')
                                                            === $competency->competency_id
                                                    )
                                                >
                                                    {{ $competency->competency_code }}
                                                    — {{ $competency->title }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>

                                <p class="mt-1 text-xs text-slate-500">
                                    The competency must belong to the selected domain.
                                </p>

                                @error('competency_id')
                                    <p class="mt-1 text-xs font-semibold text-red-600">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label
                                for="custom_message"
                                class="block text-sm font-bold text-slate-700"
                            >
                                Message
                                <span class="font-normal text-slate-400">
                                    (optional)
                                </span>
                            </label>

                            <textarea
                                id="custom_message"
                                name="custom_message"
                                rows="3"
                                maxlength="1000"
                                class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-primary focus:ring-primary"
                                placeholder="Add guidance for the student..."
                            >{{ old('custom_message') }}</textarea>

                            @error('custom_message')
                                <p class="mt-1 text-xs font-semibold text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <button
                            type="submit"
                            @disabled($targets->isEmpty())
                            class="rounded-xl border-2 border-b-4 border-primary/30 bg-primary px-5 py-2.5 font-display text-sm font-extrabold text-white transition hover:bg-primary-lip disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            Assign Intervention
                        </button>
                    </form>
                </section>

                <section class="rounded-3xl border-2 border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="font-display text-lg font-extrabold text-slate-900">
                            Intervention Summary
                        </h3>

                        <a
                            href="{{ route('faculty.interventions.index') }}"
                            class="text-sm font-bold text-primary hover:text-primary-lip"
                        >
                            View history
                        </a>
                    </div>

                    <p class="mt-3 text-sm text-slate-600">
                        {{ $interventionCounts['total'] }} total,
                        {{ $interventionCounts['open'] }} open,
                        {{ $interventionCounts['completed'] }} completed.
                    </p>
                </section>

                <section class="rounded-3xl border-2 border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="font-display text-lg font-extrabold text-slate-900">
                        Student Roster
                    </h3>

                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full min-w-[640px] text-sm">
                            <thead class="border-b border-slate-200 text-left text-slate-600">
                                <tr>
                                    <th class="py-2">Student</th>
                                    <th class="py-2">Student ID</th>
                                    <th class="py-2">Diagnostic</th>
                                    <th class="py-2 text-right">Readiness</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($students as $student)
                                    <tr class="border-b border-slate-100">
                                        <td class="py-3 font-semibold text-slate-900">
                                            {{ $student->last_name }},
                                            {{ $student->first_name }}
                                        </td>

                                        <td class="py-3 text-slate-600">
                                            {{ $student->student_id ?? '—' }}
                                        </td>

                                        <td class="py-3">
                                            {{ $student->is_diagnostic_completed
                                                ? 'Complete'
                                                : 'Pending' }}
                                        </td>

                                        <td class="py-3 text-right font-semibold">
                                            {{ $student->predicted_readiness_pct !== null
                                                ? number_format(
                                                    (float) $student->predicted_readiness_pct,
                                                    2,
                                                ).'%'
                                                : '—' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td
                                            colspan="4"
                                            class="py-8 text-center text-slate-500"
                                        >
                                            No active students are assigned to this cohort.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $students->links() }}
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
