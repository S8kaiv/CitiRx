@props([
    'recommendation',
])

@php
    $hasData = (bool) ($recommendation['has_data'] ?? false);
    $requiresDiagnostic = (bool) ($recommendation['requires_diagnostic'] ?? false);
    $action = $recommendation['recommended_action'] ?? null;
@endphp

<section
    aria-labelledby="study-plan-title"
    class="overflow-hidden rounded-3xl border-2 border-b-4 border-slate-200 bg-white shadow-sm"
>
    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
        <div>
            <h2
                id="study-plan-title"
                class="font-display text-lg font-black text-slate-900"
            >
                Personalized Study Plan
            </h2>

            <p class="mt-0.5 text-xs font-medium text-muted-ink">
                Generated from your BKT mastery and weighted readiness estimates
            </p>
        </div>

        <span
            class="rounded-full bg-primary-tint px-3 py-1 font-display text-[10px] font-bold uppercase tracking-wider text-primary"
        >
            Rule-based · {{ $recommendation['engine_version'] }}
        </span>
    </div>

    @if (! $hasData)
        <div class="p-6 text-center">
            <div
                aria-hidden="true"
                class="text-3xl"
            >
                🎯
            </div>

            <p class="mx-auto mt-3 max-w-lg text-sm leading-relaxed text-muted-ink">
                {{ $recommendation['reason'] }}
            </p>

            @if ($requiresDiagnostic)
                <a
                    href="{{ route('diagnostic.intro') }}"
                    style="--lip: rgb(var(--color-primary-lip));"
                    class="btn-press mt-5 inline-flex rounded-xl bg-primary px-5 py-2.5 font-display text-sm font-bold text-white"
                >
                    Take Diagnostic
                </a>
            @endif
        </div>
    @else
        <div class="space-y-6 p-5 sm:p-6">
            <div>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="font-display text-[10px] font-bold uppercase tracking-wider text-muted-ink">
                            Priority Domain
                        </p>

                        <h3 class="mt-1 font-display text-lg font-black text-slate-900">
                            {{ $recommendation['priority_domain']['domain_name'] }}
                        </h3>
                    </div>

                    <span
                        class="rounded-full border border-primary/25 bg-primary-tint px-3 py-1 font-display text-sm font-black text-primary"
                    >
                        {{ number_format($recommendation['priority_domain']['mastery_pct'], 1) }}%
                    </span>
                </div>

                <p class="mt-2 text-sm leading-relaxed text-muted-ink">
                    {{ $recommendation['reason'] }}
                </p>
            </div>

            @if ($recommendation['priority_competencies'] !== [])
                <div>
                    <p class="font-display text-[10px] font-bold uppercase tracking-wider text-muted-ink">
                        Priority Competencies
                    </p>

                    <div class="mt-2 divide-y divide-slate-100 rounded-2xl border border-slate-200">
                        @foreach ($recommendation['priority_competencies'] as $competency)
                            <div class="flex items-center justify-between gap-4 px-4 py-3">
                                <div class="min-w-0">
                                    <span class="font-mono text-[10px] font-bold text-primary">
                                        {{ $competency['code'] }}
                                    </span>

                                    <p
                                        class="truncate text-sm font-semibold text-slate-800"
                                        title="{{ $competency['title'] }}"
                                    >
                                        {{ $competency['title'] }}
                                    </p>
                                </div>

                                <span class="shrink-0 font-display text-sm font-black text-slate-900">
                                    {{ number_format($competency['mastery_pct'], 1) }}%
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($action)
                <div class="rounded-2xl border border-primary/25 bg-primary-tint/50 p-4">
                    <p class="font-display text-[10px] font-bold uppercase tracking-wider text-primary">
                        Recommended Action
                    </p>

                    <p class="mt-1 text-sm font-semibold leading-relaxed text-slate-800">
                        {{ $action['message'] }}
                    </p>

                    <a
                        href="{{ route('practice.intro', [
                            'domain_filter_id' => $action['domain_id'],
                            'length' => $action['suggested_length'],
                        ]) }}"
                        style="--lip: rgb(var(--color-primary-lip));"
                        class="btn-press mt-4 inline-flex rounded-xl bg-primary px-5 py-2.5 font-display text-sm font-bold text-white"
                    >
                        Set Up Targeted Practice
                    </a>
                </div>
            @endif

            @if ($recommendation['recommended_pearls'] !== [])
                <div>
                    <p class="font-display text-[10px] font-bold uppercase tracking-wider text-muted-ink">
                        Suggested Micro Pearls
                    </p>

                    <div class="mt-2 grid gap-3 sm:grid-cols-2">
                        @foreach ($recommendation['recommended_pearls'] as $pearl)
                            <a
                                href="{{ route('pearls.show', [
                                    'pearl' => $pearl['slug'],
                                ]) }}"
                                class="rounded-2xl border-2 border-b-4 border-slate-200 bg-white p-4 transition hover:border-primary/30 hover:bg-primary-tint/20"
                            >
                                <p class="text-[10px] font-bold uppercase tracking-wider text-clinical-ink">
                                    {{ $pearl['competency_title'] }}
                                </p>

                                <h3 class="mt-1 font-display text-sm font-black text-slate-900">
                                    {{ $pearl['title'] }}
                                </h3>

                                <p class="mt-2 text-xs font-medium text-muted-ink">
                                    About {{ max(1, (int) ceil($pearl['estimated_read_seconds'] / 60)) }}
                                    minute(s)
                                </p>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <p class="border-t border-slate-100 pt-3 text-[11px] leading-relaxed text-muted-ink">
                Recommendations guide review activities only. They do not change assessment scores,
                mastery calculations, XP, or official research results.
            </p>
        </div>
    @endif
</section>
