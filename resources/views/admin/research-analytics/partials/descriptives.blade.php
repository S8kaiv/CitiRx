<section class="admin-panel">
    <h3 class="font-display text-lg font-extrabold text-ink">
        {{ $title }}
    </h3>

    <div class="mt-4 overflow-x-auto">
        <table class="w-full min-w-[38rem] text-sm">
            <thead>
                <tr
                    class="border-b border-line text-left text-xs
                        font-extrabold uppercase tracking-wider
                        text-ink-muted"
                >
                    <th class="py-2">Statistic</th>
                    <th class="py-2 text-right">Pre-test</th>
                    <th class="py-2 text-right">Post-test</th>
                    <th class="py-2 text-right">Post − pre</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-line">
                @foreach ([
                    'n' => 'N',
                    'mean' => 'Mean',
                    'median' => 'Median',
                    'sd' => 'Sample SD',
                    'min' => 'Minimum',
                    'max' => 'Maximum',
                ] as $key => $label)
                    <tr>
                        <td class="py-2.5 font-bold text-ink">
                            {{ $label }}
                        </td>

                        @foreach (['pre', 'post', 'diff'] as $group)
                            @php($value = $analysis[$group][$key])

                            <td
                                class="py-2.5 text-right font-mono
                                    font-semibold text-ink"
                            >
                                {{ $value === null
                                    ? '—'
                                    : (
                                        $key === 'n'
                                            ? $value
                                            : number_format($value, 2)
                                    ) }}
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
