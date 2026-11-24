<x-app-layout surface="admin">
    <x-slot name="header">
        <x-admin.page-header title="Admin Dashboard">
            <x-slot:actions>
                <x-ui.button variant="secondary" :href="route('admin.system-health.index')">
                    System health
                </x-ui.button>

                <x-ui.button :href="route('admin.pilot.index')">
                    Pilot controls
                </x-ui.button>
            </x-slot:actions>
        </x-admin.page-header>
    </x-slot>

    @php
        $withoutCohort = $accountStats['students_without_cohort'] + $accountStats['faculty_without_cohort'];
        $formsNeedingAttention = collect($formStats)
            ->filter(fn (array $form) => ! $form['validation_complete'])
            ->count();

        $attention = array_filter([
            [
                'label' => 'Research forms needing attention',
                'count' => $formsNeedingAttention,
                'url' => route('admin.research-forms.index'),
            ],
            [
                'label' => 'Questions with validation issues',
                'count' => $questionStats['validation_issues'],
                'url' => route('admin.questions.index', ['status' => 'validation_issue']),
            ],
            [
                'label' => 'Questions awaiting validation',
                'count' => $questionStats['needing_review'],
                'url' => route('admin.questions.index', ['status' => 'awaiting_validation']),
            ],
            [
                'label' => 'Accounts without a cohort',
                'count' => $withoutCohort,
                'url' => route('admin.users.index', ['no_cohort' => 1]),
            ],
            [
                'label' => 'Unverified accounts',
                'count' => $accountStats['unverified'],
                'url' => route('admin.users.index', ['verification' => 'unverified']),
            ],
            [
                'label' => 'Micro Pearls awaiting review',
                'count' => $pearlStats['in_review'],
                'url' => route('content.pearls.index', ['status' => 'in_review']),
            ],
        ], fn (array $item) => $item['count'] > 0);
    @endphp

