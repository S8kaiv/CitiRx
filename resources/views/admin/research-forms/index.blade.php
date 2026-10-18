<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="font-display text-xs font-bold uppercase tracking-[0.18em] text-primary">
                    Research Administration
                </p>

                <h2 class="font-display text-xl font-extrabold text-slate-900">
                    Research Form Readiness
                </h2>
            </div>

            <a
                href="{{ route('dashboard') }}"
                class="rounded-xl border-2 border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/25"
            >
                Back to Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="rounded-3xl border-2 border-primary/20 bg-primary-tint/40 p-5">
                <h3 class="font-display text-base font-extrabold text-slate-900">
                    Shared readiness audit
                </h3>

                <p class="mt-2 text-sm leading-relaxed text-slate-600">
                    This page and the
                    <code class="rounded bg-white px-1.5 py-0.5 text-xs">
                        research:audit-forms
                    </code>
                    command use the same audit service.
                    “Ready for pilot” currently confirms the legacy content
                    gate only. Proper question-version binding and official
                    pilot controls must still be completed before data
                    collection.
                </p>
            </section>

            @if ($developmentOverrideConfigured)
                <div
                    role="alert"
                    class="rounded-3xl border-2 border-amber-300 bg-amber-50 p-5 text-amber-900"
                >
                    <p class="font-display font-extrabold">
                        Development override is enabled
                    </p>

                    <p class="mt-1 text-sm">
                        <code>CITIRX_ALLOW_UNVALIDATED_RESEARCH_FORMS</code>
                        is enabled. This must be false before staging,
                        deployment, or official pilot collection.
                    </p>
                </div>
            @endif

            <div class="grid gap-6 xl:grid-cols-2">
                @foreach ($reports as $report)
                    @php
                        $statusClasses = match ($report['status']['key']) {
                            'development' => 'border-amber-200 bg-amber-50 text-amber-800',
                            'needs_review' => 'border-red-200 bg-red-50 text-red-800',
                            'structurally_valid' => 'border-blue-200 bg-blue-50 text-blue-800',
                            'validated' => 'border-violet-200 bg-violet-50 text-violet-800',
                            'locked' => 'border-slate-300 bg-slate-100 text-slate-800',
                            'ready_for_pilot' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
                            default => 'border-slate-200 bg-slate-50 text-slate-700',
                        };
                    @endphp

                    <article class="overflow-hidden rounded-3xl border-2 border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 p-6">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="font-mono text-xs text-slate-500">
                                        {{ $report['form'] }}
                                    </p>

                                    <h3 class="mt-1 font-display text-xl font-extrabold text-slate-900">
                                        {{ $report['label'] }}
                                    </h3>

                                    <p class="mt-2 text-sm text-slate-600">
                                        Expected version:
                                        <strong class="text-slate-900">
                                            {{ $report['expected_version'] }}
                                        </strong>
                                    </p>

                                    <p class="mt-1 text-xs text-amber-700">
                                        {{ $report['version_binding'] }}
                                    </p>
                                </div>

                                <span class="rounded-full border px-3 py-1 text-xs font-extrabold {{ $statusClasses }}">
                                    {{ $report['status']['label'] }}
                                </span>
                            </div>

                            <dl class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
                                <div class="rounded-2xl bg-slate-50 p-3">
                                    <dt class="text-[10px] font-bold uppercase tracking-wide text-slate-500">
                                        Active
                                    </dt>

                                    <dd class="mt-1 text-xl font-extrabold text-slate-900">
                                        {{ $report['active_questions'] }}/60
                                    </dd>
                                </div>

                                <div class="rounded-2xl bg-slate-50 p-3">
                                    <dt class="text-[10px] font-bold uppercase tracking-wide text-slate-500">
                                        Validated
                                    </dt>

                                    <dd class="mt-1 text-xl font-extrabold text-slate-900">
                                        {{ $report['fully_validated'] }}/60
                                    </dd>
                                </div>

                                <div class="rounded-2xl bg-slate-50 p-3">
                                    <dt class="text-[10px] font-bold uppercase tracking-wide text-slate-500">
                                        Placeholders
                                    </dt>

                                    <dd class="mt-1 text-xl font-extrabold {{ $report['placeholders'] > 0 ? 'text-amber-700' : 'text-emerald-700' }}">
                                        {{ $report['placeholders'] }}
                                    </dd>
                                </div>

                                <div class="rounded-2xl bg-slate-50 p-3">
                                    <dt class="text-[10px] font-bold uppercase tracking-wide text-slate-500">
                                        Served
                                    </dt>

                                    <dd class="mt-1 text-xl font-extrabold text-slate-900">
                                        {{ $report['served_questions'] }}
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <div class="space-y-6 p-6">
                            <section>
                                <h4 class="font-display text-sm font-extrabold text-slate-900">
                                    Integrity checks
                                </h4>

                                <dl class="mt-3 grid gap-2 text-sm sm:grid-cols-2">
                                    @php
                                        $checks = [
                                            'Positions 1–60' => $report['positions_complete'],
                                            'Six domains × 10' => $report['domains_matching'] === 6,
                                            'Competency blueprint' => $report['competencies_matching'] === count($report['competency_rows']),
                                            'Choice structures' => $report['invalid_choice_structures'] === 0,
                                            'Stems and choices' => $report['missing_stems_or_choices'] === 0,
                                            'Rationales' => $report['missing_rationales'] === 0,
                                            'Cognitive levels' => $report['invalid_cognitive_levels'] === 0,
                                            'Duplicate stems' => $report['duplicate_stem_groups'] === 0,
                                            'Validation metadata' => $report['partial_validation'] === 0,
                                        ];
                                    @endphp

                                    @foreach ($checks as $label => $passes)
                                        <div class="flex items-center justify-between gap-4 rounded-xl bg-slate-50 px-3 py-2">
                                            <dt class="text-slate-600">
                                                {{ $label }}
                                            </dt>

                                            <dd class="font-bold {{ $passes ? 'text-emerald-700' : 'text-red-700' }}">
                                                {{ $passes ? 'Pass' : 'Fail' }}
                                            </dd>
                                        </div>
                                    @endforeach
                                </dl>
                            </section>

                            <section>
                                <h4 class="font-display text-sm font-extrabold text-slate-900">
                                    First-served status
                                </h4>

                                <p class="mt-2 text-sm text-slate-600">
                                    @if ($report['first_served_at'])
                                        First served:
                                        <strong class="text-slate-900">
                                            {{ \Illuminate\Support\Carbon::parse(
                                                $report['first_served_at'],
                                            )->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
                                        </strong>
                                    @else
                                        This form has not been served.
                                    @endif
                                </p>

                                <p class="mt-1 text-xs text-slate-500">
                                    Locked questions:
                                    {{ $report['locked_questions'] }}
                                </p>
                            </section>

                            <section>
                                <h4 class="font-display text-sm font-extrabold text-slate-900">
                                    Domain allocation
                                </h4>

                                <div class="mt-3 overflow-x-auto">
                                    <table class="min-w-full text-left text-sm">
                                        <thead class="text-xs uppercase text-slate-500">
                                            <tr>
                                                <th class="px-3 py-2">Domain</th>
                                                <th class="px-3 py-2 text-right">Expected</th>
                                                <th class="px-3 py-2 text-right">Actual</th>
                                                <th class="px-3 py-2 text-right">Status</th>
                                            </tr>
                                        </thead>

                                        <tbody class="divide-y divide-slate-100">
                                            @foreach ($report['domain_rows'] as $domain)
                                                <tr>
                                                    <td class="px-3 py-2 font-semibold text-slate-800">
                                                        {{ $domain['domain_number'] }}.
                                                        {{ $domain['domain_name'] }}
                                                    </td>

                                                    <td class="px-3 py-2 text-right">
                                                        {{ $domain['expected'] }}
                                                    </td>

                                                    <td class="px-3 py-2 text-right">
                                                        {{ $domain['actual'] }}
                                                    </td>

                                                    <td class="px-3 py-2 text-right font-bold {{ $domain['matches'] ? 'text-emerald-700' : 'text-red-700' }}">
                                                        {{ $domain['matches'] ? 'Match' : 'Mismatch' }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </section>

                            <section>
                                <h4 class="font-display text-sm font-extrabold text-slate-900">
                                    Competency blueprint
                                </h4>

                                <div class="mt-3 overflow-x-auto">
                                    <table class="min-w-full text-left text-sm">
                                        <thead class="text-xs uppercase text-slate-500">
                                            <tr>
                                                <th class="px-3 py-2">Domain</th>
                                                <th class="px-3 py-2">Code</th>
                                                <th class="px-3 py-2 text-right">Expected</th>
                                                <th class="px-3 py-2 text-right">Actual</th>
                                                <th class="px-3 py-2 text-right">Status</th>
                                            </tr>
                                        </thead>

                                        <tbody class="divide-y divide-slate-100">
                                            @foreach ($report['competency_rows'] as $competency)
                                                <tr>
                                                    <td class="px-3 py-2">
                                                        {{ $competency['domain_number'] }}
                                                    </td>

                                                    <td class="px-3 py-2 font-mono font-bold text-slate-800">
                                                        {{ $competency['competency_code'] }}
                                                    </td>

                                                    <td class="px-3 py-2 text-right">
                                                        {{ $competency['expected'] }}
                                                    </td>

                                                    <td class="px-3 py-2 text-right">
                                                        {{ $competency['actual'] }}
                                                    </td>

                                                    <td class="px-3 py-2 text-right font-bold {{ $competency['matches'] ? 'text-emerald-700' : 'text-red-700' }}">
                                                        {{ $competency['matches'] ? 'Match' : 'Mismatch' }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </section>

                            <section>
                                <h4 class="font-display text-sm font-extrabold text-slate-900">
                                    Audit findings
                                </h4>

                                @if ($report['errors'] === [])
                                    <div class="mt-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">
                                        No content or validation errors were found.
                                    </div>
                                @else
                                    <ul class="mt-3 space-y-2">
                                        @foreach ($report['errors'] as $error)
                                            <li class="rounded-xl border border-red-100 bg-red-50 px-3 py-2 text-sm text-red-800">
                                                {{ $error }}
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </section>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
