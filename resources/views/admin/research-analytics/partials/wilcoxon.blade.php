@php($wilcoxon = $analysis['wilcoxon'])

<section class="admin-panel">
    <h3 class="font-display text-lg font-extrabold text-ink">
        {{ $title }}
    </h3>

    <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
        @foreach ([
            'Paired students' => $analysis['n_pairs'],
            'Nonzero pairs' => $wilcoxon['n'],
            'Zero differences' => $wilcoxon['zero_differences'],
            'W' => number_format($wilcoxon['w_statistic'], 2),
            'z' => $wilcoxon['z'] === null
                ? '—'
                : number_format($wilcoxon['z'], 4),
            'Two-sided p' => $wilcoxon['p_value'] < 0.000001
                ? '<0.000001'
                : number_format($wilcoxon['p_value'], 6),
        ] as $label => $value)
            <div
                class="rounded-xl border border-line bg-surface-2
                    p-3 text-center"
            >
                <div
                    class="text-[10px] font-extrabold uppercase
                        tracking-wider text-ink-muted"
                >
                    {{ $label }}
                </div>

                <div class="rx-admin-stat-value mt-1 font-mono text-xl font-bold text-ink">
                    {{ $value }}
                </div>
            </div>
        @endforeach
    </div>

    <p class="mt-4 text-xs font-medium text-ink-muted">
        {{ $wilcoxon['method'] }}; two-sided test.
        W+ = {{ number_format($wilcoxon['w_plus'], 2) }},
        W− = {{ number_format($wilcoxon['w_minus'], 2) }}.
    </p>

    @if (
        str_starts_with(
            $wilcoxon['method'],
            'normal approximation',
        )
        && $wilcoxon['n'] < 20
    )
        <x-ui.alert
            variant="warning"
            class="mt-3 text-xs"
        >
            This is a small tied sample using an approximation.
            Cross-check the final p-value with R or SPSS and
            report that validation in the methodology.
        </x-ui.alert>
    @endif
</section>
