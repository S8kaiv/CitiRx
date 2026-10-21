<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-2">
            <div>
                {{-- Responsive text size so it stays compact on mobile --}}
                <h2 class="font-display text-xl sm:text-2xl lg:text-3xl font-extrabold tracking-tight text-slate-900">
                    <span class="block sm:hidden">Admin Hub</span>
                    <span class="hidden sm:block">Admin Dashboard</span>
                </h2>
                <p class="hidden sm:block text-xs font-semibold uppercase tracking-wider text-muted-ink">
                    Administration
                </p>
            </div>

            {{-- Compact pills with smaller mobile padding to guarantee 1-row fit --}}
            <div class="flex items-center gap-1.5 shrink-0">
                {{-- Students Pill --}}
                <div class="flex items-center gap-1 rounded-xl border-2 border-b-2 sm:border-b-4 border-[#F5A623]/30 bg-gold-tint px-2.5 py-1 sm:px-3.5 sm:py-1.5 font-display font-bold text-gold-ink shadow-2xs"
                    title="Active students">
                    <span class="text-xs sm:text-sm">🎓</span>
                    <span class="text-xs sm:text-sm font-black">{{ number_format($accountStats['students']) }}</span>
                    <span class="text-[9px] tracking-wider opacity-80 hidden xs:inline">STUDENTS</span>
                </div>

                {{-- Active Cohorts Pill --}}
                <div class="flex items-center gap-1 rounded-xl border-2 border-b-2 sm:border-b-4 border-[#F5A623]/30 bg-gold-tint px-2.5 py-1 sm:px-3.5 sm:py-1.5 font-display font-bold text-gold-ink shadow-2xs"
                    title="Active cohorts">
                    <span class="text-xs sm:text-sm">🗂️</span>
                    <span class="text-xs sm:text-sm font-black">{{ number_format($cohortStats['active']) }}</span>
                    <span class="text-[9px] tracking-wider opacity-80 hidden xs:inline">COHORTS</span>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-6 font-sans text-slate-900 antialiased">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('status'))
                <div role="status"
                    class="rounded-2xl border-2 border-b-4 border-emerald-300 bg-emerald-50 p-4 font-display text-sm font-bold text-emerald-800 shadow-2xs">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Welcome Bar --}}
            <div
                class="flex items-center justify-between rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-5 sm:p-6 shadow-sm">
                <div class="flex items-center gap-4">
                    <div>
                        <h1 class="font-display text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
                            Welcome back, {{ method_exists($user, 'fullName') ? $user->fullName() : $user->name }}!
                        </h1>
                        <p class="text-xs font-medium text-muted-ink mt-0.5">
                            <span class="font-semibold text-slate-800">System Administrator</span> ·
                            Monitor users, cohorts, assessment participation, research-form preparation, and
                            administrative activity.
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">

                {{-- MAIN COLUMN --}}
                <div class="space-y-6 lg:col-span-2">

                    {{-- 1. HERO CARD: Account management --}}
                    <section
                        class="relative overflow-hidden rounded-[1.5rem] border-2 border-b-4 border-primary/30 bg-gradient-to-br from-primary-tint via-[#FAF8FF] to-white p-6 sm:p-8 shadow-sm">
                        <div class="flex items-center gap-2">
                            <span
                                class="inline-flex items-center rounded-full bg-white px-3 py-1 font-display text-xs font-bold text-primary shadow-sm ring-1 ring-primary/20">
                                Account Management
                            </span>
                            @if ($accountStats['unverified'] > 0)
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full bg-gold-tint px-2.5 py-0.5 text-xs font-bold text-gold-ink">
                                    <span class="h-2 w-2 animate-pulse rounded-full bg-gold"></span>
                                    {{ $accountStats['unverified'] }} unverified
                                </span>
                            @endif
                        </div>

                        <h2 class="mt-3 font-display text-2xl font-extrabold text-slate-900 sm:text-3xl">
                            Users & Cohorts
                        </h2>
                        <p class="mt-2 max-w-xl text-sm leading-relaxed text-slate-700">
                            Keep every student and faculty account verified and assigned to a cohort.
                        </p>

                        <div class="mt-6 flex flex-wrap items-center gap-4">
                            <a href="{{ route('admin.cohorts.index') }}" style="--lip: #4A2FC4;"
                                class="btn-press inline-flex items-center rounded-2xl bg-primary px-6 py-3 font-display text-base font-bold text-white shadow-md">
                                Manage Cohorts
                            </a>

                            <a href="{{ route('admin.users.index') }}"
                                class="font-display text-sm font-bold text-primary transition hover:underline">
                                Manage Users →
                            </a>
                        </div>
                    </section>

                    {{-- 2. ACCOUNT OVERVIEW TILES --}}
                    <section aria-labelledby="account-overview-heading" class="space-y-4">
                        <div class="flex items-center justify-between gap-4">
                            <h3 id="account-overview-heading" class="font-display text-lg font-black text-slate-900">
                                Account Overview
                            </h3>
                            <a href="{{ route('admin.users.index') }}"
                                class="font-display text-xs font-bold text-primary transition hover:underline">
                                View all users →
                            </a>
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">

                            {{-- Active Students Tile (Indigo Theme) --}}
                            <article
                                class="flex flex-col justify-between rounded-3xl border-2 border-b-4 border-indigo-300 bg-gradient-to-br from-indigo-50/80 via-white to-white p-5 sm:p-6 shadow-sm">
                                <div>
                                    <div
                                        class="flex h-12 w-12 items-center justify-center rounded-2xl border-2 border-b-3 border-indigo-300 bg-indigo-100 text-2xl shadow-2xs">
                                        🎓
                                    </div>
                                    <h4 class="mt-4 font-display text-lg font-black text-slate-900">Active Students</h4>
                                    <p class="text-xs font-semibold text-muted-ink mt-0.5">
                                        <span
                                            class="font-bold text-slate-900">{{ number_format($accountStats['students']) }}</span>
                                        enrolled accounts.
                                    </p>
                                </div>
                                <div class="mt-6">
                                    <a href="{{ route('admin.users.index', ['role' => 'student']) }}"
                                        style="--lip: #4338CA;"
                                        class="btn-press block w-full rounded-2xl bg-indigo-600 py-3 text-center font-display text-xs font-black uppercase tracking-wider text-white shadow-md transition hover:bg-indigo-700">
                                        View Students
                                    </a>
                                </div>
                            </article>

                            {{-- Active Faculty Tile (Sky Theme) --}}
                            <article
                                class="flex flex-col justify-between rounded-3xl border-2 border-b-4 border-sky-300 bg-gradient-to-br from-sky-50/80 via-white to-white p-5 sm:p-6 shadow-sm">
                                <div>
                                    <div
                                        class="flex h-12 w-12 items-center justify-center rounded-2xl border-2 border-b-3 border-sky-300 bg-sky-100 text-2xl shadow-2xs">
                                        🩺
                                    </div>
                                    <h4 class="mt-4 font-display text-lg font-black text-slate-900">Active Faculty</h4>
                                    <p class="text-xs font-semibold text-muted-ink mt-0.5">
                                        <span
                                            class="font-bold text-slate-900">{{ number_format($accountStats['faculty']) }}</span>
                                        faculty accounts.
                                    </p>
                                </div>
                                <div class="mt-6">
                                    <a href="{{ route('admin.users.index', ['role' => 'faculty']) }}"
                                        style="--lip: #0369A1;"
                                        class="btn-press block w-full rounded-2xl bg-sky-500 py-3 text-center font-display text-xs font-black uppercase tracking-wider text-white shadow-md transition hover:bg-sky-600">
                                        View Faculty
                                    </a>
                                </div>
                            </article>

                            {{-- Students Without Cohort Tile (Amber Theme) --}}
                            <article
                                class="flex flex-col justify-between rounded-3xl border-2 border-b-4 border-amber-300 bg-gradient-to-br from-amber-50/80 via-white to-white p-5 sm:p-6 shadow-sm">
                                <div>
                                    <div
                                        class="flex h-12 w-12 items-center justify-center rounded-2xl border-2 border-b-3 border-amber-300 bg-amber-100 text-2xl shadow-2xs">
                                        ⚠️
                                    </div>
                                    <h4 class="mt-4 font-display text-lg font-black text-slate-900">Students Without
                                        Cohort</h4>
                                    <p class="text-xs font-semibold text-muted-ink mt-0.5">
                                        @if ($accountStats['students_without_cohort'] > 0)
                                            <span
                                                class="font-bold text-amber-900">{{ number_format($accountStats['students_without_cohort']) }}</span>
                                            awaiting assignment.
                                        @else
                                            <span
                                                class="font-bold text-slate-900">{{ number_format($accountStats['students_without_cohort']) }}</span>
                                            · All students assigned.
                                        @endif
                                    </p>
                                </div>
                                @if ($accountStats['students_without_cohort'] > 0)
                                    <div class="mt-6">
                                        <a href="{{ route('admin.users.index', ['role' => 'student', 'no_cohort' => 1]) }}"
                                            style="--lip: #B45309;"
                                            class="btn-press block w-full rounded-2xl bg-amber-500 py-3 text-center font-display text-xs font-black uppercase tracking-wider text-white shadow-md transition hover:bg-amber-600">
                                            Assign Cohorts
                                        </a>
                                    </div>
                                @endif
                            </article>

                            {{-- Faculty Without Cohort Tile (Amber Theme) --}}
                            <article
                                class="flex flex-col justify-between rounded-3xl border-2 border-b-4 border-amber-300 bg-gradient-to-br from-amber-50/80 via-white to-white p-5 sm:p-6 shadow-sm">
                                <div>
                                    <div
                                        class="flex h-12 w-12 items-center justify-center rounded-2xl border-2 border-b-3 border-amber-300 bg-amber-100 text-2xl shadow-2xs">
                                        ⚠️
                                    </div>
                                    <h4 class="mt-4 font-display text-lg font-black text-slate-900">Faculty Without
                                        Cohort</h4>
                                    <p class="text-xs font-semibold text-muted-ink mt-0.5">
                                        @if ($accountStats['faculty_without_cohort'] > 0)
                                            <span
                                                class="font-bold text-amber-900">{{ number_format($accountStats['faculty_without_cohort']) }}</span>
                                            awaiting assignment.
                                        @else
                                            <span
                                                class="font-bold text-slate-900">{{ number_format($accountStats['faculty_without_cohort']) }}</span>
                                            · All faculty assigned.
                                        @endif
                                    </p>
                                </div>
                                @if ($accountStats['faculty_without_cohort'] > 0)
                                    <div class="mt-6">
                                        <a href="{{ route('admin.users.index', ['role' => 'faculty', 'no_cohort' => 1]) }}"
                                            style="--lip: #B45309;"
                                            class="btn-press block w-full rounded-2xl bg-amber-500 py-3 text-center font-display text-xs font-black uppercase tracking-wider text-white shadow-md transition hover:bg-amber-600">
                                            Assign Cohorts
                                        </a>
                                    </div>
                                @endif
                            </article>
                        </div>

                        {{-- Status Pills Row --}}
                        <div class="flex flex-wrap gap-2 pt-1 text-xs font-bold text-slate-600">
                            <div
                                class="flex items-center gap-1.5 rounded-xl border-2 border-b-2 border-slate-200 bg-white px-3 py-1.5 shadow-2xs">
                                <span>Active cohorts:</span>
                                <span class="font-black text-slate-900">{{ $cohortStats['active'] }}</span>
                            </div>
                            <div
                                class="flex items-center gap-1.5 rounded-xl border-2 border-b-2 border-slate-200 bg-white px-3 py-1.5 shadow-2xs">
                                <span>Archived cohorts:</span>
                                <span class="font-black text-slate-900">{{ $cohortStats['archived'] }}</span>
                            </div>
                            <div
                                class="flex items-center gap-1.5 rounded-xl border-2 border-b-2 border-slate-200 bg-white px-3 py-1.5 shadow-2xs">
                                <span>Unverified accounts:</span>
                                <span class="font-black text-slate-900">{{ $accountStats['unverified'] }}</span>
                            </div>
                        </div>
                    </section>

                    {{-- 3. RESEARCH FORMS --}}
                    <section aria-labelledby="research-form-heading" class="space-y-4">
                        <div class="flex flex-wrap items-end justify-between gap-3">
                            <div>
                                <h3 id="research-form-heading" class="font-display text-lg font-black text-slate-900">
                                    Research Forms
                                </h3>
                                <p class="mt-0.5 text-xs font-semibold text-muted-ink">
                                    Lightweight summaries. Open readiness for complete shared blueprint and audit.
                                </p>
                            </div>
                            <a href="{{ route('admin.research-forms.index') }}"
                                class="font-display text-xs font-bold text-primary hover:underline">
                                Open readiness audit →
                            </a>
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            @foreach ($formStats as $form => $stats)
                                <article
                                    class="flex flex-col justify-between rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-5 sm:p-6 shadow-sm">
                                    <div>
                                        <div class="flex flex-wrap items-start justify-between gap-3">
                                            <div>
                                                <h4 class="font-display text-lg font-extrabold text-slate-900">
                                                    {{ $stats['label'] }}
                                                </h4>
                                                <p class="mt-0.5 font-mono text-xs font-semibold text-muted-ink">
                                                    {{ $form }}
                                                </p>
                                            </div>

                                            @if ($stats['validation_complete'])
                                                <span
                                                    class="rounded-full border border-emerald-300 bg-emerald-100 px-3 py-1 font-display text-xs font-bold text-emerald-700 shadow-2xs">
                                                    Validation complete
                                                </span>
                                            @else
                                                <span
                                                    class="rounded-full border border-amber-300 bg-amber-100 px-3 py-1 font-display text-xs font-bold text-amber-800 shadow-2xs">
                                                    Needs attention
                                                </span>
                                            @endif
                                        </div>

                                        <dl class="mt-5 grid grid-cols-2 gap-3 text-xs">
                                            <div class="rounded-2xl border-2 border-slate-100 bg-slate-50 p-3">
                                                <dt class="font-bold text-muted-ink">Active questions</dt>
                                                <dd class="mt-1 font-display font-extrabold text-slate-900">
                                                    {{ $stats['active_questions'] }}/60
                                                </dd>
                                            </div>
                                            <div class="rounded-2xl border-2 border-slate-100 bg-slate-50 p-3">
                                                <dt class="font-bold text-muted-ink">Validated</dt>
                                                <dd class="mt-1 font-display font-extrabold text-slate-900">
                                                    {{ $stats['validated_questions'] }}/60
                                                </dd>
                                            </div>
                                            <div class="rounded-2xl border-2 border-slate-100 bg-slate-50 p-3">
                                                <dt class="font-bold text-muted-ink">Positions 1–60</dt>
                                                <dd
                                                    class="mt-1 font-display font-extrabold {{ $stats['positions_complete'] ? 'text-emerald-700' : 'text-rose-700' }}">
                                                    {{ $stats['positions_complete'] ? 'Complete' : 'Incomplete' }}
                                                </dd>
                                            </div>
                                            <div class="rounded-2xl border-2 border-slate-100 bg-slate-50 p-3">
                                                <dt class="font-bold text-muted-ink">Placeholders</dt>
                                                <dd
                                                    class="mt-1 font-display font-extrabold {{ $stats['placeholders'] === 0 ? 'text-emerald-700' : 'text-amber-800' }}">
                                                    {{ $stats['placeholders'] }}
                                                </dd>
                                            </div>
                                        </dl>
                                    </div>

                                    <div class="mt-5">
                                        <a href="{{ route('admin.questions.index', ['form' => $form]) }}"
                                            style="--lip: #CBD5E1;"
                                            class="btn-press block w-full rounded-2xl border-2 border-b-4 border-slate-300 bg-white py-3 text-center font-display text-xs font-black uppercase tracking-wider text-slate-700 shadow-2xs transition hover:bg-slate-50">
                                            Review This Form
                                        </a>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </section>

                    {{-- 4. RECENT ADMINISTRATIVE ACTIVITY --}}
                    <article
                        class="flex flex-col justify-between rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-5 sm:p-6 shadow-sm">
                        <div>
                            <div class="flex items-center justify-between gap-4">
                                <h3 class="font-display text-lg font-black text-slate-900">
                                    Recent Administrative Activity
                                </h3>
                                <a href="{{ route('admin.audit-logs.index') }}"
                                    class="font-display text-xs font-bold text-primary hover:underline">
                                    View all →
                                </a>
                            </div>

                            <div class="mt-5 space-y-3">
                                @forelse ($recentAudits as $log)
                                    <article class="rounded-2xl border-2 border-slate-100 bg-slate-50/50 p-4">
                                        <div class="flex flex-wrap items-center gap-2 text-xs font-bold text-muted-ink">
                                            <span class="text-slate-900">
                                                {{ $log->admin?->fullName() ?? 'Former administrator' }}
                                            </span>
                                            <span aria-hidden="true">•</span>
                                            <span class="text-primary">
                                                {{ str($log->action)->replace('_', ' ')->title() }}
                                            </span>
                                            <span aria-hidden="true">•</span>
                                            <time datetime="{{ $log->created_at->toIso8601String() }}"
                                                title="{{ $log->created_at->format('M j, Y g:i A') }}">
                                                {{ $log->created_at->diffForHumans() }}
                                            </time>
                                        </div>

                                        <p class="mt-1.5 font-mono text-xs font-semibold text-slate-500">
                                            {{ class_basename($log->target_type) }} · {{ $log->target_id }}
                                        </p>

                                        @if ($log->reason)
                                            <p class="mt-2 text-xs font-medium text-slate-700 leading-relaxed">
                                                {{ \Illuminate\Support\Str::limit($log->reason, 140) }}
                                            </p>
                                        @endif
                                    </article>
                                @empty
                                    <div
                                        class="rounded-2xl border-2 border-slate-100 bg-slate-50 p-6 text-center text-xs font-semibold text-muted-ink">
                                        No administrative activity has been recorded.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </article>

                </div>

                {{-- SIDEBAR: Assessment, Question Bank & Workflow Widgets --}}
                <aside class="space-y-6">

                    {{-- Assessment Participation Widget --}}
                    <section aria-labelledby="assessment-progress-heading"
                        class="rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-6 shadow-sm">
                        <h3 id="assessment-progress-heading"
                            class="font-display text-xs font-extrabold uppercase tracking-wider text-muted-ink">
                            Assessment Participation
                        </h3>

                        <div class="mt-5 space-y-5">
                            {{-- Diagnostic Progress Bar --}}
                            <div>
                                <div class="flex items-center justify-between gap-4 text-xs font-extrabold">
                                    <span class="text-slate-800">Diagnostic completed</span>
                                    <span class="text-muted-ink">
                                        {{ $assessmentStats['diagnostic_completed'] }} / {{ $accountStats['students'] }}
                                    </span>
                                </div>
                                <div class="mt-2 h-3.5 overflow-hidden rounded-full border border-slate-200 bg-slate-100 p-0.5"
                                    role="progressbar" aria-label="Diagnostic completion" aria-valuemin="0"
                                    aria-valuemax="100" aria-valuenow="{{ $assessmentStats['diagnostic_rate'] }}">
                                    <div class="h-full rounded-full bg-primary transition-all duration-500"
                                        style="width: {{ min(100, max(0, $assessmentStats['diagnostic_rate'])) }}%">
                                    </div>
                                </div>
                                <p class="mt-1.5 text-right font-display text-xs font-black text-primary">
                                    {{ number_format($assessmentStats['diagnostic_rate'], 1) }}%
                                </p>
                            </div>

                            {{-- Mock Board Progress Bar --}}
                            <div>
                                <div class="flex items-center justify-between gap-4 text-xs font-extrabold">
                                    <span class="text-slate-800">Mock Board finalized</span>
                                    <span class="text-muted-ink">
                                        {{ $assessmentStats['mock_board_completed'] }} / {{ $accountStats['students'] }}
                                    </span>
                                </div>
                                <div class="mt-2 h-3.5 overflow-hidden rounded-full border border-slate-200 bg-slate-100 p-0.5"
                                    role="progressbar" aria-label="Mock Board completion" aria-valuemin="0"
                                    aria-valuemax="100" aria-valuenow="{{ $assessmentStats['mock_board_rate'] }}">
                                    <div class="h-full rounded-full bg-emerald-500 transition-all duration-500"
                                        style="width: {{ min(100, max(0, $assessmentStats['mock_board_rate'])) }}%">
                                    </div>
                                </div>
                                <p class="mt-1.5 text-right font-display text-xs font-black text-emerald-600">
                                    {{ number_format($assessmentStats['mock_board_rate'], 1) }}%
                                </p>
                            </div>
                        </div>

                        <div class="mt-5 border-t border-slate-100 pt-4">
                            <a href="{{ route('admin.research-analytics.index') }}"
                                class="font-display text-xs font-bold text-primary hover:underline">
                                Open detailed research analytics →
                            </a>
                        </div>
                    </section>

                    {{-- Question Bank Widget --}}
                    <section class="rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="font-display text-xs font-extrabold uppercase tracking-wider text-muted-ink">
                            Question Bank
                        </h3>

                        <dl class="mt-4 grid grid-cols-2 gap-3">
                            <div class="rounded-2xl border-2 border-slate-100 bg-slate-50 p-3.5">
                                <dt class="text-[10px] font-extrabold uppercase tracking-wider text-muted-ink">Total
                                </dt>
                                <dd class="mt-1 font-display text-2xl font-black text-slate-900">
                                    {{ $questionStats['total'] }}
                                </dd>
                            </div>
                            <div class="rounded-2xl border-2 border-slate-100 bg-slate-50 p-3.5">
                                <dt class="text-[10px] font-extrabold uppercase tracking-wider text-muted-ink">Locked
                                </dt>
                                <dd class="mt-1 font-display text-2xl font-black text-slate-900">
                                    {{ $questionStats['locked'] }}
                                </dd>
                            </div>
                            <div class="rounded-2xl border-2 border-amber-200 bg-amber-50 p-3.5">
                                <dt class="text-[10px] font-extrabold uppercase tracking-wider text-amber-700">Needing
                                    review</dt>
                                <dd class="mt-1 font-display text-2xl font-black text-amber-900">
                                    {{ $questionStats['needing_review'] }}
                                </dd>
                            </div>
                            <div class="rounded-2xl border-2 border-rose-200 bg-rose-50 p-3.5">
                                <dt class="text-[10px] font-extrabold uppercase tracking-wider text-rose-700">
                                    Validation issues</dt>
                                <dd class="mt-1 font-display text-2xl font-black text-rose-900">
                                    {{ $questionStats['validation_issues'] }}
                                </dd>
                            </div>
                        </dl>

                        @if ($questionStats['placeholders'] > 0)
                            <div
                                class="mt-4 rounded-2xl border-2 border-amber-300 bg-amber-50/80 p-3.5 font-display text-xs font-extrabold text-amber-900">
                                {{ $questionStats['placeholders'] }} development placeholder question(s) remain.
                            </div>
                        @endif

                        <div class="mt-5 border-t border-slate-100 pt-4">
                            <a href="{{ route('admin.questions.index') }}"
                                class="font-display text-xs font-bold text-primary hover:underline">
                                Open bank →
                            </a>
                        </div>
                    </section>

                    {{-- Content Workflow Widget --}}
                    <section class="rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="font-display text-xs font-extrabold uppercase tracking-wider text-muted-ink">
                            Content Workflow
                        </h3>

                        <dl class="mt-4 space-y-3 text-xs sm:text-sm">
                            <div
                                class="flex items-center justify-between gap-4 rounded-2xl border-2 border-slate-100 bg-slate-50 p-3.5">
                                <dt class="font-bold text-slate-700">Micro Pearl drafts</dt>
                                <dd class="font-display font-black text-slate-900">{{ $pearlStats['drafts'] }}</dd>
                            </div>
                            <div
                                class="flex items-center justify-between gap-4 rounded-2xl border-2 border-amber-200 bg-amber-50 p-3.5">
                                <dt class="font-bold text-amber-800">Awaiting review</dt>
                                <dd class="font-display font-black text-amber-900">{{ $pearlStats['in_review'] }}</dd>
                            </div>
                            <div
                                class="flex items-center justify-between gap-4 rounded-2xl border-2 border-emerald-200 bg-emerald-50 p-3.5">
                                <dt class="font-bold text-emerald-800">Safely published</dt>
                                <dd class="font-display font-black text-emerald-900">{{ $pearlStats['published'] }}
                                </dd>
                            </div>
                        </dl>
                    </section>

                </aside>
            </div>

            {{-- Quick Actions --}}
            <section aria-labelledby="quick-actions-heading" class="space-y-3">
                <h3 id="quick-actions-heading" class="font-display text-lg font-black text-slate-900">
                    Quick Actions
                </h3>

                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                    @foreach ([
                        ['label' => 'Users', 'route' => route('admin.users.index')],
                        ['label' => 'Cohorts', 'route' => route('admin.cohorts.index')],
                        ['label' => 'Questions', 'route' => route('admin.questions.index')],
                        ['label' => 'Analytics', 'route' => route('admin.research-analytics.index')],
                        ['label' => 'Audit Logs', 'route' => route('admin.audit-logs.index')],
                    ] as $action)
                        <a href="{{ $action['route'] }}" style="--lip: #CBD5E1;"
                            class="btn-press rounded-2xl border-2 border-b-4 border-slate-300 bg-white p-3.5 text-center font-display text-xs font-extrabold uppercase tracking-wider text-slate-700 shadow-2xs transition hover:border-primary/40 hover:bg-primary-tint hover:text-primary">
                            {{ $action['label'] }}
                        </a>
                    @endforeach
                </div>
            </section>

        </div>
    </div>
</x-app-layout>