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
