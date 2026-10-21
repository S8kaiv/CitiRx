<files>
<file name="resources\views\auth\verify-email.blade.php">
<![CDATA[
<x-guest-layout fullscreen>
    <div class="flex min-h-screen flex-col items-center justify-center bg-slate-50 p-4 sm:p-6 lg:p-12 xl:p-16">
        <div class="w-full max-w-lg text-center">
            
            {{-- Brand Header (Centered) --}}
            <div class="mb-12 flex items-center justify-center gap-4">
                <div class="flex h-16 w-16 items-center justify-center rounded-3xl bg-indigo-600 text-white font-display text-2xl font-extrabold shadow-lg rotate-[-6deg]">
                    Rx
                </div>
                <div class="text-left">
                    <p class="font-display text-3xl font-extrabold leading-none text-slate-900">CitiRx</p>
                    <p class="mt-1.5 text-sm font-medium text-slate-400">PHLE adaptive prep</p>
                </div>
            </div>

            {{-- Verification Card --}}
            <div class="rounded-[40px] border border-slate-200 bg-white p-10 shadow-xl shadow-indigo-100/60 lg:p-16">
                
                {{-- Email Icon Header (Centered) --}}
                <div class="mb-6 flex justify-center">
                    <div class="inline-flex h-16 w-16 items-center justify-center rounded-[20px] bg-indigo-50 text-indigo-600">
                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                        </svg>
                    </div>
                </div>

                {{-- Card Heading & Text (Centered) --}}
                <div class="mb-10 text-center">
                    <h1 class="font-display text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900">Check your inbox</h1>
                    <p class="mt-4 text-sm sm:text-base text-slate-500 leading-relaxed max-w-md mx-auto">
                        Thanks for signing up! Before getting started, please verify your email address by clicking on the link we just emailed to you.
                    </p>
                </div>

                {{-- Status Message (Centered) --}}
                @if (session('status') == 'verification-link-sent')
                    <div class="mb-8 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-5 text-left text-sm font-medium text-emerald-700" role="status">
                        <svg class="mt-0.5 h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                        </svg>
                        <span>A new verification link has been sent to the email address you provided during registration.</span>
                    </div>
                @endif

                {{-- Action Buttons (Centered & Stacked) --}}
                <div class="space-y-4">
                    <form method="POST" action="{{ route('verification.send') }}"
                        x-data="{ busy: false }"
                        @submit="busy = true"
                        @pageshow.window="busy = false">
                        @csrf

                        <button type="submit"
                            :disabled="busy"
                            style="--lip: #4A2FC4;"
                            class="btn-press flex w-full items-center justify-center rounded-[20px] bg-primary px-8 py-4 font-display text-lg font-bold text-white shadow-md hover:bg-primary-lip disabled:opacity-70 focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/25 transition">
                            <span x-text="busy ? 'Resending Link…' : 'Resend Verification Email'">Resend Verification Email</span>
                        </button>
                    </form>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <button type="submit" class="w-full rounded-[20px] border border-slate-200 py-4 text-center text-base font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            Log Out
                        </button>
                    </form>
                </div>

                {{-- Footer Info Text (Centered) --}}
                <p class="mt-12 text-center text-sm text-slate-400 leading-relaxed max-w-sm mx-auto">
                    Didn't receive the email? Check your spam folder or click above to send another.
                </p>
            </div>
        </div>
    </div>
</x-guest-layout>
]]>
</file>
<file name="resources\views\dashboard\admin.blade.php">
<![CDATA[
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
]]>
</file>
<file name="resources\views\admin\research-analytics\index.blade.php">
<![CDATA[
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-display text-xl sm:text-2xl lg:text-3xl font-extrabold tracking-tight text-slate-900">
                    Research Analytics
                </h2>
                <p class="hidden sm:block text-xs font-semibold uppercase tracking-wider text-muted-ink">
                    Administration
                </p>
            </div>

            <button type="button" onclick="window.print()" style="--lip: #CBD5E1;"
                class="btn-press print:hidden rounded-xl border-2 border-b-4 border-slate-300 bg-white px-4 py-2 font-display text-sm font-bold text-slate-700 shadow-2xs transition hover:bg-slate-50 focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/25">
                Print report
            </button>
        </div>
    </x-slot>

    <div class="py-6 font-sans text-slate-900 antialiased">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="relative overflow-hidden rounded-[1.5rem] border-2 border-b-4 border-primary/30 bg-gradient-to-br from-primary-tint via-[#FAF8FF] to-white p-6 sm:p-8 text-sm leading-relaxed text-slate-700 shadow-sm">
                <span class="inline-flex items-center rounded-full bg-white px-3 py-1 font-display text-xs font-bold text-primary shadow-sm ring-1 ring-primary/20">
                    Report summary
                </span>

                <p class="mt-3">
                    Generated
                    <strong>{{ $report['generated_at']->toDayDateTimeString() }}</strong>
                    from Form {{ $report['form_versions']['pre_test'] }} and
                    Form {{ $report['form_versions']['post_test'] }}.
                </p>
                <p class="mt-2">
                    <strong>Primary:</strong>
                    eligible paired students who answered all 60 post-test items.

                    <strong>Sensitivity:</strong>
                    every eligible finalized pair, including attempts with existing
                    unanswered response rows.

                    Unanswered post-test rows count as incorrect using the fixed
                    60-item denominator. Sessions with missing response rows,
                    mismatched questions, invalid form versions, or other integrity
                    failures are excluded.
                </p>
            </section>

            <section class="rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-5 sm:p-6 shadow-sm">
                <h3 class="font-display text-lg font-black text-slate-900">1. Sample funnel</h3>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-[34rem] text-sm">
                        <tbody class="divide-y divide-slate-100">
                            @foreach ([
                                'pre_candidates' => 'All completed pre-test candidates',
                                'post_candidates' => 'All finalized post-test candidates',
                                'pre_completed' => 'Eligible pre-test participants',
                                'post_finalized' => 'Eligible post-test participants',
                                'paired_finalized' => 'Eligible chronological pairs',
                                'paired_complete' => 'Primary complete-case pairs',
                                'pre_only' => 'Eligible pre-test only',
                                'post_only' => 'Eligible post-test only',
                                'excluded_pre_missing_version' => 'Excluded pre-tests: missing version',
                                'excluded_pre_wrong_version' => 'Excluded pre-tests: wrong version',
                                'excluded_post_missing_version' => 'Excluded post-tests: missing version',
                                'excluded_post_wrong_version' => 'Excluded post-tests: wrong version',
                                'excluded_pre_integrity' => 'Excluded pre-tests: integrity failure',
                                'excluded_post_integrity' => 'Excluded post-tests: integrity failure',
                                'excluded_duplicate_pre' => 'Excluded duplicate pre-tests',
                                'excluded_duplicate_post' => 'Excluded duplicate post-tests',
                                'excluded_invalid_chronology' => 'Excluded: pre-test not completed before post-test',
                            ] as $key => $label)
                                <tr>
                                    <td class="py-2.5 font-medium text-slate-600">{{ $label }}</td>
                                    <td class="py-2.5 text-right font-mono font-bold text-slate-900">
                                        {{ $report['funnel'][$key] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            @include('admin.research-analytics.partials.descriptives', [
                'title' => '2. Overall scores — primary complete-case analysis',
                'analysis' => $report['primary'],
            ])

            @include('admin.research-analytics.partials.wilcoxon', [
                'title' => '3. Overall Wilcoxon signed-rank — primary',
                'analysis' => $report['primary'],
            ])

            <section class="rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-5 sm:p-6 shadow-sm">
                <h3 class="font-display text-lg font-black text-slate-900">4. Per-domain primary analysis</h3>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-[64rem] text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-xs font-extrabold uppercase tracking-wider text-muted-ink">
                                <th class="py-2 pr-3">Domain</th>
                                <th class="py-2 text-right">N</th>
                                <th class="py-2 text-right">Pre mean</th>
                                <th class="py-2 text-right">Post mean</th>
                                <th class="py-2 text-right">Mean change</th>
                                <th class="py-2 text-right">W</th>
                                <th class="py-2 text-right">Raw p</th>
                                <th class="py-2 text-right">Bonferroni p</th>
                                <th class="py-2 text-right">Adjusted α=.05</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($report['primary']['per_domain'] as $row)
                                <tr>
                                    <td class="py-3 pr-3 font-display font-black text-slate-800">
                                        {{ $row['domain_number'] }}. {{ $row['domain_name'] }}
                                    </td>
                                    <td class="py-3 text-right">{{ $row['n_pairs'] }}</td>
                                    <td class="py-3 text-right">
                                        {{ $row['pre']['mean'] === null ? '—' : number_format($row['pre']['mean'], 2) }}
                                    </td>
                                    <td class="py-3 text-right">
                                        {{ $row['post']['mean'] === null ? '—' : number_format($row['post']['mean'], 2) }}
                                    </td>
                                    <td class="py-3 text-right">
                                        {{ $row['diff']['mean'] === null ? '—' : number_format($row['diff']['mean'], 2) }}
                                    </td>
                                    <td class="py-3 text-right">{{ number_format($row['wilcoxon']['w_statistic'], 2) }}</td>
                                    <td class="py-3 text-right">
                                        {{ $row['wilcoxon']['p_value'] < 0.000001 ? '<0.000001' : number_format($row['wilcoxon']['p_value'], 6) }}
                                    </td>
                                    <td class="py-3 text-right">
                                        {{ $row['bonferroni_p_value'] < 0.000001 ? '<0.000001' : number_format($row['bonferroni_p_value'], 6) }}
                                    </td>
                                    <td class="py-3 text-right">
                                        @if ($row['significant_at_05'])
                                            <span class="rounded-full bg-emerald-100 px-2.5 py-1 font-display text-xs font-bold text-emerald-700">Yes</span>
                                        @else
                                            <span class="rounded-full bg-slate-100 px-2.5 py-1 font-display text-xs font-bold text-slate-500">No</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="mt-3 text-xs font-medium text-muted-ink">
                    Bonferroni-adjusted p-values control the family-wise error rate across the reported domains.
                </p>
            </section>

            <section class="rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-5 sm:p-6 shadow-sm">
                <h3 class="font-display text-lg font-black text-slate-900">5. Readiness prediction accuracy</h3>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-[36rem] text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-xs font-extrabold uppercase tracking-wider text-muted-ink">
                                <th class="py-2">Population</th>
                                <th class="py-2 text-right">N</th>
                                <th class="py-2 text-right">MAE</th>
                                <th class="py-2 text-right">RMSE</th>
                                <th class="py-2 text-right">Mean error</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ([
                                'primary' => 'Complete cases',
                                'sensitivity' => 'All finalized pairs',
                            ] as $key => $label)
                                @php($row = $report['readiness'][$key])
                                <tr>
                                    <td class="py-3 font-display font-black text-slate-800">{{ $label }}</td>
                                    <td class="py-3 text-right">{{ $row['n'] }}</td>
                                    <td class="py-3 text-right">{{ $row['mae'] === null ? '—' : number_format($row['mae'], 4) }}</td>
                                    <td class="py-3 text-right">{{ $row['rmse'] === null ? '—' : number_format($row['rmse'], 4) }}</td>
                                    <td class="py-3 text-right">{{ $row['mean_error'] === null ? '—' : number_format($row['mean_error'], 4) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="mt-3 text-xs font-medium text-muted-ink">
                    Error = readiness snapshot at post-test start minus actual fixed-denominator post-test percentage.
                    Lower MAE and RMSE indicate better predictive accuracy.
                </p>
            </section>

            @include('admin.research-analytics.partials.descriptives', [
                'title' => '6. Overall scores — sensitivity analysis',
                'analysis' => $report['sensitivity'],
            ])

            @include('admin.research-analytics.partials.wilcoxon', [
                'title' => '7. Overall Wilcoxon signed-rank — sensitivity',
                'analysis' => $report['sensitivity'],
            ])

            <section class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-5 sm:p-6 shadow-sm">
                    <h3 class="font-display text-lg font-black text-slate-900">8. Submission breakdown</h3>
                    <table class="mt-4 w-full text-sm">
                        <tbody class="divide-y divide-slate-100">
                            @foreach ([
                                'manual_complete' => 'Manual — complete',
                                'manual_incomplete' => 'Manual — incomplete',
                                'expired_complete' => 'Expired — complete',
                                'expired_incomplete' => 'Expired — incomplete',
                                'unknown' => 'Missing/legacy reason',
                            ] as $key => $label)
                                <tr>
                                    <td class="py-2.5 font-medium text-slate-600">{{ $label }}</td>
                                    <td class="py-2.5 text-right font-mono font-bold">{{ $report['breakdown'][$key] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="rounded-3xl border-2 border-b-4 border-amber-300 bg-gradient-to-br from-amber-50/80 via-white to-white p-5 sm:p-6 shadow-sm">
                    <h3 class="font-display text-lg font-black text-amber-900">Interpretation guardrails</h3>
                    <ul class="mt-4 list-disc space-y-2 pl-5 text-sm font-medium text-slate-700">
                        <li>Wilcoxon N excludes zero pre/post differences.</li>
                        <li>Small untied samples use the exact distribution.</li>
                        <li>Tied samples use the documented normal approximation.</li>
                        <li>Final thesis values must be cross-checked in R or SPSS.</li>
                        <li>Placeholder-form results are for software verification only.</li>
                    </ul>
                </div>
            </section>

            <section class="rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-5 sm:p-6 shadow-sm">
                <h3 class="font-display text-lg font-black text-slate-900">9. Cohort summaries</h3>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-[58rem] text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-xs font-extrabold uppercase tracking-wider text-muted-ink">
                                <th class="py-2 pr-3">Cohort</th>
                                <th class="py-2 text-right">Enrolled</th>
                                <th class="py-2 text-right">Pre</th>
                                <th class="py-2 text-right">Post</th>
                                <th class="py-2 text-right">Paired</th>
                                <th class="py-2 text-right">Complete pairs</th>
                                <th class="py-2 text-right">Mean change</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($report['cohorts'] as $cohort)
                                <tr>
                                    <td class="py-3 pr-3 font-display font-black text-slate-800">
                                        {{ $cohort['cohort_name'] }}
                                        <span class="block text-xs font-normal text-muted-ink">{{ $cohort['academic_year'] }}</span>
                                    </td>
                                    <td class="py-3 text-right">{{ $cohort['enrolled_students'] }}</td>
                                    <td class="py-3 text-right">{{ $cohort['pre_completed'] }}</td>
                                    <td class="py-3 text-right">{{ $cohort['post_finalized'] }}</td>
                                    <td class="py-3 text-right">{{ $cohort['paired_finalized'] }}</td>
                                    <td class="py-3 text-right">{{ $cohort['paired_complete'] }}</td>
                                    <td class="py-3 text-right">
                                        {{ $cohort['mean_change_complete'] === null ? '—' : number_format($cohort['mean_change_complete'], 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-xs font-semibold text-muted-ink">
                                        No cohorts available.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>

]]>
</file>
<file name="resources\views\admin\research-analytics\partials\descriptives.blade.php">
<![CDATA[
<section class="rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-5 sm:p-6 shadow-sm">
    <h3 class="font-display text-lg font-black text-slate-900">{{ $title }}</h3>
    <div class="mt-4 overflow-x-auto">
        <table class="w-full min-w-[38rem] text-sm">
            <thead>
                <tr class="border-b border-slate-200 text-left text-xs font-extrabold uppercase tracking-wider text-muted-ink">
                    <th class="py-2">Statistic</th>
                    <th class="py-2 text-right">Pre-test</th>
                    <th class="py-2 text-right">Post-test</th>
                    <th class="py-2 text-right">Post − pre</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ([
                    'n' => 'N',
                    'mean' => 'Mean',
                    'median' => 'Median',
                    'sd' => 'Sample SD',
                    'min' => 'Minimum',
                    'max' => 'Maximum',
                ] as $key => $label)
                    <tr>
                        <td class="py-2.5 font-bold text-slate-700">{{ $label }}</td>
                        @foreach (['pre', 'post', 'diff'] as $group)
                            @php($value = $analysis[$group][$key])
                            <td class="py-2.5 text-right font-mono font-semibold text-slate-800">
                                {{ $value === null ? '—' : ($key === 'n' ? $value : number_format($value, 2)) }}
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

]]>
</file>
<file name="resources\views\admin\research-analytics\partials\wilcoxon.blade.php">
<![CDATA[
@php($wilcoxon = $analysis['wilcoxon'])

<section class="rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-5 sm:p-6 shadow-sm">
    <h3 class="font-display text-lg font-black text-slate-900">{{ $title }}</h3>

    <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
        @foreach ([
            'Paired students' => $analysis['n_pairs'],
            'Nonzero pairs' => $wilcoxon['n'],
            'Zero differences' => $wilcoxon['zero_differences'],
            'W' => number_format($wilcoxon['w_statistic'], 2),
            'z' => $wilcoxon['z'] === null ? '—' : number_format($wilcoxon['z'], 4),
            'Two-sided p' => $wilcoxon['p_value'] < 0.000001 ? '<0.000001' : number_format($wilcoxon['p_value'], 6),
        ] as $label => $value)
            <div class="rounded-2xl border-2 border-slate-100 bg-slate-50 p-3 text-center">
                <div class="text-[10px] font-extrabold uppercase tracking-wider text-muted-ink">{{ $label }}</div>
                <div class="mt-1 font-mono text-xl font-bold text-slate-900">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    <p class="mt-4 text-xs font-medium text-muted-ink">
        {{ $wilcoxon['method'] }}; two-sided test.
        W+ = {{ number_format($wilcoxon['w_plus'], 2) }},
        W− = {{ number_format($wilcoxon['w_minus'], 2) }}.
    </p>

    @if (str_starts_with($wilcoxon['method'], 'normal approximation') && $wilcoxon['n'] < 20)
        <p class="mt-2 rounded-2xl border-2 border-amber-200 bg-amber-50 p-3 text-xs font-semibold text-amber-800">
            This is a small tied sample using an approximation. Cross-check the final p-value with
            R or SPSS and report that validation in the methodology.
        </p>
    @endif
</section>

]]>
</file>
</files>