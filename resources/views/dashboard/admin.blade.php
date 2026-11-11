<x-app-layout>
    <x-slot name="header">
        <x-admin.page-header
        title="Admin Dashboard"
            description="Manage accounts, research readiness, content, pilot controls, and operational health."
        >
            <x-slot:actions>
                <a
                    href="{{ route('admin.system-health.index') }}"
                    class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-bold text-slate-700 transition-colors hover:bg-slate-50 focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/25"
                >
                    System health
                </a>

                <a
                    href="{{ route('admin.pilot.index') }}"
                    class="btn-press inline-flex min-h-11 items-center justify-center rounded-xl bg-primary px-4 text-sm font-bold text-white shadow-md focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/25"
                    style="--lip: rgb(var(--color-primary-lip));"
                >
                    Pilot controls
                </a>
            </x-slot:actions>
        </x-admin.page-header>
    </x-slot>

    @php
        $withoutCohort =
            $accountStats['students_without_cohort']
            + $accountStats['faculty_without_cohort'];

        $researchFormsNeedingAttention = collect(
            $formStats,
        )
            ->filter(
                fn (array $stats): bool =>
                    ! $stats['validation_complete'],
            )
            ->count();

        $attentionItems = [
            [
                'count' => $withoutCohort,
                'label' => 'Accounts without cohort assignments',
                'description' => 'Assign students and faculty before cohort reporting or intervention work.',
                'route' => route(
                    'admin.users.index',
                    [
                        'no_cohort' => 1,
                    ],
                ),
                'tone' => 'bg-amber-100 text-amber-900',
            ],
            [
                'count' => $researchFormsNeedingAttention,
                'label' => 'Research forms requiring attention',
                'description' => 'Resolve structural, validation, or publication readiness requirements.',
                'route' => route(
                    'admin.research-forms.index',
                ),
                'tone' => 'bg-red-100 text-red-800',
            ],
            [
                'count' => $questionStats['needing_review'],
                'label' => 'Research questions awaiting validation',
                'description' => 'Review active research questions submitted for faculty validation.',
                'route' => route(
                    'admin.questions.index',
                    [
                        'status' => 'awaiting_validation',
                    ],
                ),
                'tone' => 'bg-primary-tint text-primary',
            ],
            [
                'count' => $pearlStats['in_review'],
                'label' => 'Micro Pearls awaiting review',
                'description' => 'Review submitted Micro Pearls before publication.',
                'route' => route(
                    'content.pearls.index',
                    [
                        'status' => 'in_review',
                    ],
                ),
                'tone' => 'bg-violet-100 text-violet-800',
            ],
            [
                'count' => $accountStats['unverified'],
                'label' => 'Unverified accounts',
                'description' => 'Check accounts that cannot yet access verified application routes.',
                'route' => route(
                    'admin.users.index',
                    [
                        'verification' => 'unverified',
                    ],
                ),
                'tone' => 'bg-slate-100 text-slate-800',
            ],
        ];

        $attentionTotal = collect(
            $attentionItems,
        )->sum('count');
    @endphp

    <div class="admin-surface pt-6 pb-6 sm:pb-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-admin.flash />

            <section
                aria-labelledby="admin-attention-heading"
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
            >
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
                    <div>
                        <h2
                            id="admin-attention-heading"
                            class="font-display text-lg font-extrabold text-slate-950"
                        >
                            Needs attention
                        </h2>

                        <p class="mt-1 text-sm text-slate-600">
                            Work requiring administrative action
                        </p>
                    </div>

                    <span
                        class="rounded-full px-3 py-1 text-xs font-bold
                            {{ $attentionTotal > 0
                                ? 'bg-amber-100 text-amber-900'
                                : 'bg-emerald-100 text-emerald-800' }}"
                    >
                        {{ $attentionTotal > 0
                            ? number_format($attentionTotal).' open'
                            : 'All clear' }}
                    </span>
                </div>

                @if ($attentionTotal > 0)
                    <ul class="divide-y divide-slate-100">
                        @foreach ($attentionItems as $item)
                            @continue($item['count'] === 0)

                            <li>
                                <a
                                    href="{{ $item['route'] }}"
                                    class="group flex min-h-16 items-center gap-4 px-5 py-3 transition-colors hover:bg-slate-50 focus:outline-none focus-visible:ring-4 focus-visible:ring-inset focus-visible:ring-primary/25"
                                >
                                    <span
                                        class="flex h-9 min-w-9 items-center justify-center rounded-full px-2 font-display text-sm font-extrabold tabular-nums {{ $item['tone'] }}"
                                    >
                                        {{ number_format($item['count']) }}
                                    </span>

                                    <span class="min-w-0 flex-1">
                                        <span class="block font-bold text-slate-900">
                                            {{ $item['label'] }}
                                        </span>

                                        <span class="mt-0.5 block text-xs leading-relaxed text-slate-500">
                                            {{ $item['description'] }}
                                        </span>
                                    </span>

                                    <x-admin.icon
                                        name="arrow"
                                        class="h-5 w-5 shrink-0 text-slate-400 transition-transform group-hover:translate-x-1"
                                    />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="px-5 py-8 text-center">
                        <p class="font-bold text-emerald-800">
                            No immediate administrative action is required.
                        </p>

                        <p class="mt-1 text-sm text-slate-500">
                            Continue monitoring pilot readiness and system health.
                        </p>
                    </div>
                @endif
            </section>

            <section aria-labelledby="admin-overview-heading">
                <div class="mb-3">
                    <h2
                        id="admin-overview-heading"
                        class="font-display text-lg font-extrabold text-slate-950"
                    >
                        Account overview
                    </h2>

                    <p class="mt-1 text-sm text-slate-600">
                        Current active accounts and cohort coverage
                    </p>
                </div>

                <dl class="grid overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm sm:grid-cols-2 sm:divide-x sm:divide-y-0 xl:grid-cols-4">
                    <x-admin.metric
                        label="Active students"
                        :value="number_format(
                            $accountStats['students'],
                        )"
                    />

                    <x-admin.metric
                        label="Active faculty"
                        :value="number_format(
                            $accountStats['faculty'],
                        )"
                    />

                    <x-admin.metric
                        label="Active cohorts"
                        :value="number_format(
                            $cohortStats['active'],
                        )"
                        :hint="$cohortStats['archived'].' archived'"
                        tone="primary"
                    />

                    <x-admin.metric
                        label="Without cohorts"
                        :value="number_format($withoutCohort)"
                        hint="Students and faculty"
                        :tone="$withoutCohort > 0
                            ? 'warning'
                            : 'success'"
                    />
                </dl>
            </section>

            <div class="grid gap-6 xl:grid-cols-2">
                <x-admin.panel
                    id="assessment-participation-heading"
                    title="Assessment participation"
                    description="Completion across active student accounts."
                >
                    <x-slot:actions>
                        <a
                            href="{{ route(
                                'admin.research-analytics.index',
                            ) }}"
                            class="text-sm font-bold text-primary underline-offset-4 hover:underline"
                        >
                            Open analytics
                        </a>
                    </x-slot:actions>

                    <div class="space-y-6">
                        <x-admin.progress
                            label="Diagnostic completed"
                            :value="$assessmentStats[
                                'diagnostic_rate'
                            ]"
                            :completed="$assessmentStats[
                                'diagnostic_completed'
                            ]"
                            :total="$accountStats['students']"
                        />

                        <x-admin.progress
                            label="Mock Board finalized"
                            :value="$assessmentStats[
                                'mock_board_rate'
                            ]"
                            :completed="$assessmentStats[
                                'mock_board_completed'
                            ]"
                            :total="$accountStats['students']"
                            tone="success"
                        />
                    </div>
                </x-admin.panel>

                <x-admin.panel
                    id="question-bank-heading"
                    title="Question bank"
                    description="Availability, locks, and validation readiness."
                >
                    <x-slot:actions>
                        <a
                            href="{{ route(
                                'admin.questions.index',
                            ) }}"
                            class="text-sm font-bold text-primary underline-offset-4 hover:underline"
                        >
                            Open question bank
                        </a>
                    </x-slot:actions>

                    <dl class="divide-y divide-slate-100">
                        @foreach ([
                            [
                                'label' => 'Total questions',
                                'value' => $questionStats['total'],
                                'class' => 'text-slate-950',
                            ],
                            [
                                'label' => 'Locked questions',
                                'value' => $questionStats['locked'],
                                'class' => 'text-slate-950',
                            ],
                            [
                                'label' => 'Needing review',
                                'value' => $questionStats[
                                    'needing_review'
                                ],
                                'class' => 'text-amber-800',
                            ],
                            [
                                'label' => 'Validation issues',
                                'value' => $questionStats[
                                    'validation_issues'
                                ],
                                'class' => 'text-red-700',
                            ],
                            [
                                'label' => 'Development placeholders',
                                'value' => $questionStats[
                                    'placeholders'
                                ],
                                'class' => $questionStats[
                                    'placeholders'
                                ] > 0
                                    ? 'text-amber-800'
                                    : 'text-emerald-700',
                            ],
                        ] as $item)
                            <div class="flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0">
                                <dt class="text-sm text-slate-600">
                                    {{ $item['label'] }}
                                </dt>

                                <dd class="font-display text-lg font-extrabold tabular-nums {{ $item['class'] }}">
                                    {{ number_format(
                                        $item['value'],
                                    ) }}
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </x-admin.panel>
            </div>

            <section aria-labelledby="research-forms-heading">
                <div class="mb-3 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h2
                            id="research-forms-heading"
                            class="font-display text-lg font-extrabold text-slate-950"
                        >
                            Research forms
                        </h2>

                        <p class="mt-1 text-sm text-slate-600">
                            Published content and validation readiness
                        </p>
                    </div>

                    <a
                        href="{{ route(
                            'admin.research-forms.index',
                        ) }}"
                        class="text-sm font-bold text-primary underline-offset-4 hover:underline"
                    >
                        Open readiness workflow
                    </a>
                </div>

                <div class="grid gap-6 lg:grid-cols-2">
                    @foreach ($formStats as $form => $stats)
                        <x-admin.panel
                            :id="'research-form-'.$form"
                            :title="$stats['label']"
                            :description="$form"
                        >
                            <x-slot:actions>
                                <span
                                    class="rounded-full px-3 py-1 text-xs font-bold
                                        {{ $stats[
                                            'validation_complete'
                                        ]
                                            ? 'bg-emerald-100 text-emerald-800'
                                            : 'bg-amber-100 text-amber-900' }}"
                                >
                                    {{ $stats[
                                        'validation_complete'
                                    ]
                                        ? 'Validation complete'
                                        : 'Needs attention' }}
                                </span>
                            </x-slot:actions>

                            <dl class="grid grid-cols-2 divide-x divide-y divide-slate-100 overflow-hidden rounded-xl border border-slate-200">
                                <div class="p-3">
                                    <dt class="text-xs text-slate-500">
                                        Questions
                                    </dt>

                                    <dd class="mt-1 font-bold text-slate-950">
                                        {{ $stats[
                                            'active_questions'
                                        ] }}/60
                                    </dd>
                                </div>

                                <div class="p-3">
                                    <dt class="text-xs text-slate-500">
                                        Validated
                                    </dt>

                                    <dd class="mt-1 font-bold text-slate-950">
                                        {{ $stats[
                                            'validated_questions'
                                        ] }}/60
                                    </dd>
                                </div>

                                <div class="p-3">
                                    <dt class="text-xs text-slate-500">
                                        Positions
                                    </dt>

                                    <dd
                                        class="mt-1 font-bold
                                            {{ $stats[
                                                'positions_complete'
                                            ]
                                                ? 'text-emerald-700'
                                                : 'text-red-700' }}"
                                    >
                                        {{ $stats[
                                            'positions_complete'
                                        ]
                                            ? 'Complete'
                                            : 'Incomplete' }}
                                    </dd>
                                </div>

                                <div class="p-3">
                                    <dt class="text-xs text-slate-500">
                                        Placeholders
                                    </dt>

                                    <dd
                                        class="mt-1 font-bold
                                            {{ $stats[
                                                'placeholders'
                                            ] === 0
                                                ? 'text-emerald-700'
                                                : 'text-amber-800' }}"
                                    >
                                        {{ $stats['placeholders'] }}
                                    </dd>
                                </div>
                            </dl>

                            <a
                                href="{{ route(
                                    'admin.questions.index',
                                    [
                                        'form' => $form,
                                    ],
                                ) }}"
                                class="mt-4 inline-flex min-h-11 items-center text-sm font-bold text-primary underline-offset-4 hover:underline"
                            >
                                Review form questions
                            </a>
                        </x-admin.panel>
                    @endforeach
                </div>
            </section>

            <div class="grid gap-6 xl:grid-cols-[0.85fr_1.4fr]">
                <x-admin.panel
                    id="content-workflow-heading"
                    title="Content workflow"
                    description="Micro Pearl authoring and publication."
                >
                    <x-slot:actions>
                        <a
                            href="{{ route(
                                'content.pearls.index',
                            ) }}"
                            class="text-sm font-bold text-primary underline-offset-4 hover:underline"
                        >
                            Manage pearls
                        </a>
                    </x-slot:actions>

                    <dl class="divide-y divide-slate-100">
                        <div class="flex items-center justify-between gap-4 py-3 first:pt-0">
                            <dt class="text-sm text-slate-600">
                                Drafts
                            </dt>

                            <dd class="font-display text-lg font-extrabold tabular-nums text-slate-950">
                                {{ $pearlStats['drafts'] }}
                            </dd>
                        </div>

                        <div class="flex items-center justify-between gap-4 py-3">
                            <dt class="text-sm text-slate-600">
                                Awaiting review
                            </dt>

                            <dd class="font-display text-lg font-extrabold tabular-nums text-amber-800">
                                {{ $pearlStats['in_review'] }}
                            </dd>
                        </div>

                        <div class="flex items-center justify-between gap-4 py-3 last:pb-0">
                            <dt class="text-sm text-slate-600">
                                Published
                            </dt>

                            <dd class="font-display text-lg font-extrabold tabular-nums text-emerald-700">
                                {{ $pearlStats['published'] }}
                            </dd>
                        </div>
                    </dl>
                </x-admin.panel>

                <x-admin.panel
                    id="recent-admin-activity-heading"
                    title="Recent administrative activity"
                    description="Latest recorded governance actions."
                >
                    <x-slot:actions>
                        <a
                            href="{{ route(
                                'admin.audit-logs.index',
                            ) }}"
                            class="text-sm font-bold text-primary underline-offset-4 hover:underline"
                        >
                            View audit history
                        </a>
                    </x-slot:actions>

                    <ol class="divide-y divide-slate-100">
                        @forelse ($recentAudits as $log)
                            <li class="py-4 first:pt-0 last:pb-0">
                                <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-slate-500">
                                    <span class="font-bold text-slate-900">
                                        {{ $log->actorDisplayName() }}
                                    </span>

                                    <span aria-hidden="true">·</span>

                                    <span>
                                        {{ str($log->action)
                                            ->replace('_', ' ')
                                            ->title() }}
                                    </span>

                                    <span aria-hidden="true">·</span>

                                    <time
                                        datetime="{{ $log
                                            ->created_at
                                            ->toIso8601String() }}"
                                        title="{{ $log
                                            ->created_at
                                            ->format(
                                                'M j, Y g:i A',
                                            ) }}"
                                    >
                                        {{ $log
                                            ->created_at
                                            ->diffForHumans() }}
                                    </time>
                                </div>

                                @if ($log->reason)
                                    <p class="mt-2 text-sm leading-relaxed text-slate-700">
                                        {{ \Illuminate\Support\Str::limit(
                                            $log->reason,
                                            160,
                                        ) }}
                                    </p>
                                @endif
                            </li>
                        @empty
                            <li class="py-8 text-center text-sm text-slate-500">
                                No administrative activity has been recorded.
                            </li>
                        @endforelse
                    </ol>
                </x-admin.panel>
            </div>

            <nav
                aria-label="Administrative shortcuts"
                class="flex flex-wrap gap-x-5 gap-y-2 border-t border-slate-200 pt-5 text-sm font-bold"
            >
                <a
                    href="{{ route('admin.users.index') }}"
                    class="min-h-11 content-center text-primary underline-offset-4 hover:underline"
                >
                    Users
                </a>

                <a
                    href="{{ route('admin.cohorts.index') }}"
                    class="min-h-11 content-center text-primary underline-offset-4 hover:underline"
                >
                    Cohorts
                </a>

                <a
                    href="{{ route(
                        'admin.research-form-imports.index',
                    ) }}"
                    class="min-h-11 content-center text-primary underline-offset-4 hover:underline"
                >
                    Import research CSV
                </a>

                <a
                    href="{{ route(
                        'admin.research-participants.index',
                    ) }}"
                    class="min-h-11 content-center text-primary underline-offset-4 hover:underline"
                >
                    Participant scope
                </a>

                <a
                    href="{{ route('admin.audit-logs.index') }}"
                    class="min-h-11 content-center text-primary underline-offset-4 hover:underline"
                >
                    Audit history
                </a>
            </nav>
        </div>
    </div>
</x-app-layout>