<div class="admin-surface py-6 sm:pb-8">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <x-admin.flash />

        <section aria-labelledby="admin-overview-heading" class="space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 id="admin-overview-heading" class="font-display text-xl font-extrabold text-ink">
                    Overview
                </h2>

                @if ($attention === [])
                    <x-ui.badge variant="success">All clear</x-ui.badge>
                @endif
            </div>

            @if ($attention !== [])
                <section aria-labelledby="admin-attention-heading"
                    class="overflow-hidden rounded-2xl border border-warning-strong/40 bg-surface">
                    <h3 id="admin-attention-heading" class="bg-warning-tint px-4 py-3 font-bold text-warning-ink">
                        Needs attention
                    </h3>

                    <ul class="divide-y divide-line">
                        @foreach ($attention as $item)
                            <li>
                                <a href="{{ $item['url'] }}"
                                    class="rx-admin-attention-link flex min-h-12 items-center gap-3 px-4 py-2.5 text-sm focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary">
                                    <span class="min-w-0 flex-1 break-words font-bold text-ink">
                                        {{ $item['label'] }}
                                    </span>
                                    <span class="shrink-0 font-extrabold tabular-nums text-warning-ink">
                                        {{ number_format($item['count']) }}
                                    </span>
                                    <x-admin.icon name="arrow" class="h-4 w-4 shrink-0 text-ink-muted" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <dl class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                @foreach ([
                    'Active students' => $accountStats['students'],
                    'Active faculty' => $accountStats['faculty'],
                    'Active cohorts' => $cohortStats['active'],
                    'Unassigned accounts' => $withoutCohort,
                ] as $label => $total)
                    <div class="min-w-0 rounded-2xl border border-line bg-surface px-4 py-3 sm:px-5 sm:py-4">
                        <dt class="text-sm font-medium text-ink-muted">{{ $label }}</dt>
                        <dd class="mt-1 font-display text-2xl font-extrabold tabular-nums text-ink">
                            {{ number_format($total) }}
                        </dd>

                        @if ($label === 'Active cohorts' && $cohortStats['archived'] > 0)
                            <dd class="mt-1 text-xs text-ink-muted">
                                {{ number_format($cohortStats['archived']) }} archived
                            </dd>
                        @endif
                    </div>
                @endforeach
            </dl>
        </section>

        <div class="grid gap-5 xl:grid-cols-2">
            <x-admin.panel id="assessment-overview-heading" title="Assessment progress"
                description="Completion across active student accounts.">
                <x-slot:actions>
                    <x-ui.button variant="secondary" :href="route('admin.research-analytics.index')">
                        Open analytics
                    </x-ui.button>
                </x-slot:actions>

                <div class="space-y-4 rounded-xl border border-primary/25 bg-primary-tint/35 p-4">
                    <x-admin.progress label="Form A diagnostic"
                        :value="$assessmentStats['diagnostic_rate']"
                        :completed="$assessmentStats['diagnostic_completed']"
                        :total="$accountStats['students']" />

                    <x-admin.progress label="Form B mock board"
                        :value="$assessmentStats['mock_board_rate']"
                        :completed="$assessmentStats['mock_board_completed']"
                        :total="$accountStats['students']"
                        tone="success" />
                </div>

                <div class="mt-5 border-t border-line pt-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h3 class="font-display text-base font-extrabold text-ink">Form readiness</h3>
                        <x-ui.button variant="ghost" :href="route('admin.research-forms.index')">
                            Manage forms
                        </x-ui.button>
                    </div>

                    <ul class="divide-y divide-line">
                        @foreach ($formStats as $form => $stats)
                            <li class="py-3 last:pb-0">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <a href="{{ route('admin.questions.index', ['form' => $form]) }}"
                                        class="font-bold text-primary-ink underline-offset-4 hover:underline">
                                        {{ $stats['label'] }}
                                    </a>

                                    <x-ui.badge :variant="$stats['validation_complete'] ? 'success' : 'warning'">
                                        {{ $stats['validation_complete'] ? 'Content ready' : 'Review needed' }}
                                    </x-ui.badge>
                                </div>

                                <p class="mt-1 text-xs text-ink-muted">
                                    {{ $stats['validated_questions'] }}/{{ \App\Services\ResearchFormBlueprint::total($form) }} validated
                                    &middot; {{ $stats['active_questions'] }} active
                                    @if ($stats['placeholders'] > 0)
                                        &middot; {{ $stats['placeholders'] }} placeholders
                                    @endif
                                    @unless ($stats['positions_complete'])
                                        &middot; Incomplete positions
                                    @endunless
                                </p>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </x-admin.panel>

            <x-admin.panel id="recent-admin-activity-heading" title="Recent activity">
                <x-slot:actions>
                    <x-ui.button variant="ghost" :href="route('admin.audit-logs.index')">
                        Audit history
                    </x-ui.button>
                </x-slot:actions>

                <ol class="divide-y divide-line">
                    @forelse ($recentAudits->take(5) as $log)
                        <li class="flex items-start justify-between gap-3 py-3 first:pt-0 last:pb-0">
                            <div class="min-w-0">
                                <p class="break-words text-sm font-bold text-ink">
                                    {{ str($log->action)->replace(['.', '_'], ' ')->headline() }}
                                </p>
                                <p class="mt-1 break-words text-xs text-ink-muted">
                                    {{ $log->actorDisplayName() }}
                                </p>
                            </div>

                            <time datetime="{{ $log->created_at->toIso8601String() }}"
                                title="{{ $log->created_at->format('M j, Y g:i A') }}"
                                class="mt-0.5 shrink-0 text-xs tabular-nums text-ink-muted">
                                {{ $log->created_at->diffForHumans(short: true) }}
                            </time>
                        </li>
                    @empty
                        <li class="text-sm text-ink-muted">No administrative activity yet.</li>
                    @endforelse
                </ol>
            </x-admin.panel>

            <x-admin.panel id="question-bank-heading" title="Question Bank">
                <x-slot:actions>
                    <x-ui.button variant="ghost" :href="route('admin.questions.index')">
                        Browse {{ number_format($questionStats['total']) }} questions
                    </x-ui.button>
                </x-slot:actions>

                <dl class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                    @foreach ([
                        'Locked' => 'locked',
                        'Awaiting validation' => 'needing_review',
                        'Validation issues' => 'validation_issues',
                        'Placeholders' => 'placeholders',
                    ] as $label => $key)
                        <div class="min-w-0">
                            <dt class="text-ink-muted">{{ $label }}</dt>
                            <dd class="mt-1 font-display text-lg font-extrabold tabular-nums text-ink">
                                {{ number_format($questionStats[$key]) }}
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </x-admin.panel>

            <x-admin.panel id="content-workflow-heading" title="Micro Pearls">
                <x-slot:actions>
                    <x-ui.button variant="ghost" :href="route('content.pearls.index')">
                        Manage pearls
                    </x-ui.button>
                </x-slot:actions>

                <dl class="grid grid-cols-3 gap-3 text-sm">
                    @foreach (['Drafts' => 'drafts', 'In review' => 'in_review', 'Published' => 'published'] as $label => $key)
                        <div class="min-w-0">
                            <dt class="text-ink-muted">{{ $label }}</dt>
                            <dd class="mt-1 font-display text-lg font-extrabold tabular-nums text-ink">
                                {{ number_format($pearlStats[$key]) }}
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </x-admin.panel>
        </div>
    </div>
</div>
</x-app-layout>
