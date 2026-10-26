<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="font-display text-xs font-bold uppercase tracking-[0.18em] text-primary">
                    Administration
                </p>
                <h2 class="font-display text-xl font-extrabold text-slate-900">
                    Research Analytics
                </h2>
            </div>

            <button type="button" onclick="window.print()"
                class="print:hidden rounded-xl border-2 border-slate-200 bg-white px-4 py-2 font-display text-sm font-bold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/25">
                Print report
            </button>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="rounded-2xl border-2 border-primary/15 bg-primary-tint/40 p-5 text-sm text-slate-700">
                <p>
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

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="font-display text-lg font-extrabold text-slate-900">
                    Official participant scope
                </h3>

                <p class="mt-1 text-sm text-slate-600">
                    Aggregate counts only. Participant identities are never
                    displayed in the research report.
                </p>

                <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($report['eligibility']['participant_status_counts'] as $status => $total)
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <p class="text-xs font-black uppercase tracking-wider text-slate-500">
                                {{ \App\Models\ResearchParticipant::label($status) }}
                            </p>

                            <p class="mt-1 text-2xl font-black text-slate-900">
                                {{ $total }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="font-display text-lg font-extrabold text-slate-900">1. Sample funnel</h3>
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

                                'excluded_pre_participant_scope' => 'Excluded pre-tests: participant scope',

                                'excluded_post_participant_scope' => 'Excluded post-tests: participant scope',
                            ] as $key => $label)
                                <tr>
                                    <td class="py-2.5 text-slate-600">
                                        {{ $label }}
                                    </td>

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

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="font-display text-lg font-extrabold text-slate-900">
                    4. Per-domain primary analysis
                </h3>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-[64rem] text-sm">
                        <thead>
                            <tr
                                class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
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
                                    <td class="py-3 pr-3 font-semibold text-slate-800">
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
                                    <td class="py-3 text-right">{{ number_format($row['wilcoxon']['w_statistic'], 2) }}
                                    </td>
                                    <td class="py-3 text-right">
                                        {{ $row['wilcoxon']['p_value'] < 0.000001 ? '<0.000001' : number_format($row['wilcoxon']['p_value'], 6) }}
                                    </td>
                                    <td class="py-3 text-right">
                                        {{ $row['bonferroni_p_value'] < 0.000001 ? '<0.000001' : number_format($row['bonferroni_p_value'], 6) }}
                                    </td>
                                    <td class="py-3 text-right">
                                        @if ($row['significant_at_05'])
                                            <span class="font-bold text-emerald-700">Yes</span>
                                        @else
                                            <span class="text-slate-500">No</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="mt-3 text-xs text-slate-500">
                    Bonferroni-adjusted p-values control the family-wise error rate across the reported domains.
                </p>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="font-display text-lg font-extrabold text-slate-900">
                    5. Readiness prediction accuracy
                </h3>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-[36rem] text-sm">
                        <thead>
                            <tr
                                class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
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
                                    <td class="py-3 font-semibold text-slate-800">{{ $label }}</td>
                                    <td class="py-3 text-right">{{ $row['n'] }}</td>
                                    <td class="py-3 text-right">
                                        {{ $row['mae'] === null ? '—' : number_format($row['mae'], 4) }}</td>
                                    <td class="py-3 text-right">
                                        {{ $row['rmse'] === null ? '—' : number_format($row['rmse'], 4) }}</td>
                                    <td class="py-3 text-right">
                                        {{ $row['mean_error'] === null ? '—' : number_format($row['mean_error'], 4) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="mt-3 text-xs text-slate-500">
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
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="font-display text-lg font-extrabold text-slate-900">
                        8. Submission breakdown
                    </h3>
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
                                    <td class="py-2.5 text-slate-600">{{ $label }}</td>
                                    <td class="py-2.5 text-right font-mono font-bold">{{ $report['breakdown'][$key] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="font-display text-lg font-extrabold text-slate-900">
                        Interpretation guardrails
                    </h3>
                    <ul class="mt-4 list-disc space-y-2 pl-5 text-sm text-slate-600">
                        <li>Wilcoxon N excludes zero pre/post differences.</li>
                        <li>Small untied samples use the exact distribution.</li>
                        <li>Tied samples use the documented normal approximation.</li>
                        <li>Final thesis values must be cross-checked in R or SPSS.</li>
                        <li>Placeholder-form results are for software verification only.</li>
                    </ul>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="font-display text-lg font-extrabold text-slate-900">
                    9. Cohort summaries
                </h3>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-[58rem] text-sm">
                        <thead>
                            <tr
                                class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
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
                                    <td class="py-3 pr-3 font-semibold text-slate-800">
                                        {{ $cohort['cohort_name'] }}
                                        <span
                                            class="block text-xs font-normal text-slate-500">{{ $cohort['academic_year'] }}</span>
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
                                    <td colspan="7" class="py-6 text-center text-slate-500">No cohorts available.
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
