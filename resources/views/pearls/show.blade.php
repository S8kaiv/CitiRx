<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="$pearl->title" :description="$pearl->competency->competency_code .' · '.$pearl->competency->title .' · about '.$pearl->estimated_read_seconds . ' seconds'">
            <x-slot:actions>
                <x-ui.button variant="secondary" :href="route('pearls.index')">Back to Micro Pearls</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <div class="pt-6 pb-8">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">

            <section class="rounded-3xl border-2 border-b-4 border-primary/30 bg-primary-tint/50 p-6">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-white px-3 py-1 font-display text-xs font-bold text-primary">
                        Domain {{ $pearl->competency->domain->domain_number }}
                    </span>

                    @if ($pearl->is_ai_generated)
                        <span class="rounded-full bg-info-tint px-3 py-1 font-display text-xs font-bold text-info-ink">
                            AI-assisted and human-reviewed
                        </span>
                    @endif
                </div>

                <h2 class="mt-4 font-display text-sm font-extrabold uppercase tracking-wider text-primary">
                    Quick Recall
                </h2>

                <p class="mt-2 text-base font-semibold leading-7 text-slate-800">
                    {{ $pearl->mnemonic_summary }}
                </p>
            </section>

            @if (filled($pearl->learning_objectives))
                <section class="rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-6">
                    <h2 class="font-display text-lg font-extrabold text-slate-900">
                        Learning Objectives
                    </h2>

                    <ul class="mt-4 space-y-3">
                        @foreach ($pearl->learning_objectives as $objective)
                            <li class="flex gap-3 text-sm leading-6 text-slate-700">
                                <span class="font-bold text-primary">✓</span>
                                <span>{{ $objective }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <section class="rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-6">
                <h2 class="font-display text-lg font-extrabold text-slate-900">
                    Detailed Lesson
                </h2>

                <div class="mt-4 whitespace-pre-line text-sm leading-7 text-slate-700">{{ $pearl->lesson_content }}</div>
            </section>

            @if (filled($pearl->key_points))
                <section class="rounded-3xl border-2 border-b-4 border-success-strong/30 bg-success-tint p-6">
                    <h2 class="font-display text-lg font-extrabold text-success-ink">
                        Key Points
                    </h2>

                    <ul class="mt-4 space-y-3">
                        @foreach ($pearl->key_points as $point)
                            <li class="flex gap-3 text-sm leading-6 text-success-ink">
                                <span aria-hidden="true">💡</span>
                                <span>{{ $point }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if (filled($pearl->common_pitfalls))
                <section class="rounded-3xl border-2 border-b-4 border-warning-strong/30 bg-warning-tint p-6">
                    <h2 class="font-display text-lg font-extrabold text-warning-ink">
                        Common Pitfalls
                    </h2>

                    <ul class="mt-4 space-y-3">
                        @foreach ($pearl->common_pitfalls as $pitfall)
                            <li class="flex gap-3 text-sm leading-6 text-warning-ink">
                                <span aria-hidden="true">⚠</span>
                                <span>{{ $pitfall }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if (filled($pearl->recall_prompts))
                <section class="rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-6">
                    <h2 class="font-display text-lg font-extrabold text-slate-900">
                        Active Recall
                    </h2>

                    <div class="mt-4 space-y-3">
                        @foreach ($pearl->recall_prompts as $prompt)
                            @php
                                $question = is_array($prompt)
                                    ? ($prompt['question'] ?? '')
                                    : (string) $prompt;

                                $answer = is_array($prompt)
                                    ? ($prompt['answer'] ?? '')
                                    : '';
                            @endphp

                            @if ($question !== '')
                                <details class="rounded-2xl border-2 border-slate-200 bg-slate-50 p-4">
                                    <summary class="cursor-pointer font-display text-sm font-bold text-slate-800">
                                        {{ $question }}
                                    </summary>

                                    @if ($answer !== '')
                                        <p class="mt-3 text-sm leading-6 text-slate-700">
                                            {{ $answer }}
                                        </p>
                                    @endif
                                </details>
                            @endif
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-6">
                <h2 class="font-display text-lg font-extrabold text-slate-900">
                    References
                </h2>

                <ul class="mt-4 space-y-2 text-sm leading-6 text-slate-600">
                    @foreach ($pearl->source_references ?? [] as $reference)
                        <li>
                            {{ $reference }}
                        </li>
                    @endforeach
                </ul>

                <p class="mt-4 text-xs text-ink-muted">
                    Content version {{ $pearl->content_version }}.
                    Reviewed {{ $pearl->reviewed_at?->format('F j, Y') }}.
                </p>
            </section>

            <div class="flex justify-center">
                <a
                    href="{{ route('pearls.index') }}"
                    class="rounded-2xl border-2 border-b-4 border-slate-200 bg-white px-6 py-3 font-display text-sm font-bold text-slate-700 transition hover:bg-slate-50"
                >
                    Back to Micro Pearls
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
