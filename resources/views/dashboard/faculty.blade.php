<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            title="Faculty Dashboard"
            :description="$cohort
                ? $cohort->cohort_name.' · AY '.$cohort->academic_year
                : 'Cohort readiness, student support, and interventions.'"
        >
            @if ($cohort)
                <x-slot:actions>
                    <x-ui.button
                        variant="secondary"
                        :href="route('faculty.interventions.index')"
                    >
                        Intervention History
                    </x-ui.button>
                </x-slot:actions>
            @endif
        </x-ui.page-header>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <x-ui.alert variant="success">
                    {{ session('status') }}
                </x-ui.alert>
            @endif

            @if (session('error'))
                <x-ui.alert variant="danger">
                    {{ session('error') }}
                </x-ui.alert>
            @endif

            @if ($cohort === null)
                <x-ui.card class="py-12 text-center" raised>
                    <h2 class="font-display text-xl font-extrabold text-ink">
                        No cohort assigned
                    </h2>

                    <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-ink-muted">
                        Your faculty account is not assigned to a cohort.
                        Contact an administrator before reviewing student progress.
                    </p>
                </x-ui.card>
            @else
                <section
                    aria-label="Cohort summary"
                    class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
                >
                    <x-ui.card>
                        <p class="text-xs font-bold uppercase tracking-wide text-ink-muted">
                            Active Students
                        </p>

                        <p class="mt-2 font-display text-3xl font-black tabular-nums text-ink">
                            {{ $summary['total_students'] }}
                        </p>
                    </x-ui.card>

                    <x-ui.card>
                        <p class="text-xs font-bold uppercase tracking-wide text-ink-muted">
                            Diagnostic Complete
                        </p>

                        <p class="mt-2 font-display text-3xl font-black tabular-nums text-ink">
                            {{ $summary['diagnostic_completed'] }}
                        </p>

                        <p class="mt-1 text-xs text-ink-muted">
                            {{ number_format($summary['diagnostic_completion_pct'], 1) }}%
                            of the cohort
                        </p>
                    </x-ui.card>

                    <x-ui.card class="border-primary/25 bg-primary-tint">
                        <p class="text-xs font-bold uppercase tracking-wide text-primary-ink">
                            Average Readiness
                        </p>

                        <p class="mt-2 font-display text-3xl font-black tabular-nums text-primary-ink">
                            {{ $summary['avg_readiness'] !== null
                                ? number_format($summary['avg_readiness'], 2).'%'
                                : '—' }}
                        </p>
                    </x-ui.card>

                    <x-ui.card class="border-warning-strong/30 bg-warning-tint">
                        <p class="text-xs font-bold uppercase tracking-wide text-warning-ink">
                            Open Interventions
                        </p>

                        <p class="mt-2 font-display text-3xl font-black tabular-nums text-warning-ink">
                            {{ $interventionCounts['open'] }}
                        </p>

                        <p class="mt-1 text-xs text-warning-ink/80">
                            {{ $interventionCounts['completed'] }} completed
                        </p>
                    </x-ui.card>
                </section>

                <section class="grid gap-6 lg:grid-cols-2">
                    <x-ui.card>
                        <h2 class="font-display text-lg font-extrabold text-ink">
                            Readiness Distribution
                        </h2>

                        @php
                            $bands = [
                                'board_ready' => [
                                    'Board Ready',
                                    'bg-success-strong',
                                ],
                                'approaching' => [
                                    'Approaching',
                                    'bg-info-strong',
                                ],
                                'developing' => [
                                    'Developing',
                                    'bg-warning-strong',
                                ],
                                'at_risk' => [
                                    'At Risk',
                                    'bg-danger-strong',
                                ],
                            ];

                            $readinessTotal = collect($bands)
                                ->keys()
                                ->sum(
                                    fn ($key) => $readinessBands[$key],
                                );
                        @endphp

                        @if ($readinessTotal === 0)
                            <p class="mt-4 text-sm text-ink-muted">
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
                                        <div class="mb-1.5 flex items-center justify-between gap-4 text-sm">
                                            <span class="font-semibold text-ink">
                                                {{ $label }}
                                            </span>

                                            <span class="tabular-nums text-ink-muted">
                                                {{ $count }} · {{ $percentage }}%
                                            </span>
                                        </div>

                                        <div
                                            class="h-2.5 overflow-hidden rounded-full bg-surface-2"
                                            role="progressbar"
                                            aria-label="{{ $label }}"
                                            aria-valuemin="0"
                                            aria-valuemax="100"
                                            aria-valuenow="{{ $percentage }}"
                                        >
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
                            <p class="mt-4 text-xs leading-relaxed text-ink-muted">
                                {{ $readinessBands['no_data'] }}
                                {{ \Illuminate\Support\Str::plural(
                                    'student',
                                    $readinessBands['no_data'],
                                ) }}
                                still without a usable readiness estimate.
                            </p>
                        @endif
                    </x-ui.card>

                    <x-ui.card>
                        <h2 class="font-display text-lg font-extrabold text-ink">
                            Domain Mastery
                        </h2>

                        <div class="mt-4 space-y-3">
                            @foreach ($domainMastery as $domain)
                                <div class="flex items-center justify-between gap-4 rounded-2xl bg-surface-2 p-3 sm:p-4">
                                    <div class="min-w-0">
                                        <p class="text-sm font-bold text-ink">
                                            Domain {{ $domain['domain_number'] }}
                                        </p>

                                        <p class="truncate text-xs text-ink-muted">
                                            {{ $domain['domain_name'] }}
                                        </p>
                                    </div>

                                    <div class="shrink-0 text-right">
                                        <p class="font-display text-lg font-black tabular-nums text-ink">
                                            {{ $domain['avg_mastery_pct'] !== null
                                                ? number_format($domain['avg_mastery_pct'], 1).'%'
                                                : '—' }}
                                        </p>

                                        <p class="text-xs text-ink-muted">
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
                    </x-ui.card>
                </section>

                @if ($supportStudents->isNotEmpty())
                    <x-ui.card class="border-warning-strong/30 bg-warning-tint">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h2 class="font-display text-lg font-extrabold text-warning-ink">
                                    Students Needing Support
                                </h2>

                                <p class="mt-1 text-sm text-warning-ink/80">
                                    Students with a pending diagnostic or an at-risk readiness estimate.
                                </p>
                            </div>

                            <x-ui.badge variant="warning">
                                Up to 20 students
                            </x-ui.badge>
                        </div>

                        <div class="-mx-4 mt-4 overflow-x-auto px-4 sm:-mx-6 sm:px-6">
                            <table class="min-w-[680px] w-full text-sm">
                                <thead class="border-b border-warning-strong/30 text-left text-warning-ink">
                                    <tr>
                                        <th scope="col" class="px-3 py-3 font-bold">Student</th>
                                        <th scope="col" class="px-3 py-3 font-bold">Student ID</th>
                                        <th scope="col" class="px-3 py-3 font-bold">Diagnostic</th>
                                        <th scope="col" class="px-3 py-3 text-right font-bold">Readiness</th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-warning-strong/20">
                                    @foreach ($supportStudents as $student)
                                        <tr>
                                            <td class="px-3 py-3 font-semibold text-ink">
                                                {{ $student['name'] }}
                                            </td>

                                            <td class="px-3 py-3 text-ink-muted">
                                                {{ $student['student_id'] ?? '—' }}
                                            </td>

                                            <td class="px-3 py-3">
                                                <x-ui.badge
                                                    :variant="$student['is_diagnostic_completed']
                                                        ? 'success'
                                                        : 'warning'"
                                                >
                                                    {{ $student['is_diagnostic_completed']
                                                        ? 'Complete'
                                                        : 'Pending' }}
                                                </x-ui.badge>
                                            </td>

                                            <td class="px-3 py-3 text-right font-semibold tabular-nums text-ink">
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
                    </x-ui.card>
                @endif

                <x-ui.card>
                    <div>
                        <h2 class="font-display text-lg font-extrabold text-ink">
                            Assign Intervention
                        </h2>

                        <p class="mt-1 max-w-3xl text-sm leading-relaxed text-ink-muted">
                            Assign focused guidance to a student in your cohort. Competency-level targeting is optional.
                        </p>
                    </div>

                    @if ($targets->isEmpty())
                        <x-ui.alert variant="warning" class="mt-5">
                            No active students are available for intervention assignment.
                        </x-ui.alert>
                    @endif

                    <form
                        method="POST"
                        action="{{ route('faculty.interventions.store') }}"
                        class="mt-5 space-y-5"
                    >
                        @csrf

                        <div class="grid gap-5 md:grid-cols-2">
                            <x-ui.field
                                name="target_user_id"
                                label="Student"
                                type="select"
                                required
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
                            </x-ui.field>

                            <x-ui.field
                                name="intervention_type"
                                label="Type"
                                type="select"
                                required
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
                            </x-ui.field>
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            <x-ui.field
                                name="domain_id"
                                label="Domain"
                                type="select"
                                required
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
                            </x-ui.field>

                            <x-ui.field
                                name="competency_id"
                                label="Competency (optional)"
                                type="select"
                                hint="The competency must belong to the selected domain."
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
                            </x-ui.field>
                        </div>

                        <x-ui.field
                            name="custom_message"
                            label="Message (optional)"
                            type="textarea"
                            :rows="3"
                            maxlength="1000"
                            placeholder="Add guidance for the student..."
                            :value="old('custom_message')"
                        />

                        <x-ui.button
                            type="submit"
                            :disabled="$targets->isEmpty()"
                        >
                            Assign Intervention
                        </x-ui.button>
                    </form>
                </x-ui.card>

                <x-ui.card>
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h2 class="font-display text-lg font-extrabold text-ink">
                                Intervention Summary
                            </h2>

                            <p class="mt-2 text-sm text-ink-muted">
                                <span class="font-semibold tabular-nums text-ink">
                                    {{ $interventionCounts['total'] }}
                                </span>
                                total,
                                <span class="font-semibold tabular-nums text-warning-ink">
                                    {{ $interventionCounts['open'] }}
                                </span>
                                open, and
                                <span class="font-semibold tabular-nums text-success-ink">
                                    {{ $interventionCounts['completed'] }}
                                </span>
                                completed.
                            </p>
                        </div>

                        <x-ui.button
                            variant="ghost"
                            :href="route('faculty.interventions.index')"
                        >
                            View History
                        </x-ui.button>
                    </div>
                </x-ui.card>

                <x-ui.card :padded="false">
                    <div class="border-b border-line px-4 py-4 sm:px-6">
                        <h2 class="font-display text-lg font-extrabold text-ink">
                            Student Roster
                        </h2>

                        <p class="mt-1 text-sm text-ink-muted">
                            Active students currently assigned to {{ $cohort->cohort_name }}.
                        </p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-[680px] w-full text-sm">
                            <thead class="bg-surface-2 text-left text-ink-muted">
                                <tr>
                                    <th scope="col" class="px-4 py-3 font-bold sm:px-6">Student</th>
                                    <th scope="col" class="px-4 py-3 font-bold">Student ID</th>
                                    <th scope="col" class="px-4 py-3 font-bold">Diagnostic</th>
                                    <th scope="col" class="px-4 py-3 text-right font-bold sm:px-6">Readiness</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-line">
                                @forelse ($students as $student)
                                    <tr>
                                        <td class="px-4 py-3 font-semibold text-ink sm:px-6">
                                            {{ $student->last_name }},
                                            {{ $student->first_name }}
                                        </td>

                                        <td class="px-4 py-3 text-ink-muted">
                                            {{ $student->student_id ?? '—' }}
                                        </td>

                                        <td class="px-4 py-3">
                                            <x-ui.badge
                                                :variant="$student->is_diagnostic_completed
                                                    ? 'success'
                                                    : 'warning'"
                                            >
                                                {{ $student->is_diagnostic_completed
                                                    ? 'Complete'
                                                    : 'Pending' }}
                                            </x-ui.badge>
                                        </td>

                                        <td class="px-4 py-3 text-right font-semibold tabular-nums text-ink sm:px-6">
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
                                            class="px-4 py-10 text-center text-ink-muted"
                                        >
                                            No active students are assigned to this cohort.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($students->hasPages())
                        <div class="border-t border-line px-4 py-4 sm:px-6">
                            {{ $students->links() }}
                        </div>
                    @endif
                </x-ui.card>
            @endif
        </div>
    </div>
</x-app-layout>
