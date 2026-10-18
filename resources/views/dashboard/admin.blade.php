<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="font-display text-xs font-bold uppercase tracking-[0.18em] text-primary">
                    Administration
                </p>

                <h2 class="font-display text-xl font-extrabold text-slate-900">
                    Admin Dashboard
                </h2>
            </div>

            <div class="flex flex-wrap gap-2">
                <a
                    href="{{ route('admin.users.index') }}"
                    class="rounded-xl border-2 border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/25"
                >
                    Manage Users
                </a>

                <a
                    href="{{ route('admin.cohorts.index') }}"
                    class="rounded-xl border-2 border-b-4 border-primary/30 bg-primary px-4 py-2 text-sm font-bold text-white transition hover:bg-[#4A2FC4] focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/25"
                >
                    Manage Cohorts
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div
                    role="status"
                    class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800"
                >
                    {{ session('status') }}
                </div>
            @endif

            <section class="overflow-hidden rounded-3xl bg-gradient-to-br from-primary to-[#4A2FC4] p-6 text-white shadow-lg sm:p-8">
                <p class="text-sm font-semibold text-white/80">
                    Welcome back
                </p>

                <h3 class="mt-1 font-display text-2xl font-extrabold sm:text-3xl">
                    {{ $user->fullName() }}
                </h3>

                <p class="mt-3 max-w-2xl text-sm leading-relaxed text-white/85">
                    Monitor users, cohorts, assessment participation,
                    research-form preparation, and administrative activity.
                </p>
            </section>

            <section aria-labelledby="account-overview-heading">
                <div class="mb-3 flex items-center justify-between gap-4">
                    <h3
                        id="account-overview-heading"
                        class="font-display text-lg font-extrabold text-slate-900"
                    >
                        Account Overview
                    </h3>

                    <a
                        href="{{ route('admin.users.index') }}"
                        class="text-sm font-bold text-primary hover:underline"
                    >
                        View all users
                    </a>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <article class="rounded-3xl border-2 border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">
                            Active Students
                        </p>

                        <p class="mt-2 font-display text-3xl font-extrabold text-slate-900">
                            {{ number_format($accountStats['students']) }}
                        </p>
                    </article>

                    <article class="rounded-3xl border-2 border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">
                            Active Faculty
                        </p>

                        <p class="mt-2 font-display text-3xl font-extrabold text-slate-900">
                            {{ number_format($accountStats['faculty']) }}
                        </p>
                    </article>

                    <article class="rounded-3xl border-2 border-amber-200 bg-amber-50 p-5 shadow-sm">
                        <p class="text-xs font-bold uppercase tracking-wide text-amber-700">
                            Students Without Cohort
                        </p>

                        <p class="mt-2 font-display text-3xl font-extrabold text-amber-900">
                            {{ number_format($accountStats['students_without_cohort']) }}
                        </p>

                        @if ($accountStats['students_without_cohort'] > 0)
                            <a
                                href="{{ route('admin.users.index', [
                                    'role' => 'student',
                                    'no_cohort' => 1,
                                ]) }}"
                                class="mt-3 inline-flex text-xs font-bold text-amber-800 hover:underline"
                            >
                                Assign cohorts
                            </a>
                        @endif
                    </article>

                    <article class="rounded-3xl border-2 border-amber-200 bg-amber-50 p-5 shadow-sm">
                        <p class="text-xs font-bold uppercase tracking-wide text-amber-700">
                            Faculty Without Cohort
                        </p>

                        <p class="mt-2 font-display text-3xl font-extrabold text-amber-900">
                            {{ number_format($accountStats['faculty_without_cohort']) }}
                        </p>

                        @if ($accountStats['faculty_without_cohort'] > 0)
                            <a
                                href="{{ route('admin.users.index', [
                                    'role' => 'faculty',
                                    'no_cohort' => 1,
                                ]) }}"
                                class="mt-3 inline-flex text-xs font-bold text-amber-800 hover:underline"
                            >
                                Assign cohorts
                            </a>
                        @endif
                    </article>
                </div>

                <div class="mt-3 flex flex-wrap gap-x-6 gap-y-2 text-xs text-slate-500">
                    <span>
                        Active cohorts:
                        <strong class="text-slate-800">
                            {{ $cohortStats['active'] }}
                        </strong>
                    </span>

                    <span>
                        Archived cohorts:
                        <strong class="text-slate-800">
                            {{ $cohortStats['archived'] }}
                        </strong>
                    </span>

                    <span>
                        Unverified accounts:
                        <strong class="text-slate-800">
                            {{ $accountStats['unverified'] }}
                        </strong>
                    </span>
                </div>
            </section>

            <section
                class="grid gap-5 lg:grid-cols-2"
                aria-labelledby="assessment-progress-heading"
            >
                <article class="rounded-3xl border-2 border-slate-200 bg-white p-6 shadow-sm">
                    <h3
                        id="assessment-progress-heading"
                        class="font-display text-lg font-extrabold text-slate-900"
                    >
                        Assessment Participation
                    </h3>

                    <div class="mt-6 space-y-6">
                        <div>
                            <div class="flex items-center justify-between gap-4 text-sm">
                                <span class="font-bold text-slate-700">
                                    Diagnostic completed
                                </span>

                                <span class="text-slate-500">
                                    {{ $assessmentStats['diagnostic_completed'] }}
                                    /
                                    {{ $accountStats['students'] }}
                                </span>
                            </div>

                            <div
                                class="mt-2 h-3 overflow-hidden rounded-full bg-slate-100"
                                role="progressbar"
                                aria-label="Diagnostic completion"
                                aria-valuemin="0"
                                aria-valuemax="100"
                                aria-valuenow="{{ $assessmentStats['diagnostic_rate'] }}"
                            >
                                <div
                                    class="h-full rounded-full bg-primary"
                                    style="width: {{ min(
                                        100,
                                        max(
                                            0,
                                            $assessmentStats['diagnostic_rate'],
                                        ),
                                    ) }}%"
                                ></div>
                            </div>

                            <p class="mt-2 text-right text-xs font-bold text-primary">
                                {{ number_format(
                                    $assessmentStats['diagnostic_rate'],
                                    1,
                                ) }}%
                            </p>
                        </div>

                        <div>
                            <div class="flex items-center justify-between gap-4 text-sm">
                                <span class="font-bold text-slate-700">
                                    Mock Board finalized
                                </span>

                                <span class="text-slate-500">
                                    {{ $assessmentStats['mock_board_completed'] }}
                                    /
                                    {{ $accountStats['students'] }}
                                </span>
                            </div>

                            <div
                                class="mt-2 h-3 overflow-hidden rounded-full bg-slate-100"
                                role="progressbar"
                                aria-label="Mock Board completion"
                                aria-valuemin="0"
                                aria-valuemax="100"
                                aria-valuenow="{{ $assessmentStats['mock_board_rate'] }}"
                            >
                                <div
                                    class="h-full rounded-full bg-emerald-500"
                                    style="width: {{ min(
                                        100,
                                        max(
                                            0,
                                            $assessmentStats['mock_board_rate'],
                                        ),
                                    ) }}%"
                                ></div>
                            </div>

                            <p class="mt-2 text-right text-xs font-bold text-emerald-700">
                                {{ number_format(
                                    $assessmentStats['mock_board_rate'],
                                    1,
                                ) }}%
                            </p>
                        </div>
                    </div>

                    <a
                        href="{{ route('admin.research-analytics.index') }}"
                        class="mt-6 inline-flex text-sm font-bold text-primary hover:underline"
                    >
                        Open detailed research analytics
                    </a>
                </article>

                <article class="rounded-3xl border-2 border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between gap-4">
                        <h3 class="font-display text-lg font-extrabold text-slate-900">
                            Question Bank
                        </h3>

                        <a
                            href="{{ route('admin.questions.index') }}"
                            class="text-sm font-bold text-primary hover:underline"
                        >
                            Open bank
                        </a>
                    </div>

                    <dl class="mt-5 grid grid-cols-2 gap-4">
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <dt class="text-xs font-bold uppercase text-slate-500">
                                Total
                            </dt>

                            <dd class="mt-1 text-2xl font-extrabold text-slate-900">
                                {{ $questionStats['total'] }}
                            </dd>
                        </div>

                        <div class="rounded-2xl bg-slate-50 p-4">
                            <dt class="text-xs font-bold uppercase text-slate-500">
                                Locked
                            </dt>

                            <dd class="mt-1 text-2xl font-extrabold text-slate-900">
                                {{ $questionStats['locked'] }}
                            </dd>
                        </div>

                        <div class="rounded-2xl bg-amber-50 p-4">
                            <dt class="text-xs font-bold uppercase text-amber-700">
                                Needing review
                            </dt>

                            <dd class="mt-1 text-2xl font-extrabold text-amber-900">
                                {{ $questionStats['needing_review'] }}
                            </dd>
                        </div>

                        <div class="rounded-2xl bg-red-50 p-4">
                            <dt class="text-xs font-bold uppercase text-red-700">
                                Validation issues
                            </dt>

                            <dd class="mt-1 text-2xl font-extrabold text-red-900">
                                {{ $questionStats['validation_issues'] }}
                            </dd>
                        </div>
                    </dl>

                    @if ($questionStats['placeholders'] > 0)
                        <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                            <strong>
                                {{ $questionStats['placeholders'] }}
                            </strong>
                            development placeholder question(s) remain.
                        </div>
                    @endif
                </article>
            </section>

            <section aria-labelledby="research-form-heading">
                <div class="mb-3 flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h3
                            id="research-form-heading"
                            class="font-display text-lg font-extrabold text-slate-900"
                        >
                            Research Forms
                        </h3>

                        <p class="mt-1 text-xs text-slate-500">
                            These dashboard cards are lightweight summaries.
                            Open readiness for the complete shared blueprint
                            and validation audit.
                        </p>
                    </div>

                    <a
                        href="{{ route('admin.research-forms.index') }}"
                        class="text-sm font-bold text-primary hover:underline focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/25"
                    >
                        Open readiness audit
                    </a>
                </div>

                <div class="grid gap-5 lg:grid-cols-2">
                    @foreach ($formStats as $form => $stats)
                        <article class="rounded-3xl border-2 border-slate-200 bg-white p-6 shadow-sm">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <h4 class="font-display text-lg font-extrabold text-slate-900">
                                        {{ $stats['label'] }}
                                    </h4>

                                    <p class="mt-1 font-mono text-xs text-slate-500">
                                        {{ $form }}
                                    </p>
                                </div>

                                @if ($stats['validation_complete'])
                                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">
                                        Validation complete
                                    </span>
                                @else
                                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">
                                        Needs attention
                                    </span>
                                @endif
                            </div>

                            <dl class="mt-5 grid grid-cols-2 gap-3 text-sm">
                                <div class="rounded-xl bg-slate-50 p-3">
                                    <dt class="text-xs text-slate-500">
                                        Active questions
                                    </dt>

                                    <dd class="mt-1 font-bold text-slate-900">
                                        {{ $stats['active_questions'] }}/60
                                    </dd>
                                </div>

                                <div class="rounded-xl bg-slate-50 p-3">
                                    <dt class="text-xs text-slate-500">
                                        Validated
                                    </dt>

                                    <dd class="mt-1 font-bold text-slate-900">
                                        {{ $stats['validated_questions'] }}/60
                                    </dd>
                                </div>

                                <div class="rounded-xl bg-slate-50 p-3">
                                    <dt class="text-xs text-slate-500">
                                        Positions 1–60
                                    </dt>

                                    <dd class="mt-1 font-bold {{ $stats['positions_complete'] ? 'text-emerald-700' : 'text-red-700' }}">
                                        {{ $stats['positions_complete'] ? 'Complete' : 'Incomplete' }}
                                    </dd>
                                </div>

                                <div class="rounded-xl bg-slate-50 p-3">
                                    <dt class="text-xs text-slate-500">
                                        Placeholders
                                    </dt>

                                    <dd class="mt-1 font-bold {{ $stats['placeholders'] === 0 ? 'text-emerald-700' : 'text-amber-800' }}">
                                        {{ $stats['placeholders'] }}
                                    </dd>
                                </div>
                            </dl>

                            <a
                                href="{{ route('admin.questions.index', [
                                    'form' => $form,
                                ]) }}"
                                class="mt-5 inline-flex text-sm font-bold text-primary hover:underline"
                            >
                                Review this form
                            </a>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="grid gap-5 lg:grid-cols-[1fr_1.35fr]">
                <article class="rounded-3xl border-2 border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="font-display text-lg font-extrabold text-slate-900">
                        Content Workflow
                    </h3>

                    <dl class="mt-5 space-y-3 text-sm">
                        <div class="flex justify-between gap-4 rounded-xl bg-slate-50 p-3">
                            <dt class="text-slate-600">
                                Micro Pearl drafts
                            </dt>

                            <dd class="font-bold text-slate-900">
                                {{ $pearlStats['drafts'] }}
                            </dd>
                        </div>

                        <div class="flex justify-between gap-4 rounded-xl bg-amber-50 p-3">
                            <dt class="text-amber-800">
                                Awaiting review
                            </dt>

                            <dd class="font-bold text-amber-900">
                                {{ $pearlStats['in_review'] }}
                            </dd>
                        </div>

                        <div class="flex justify-between gap-4 rounded-xl bg-emerald-50 p-3">
                            <dt class="text-emerald-800">
                                Safely published
                            </dt>

                            <dd class="font-bold text-emerald-900">
                                {{ $pearlStats['published'] }}
                            </dd>
                        </div>
                    </dl>
                </article>

                <article class="rounded-3xl border-2 border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between gap-4">
                        <h3 class="font-display text-lg font-extrabold text-slate-900">
                            Recent Administrative Activity
                        </h3>

                        <a
                            href="{{ route('admin.audit-logs.index') }}"
                            class="text-sm font-bold text-primary hover:underline"
                        >
                            View all
                        </a>
                    </div>

                    <div class="mt-5 space-y-3">
                        @forelse ($recentAudits as $log)
                            <article class="rounded-2xl border border-slate-200 p-4">
                                <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                    <span class="font-bold text-slate-900">
                                        {{ $log->admin?->fullName() ?? 'Former administrator' }}
                                    </span>

                                    <span aria-hidden="true">•</span>

                                    <span>
                                        {{ str($log->action)
                                            ->replace('_', ' ')
                                            ->title() }}
                                    </span>

                                    <span aria-hidden="true">•</span>

                                    <time
                                        datetime="{{ $log->created_at->toIso8601String() }}"
                                        title="{{ $log->created_at->format('M j, Y g:i A') }}"
                                    >
                                        {{ $log->created_at->diffForHumans() }}
                                    </time>
                                </div>

                                <p class="mt-2 text-xs text-slate-500">
                                    {{ class_basename($log->target_type) }}
                                    ·
                                    {{ $log->target_id }}
                                </p>

                                @if ($log->reason)
                                    <p class="mt-2 text-sm text-slate-700">
                                        {{ \Illuminate\Support\Str::limit(
                                            $log->reason,
                                            140,
                                        ) }}
                                    </p>
                                @endif
                            </article>
                        @empty
                            <div class="rounded-2xl bg-slate-50 p-6 text-center text-sm text-slate-500">
                                No administrative activity has been recorded.
                            </div>
                        @endforelse
                    </div>
                </article>
            </section>

            <section aria-labelledby="quick-actions-heading">
                <h3
                    id="quick-actions-heading"
                    class="font-display text-lg font-extrabold text-slate-900"
                >
                    Quick Actions
                </h3>

                <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                    @foreach ([
                        [
                            'label' => 'Users',
                            'route' => route('admin.users.index'),
                        ],
                        [
                            'label' => 'Cohorts',
                            'route' => route('admin.cohorts.index'),
                        ],
                        [
                            'label' => 'Questions',
                            'route' => route('admin.questions.index'),
                        ],
                        [
                            'label' => 'Analytics',
                            'route' => route('admin.research-analytics.index'),
                        ],
                        [
                            'label' => 'Audit Logs',
                            'route' => route('admin.audit-logs.index'),
                        ],
                    ] as $action)
                        <a
                            href="{{ $action['route'] }}"
                            class="rounded-2xl border-2 border-slate-200 bg-white p-4 text-center text-sm font-bold text-slate-700 shadow-sm transition hover:border-primary/30 hover:bg-primary-tint focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/25"
                        >
                            {{ $action['label'] }}
                        </a>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
