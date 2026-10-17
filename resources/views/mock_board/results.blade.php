<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="font-display text-xl font-black text-slate-900">
                Post-Test Results
            </h1>

            <p class="font-display text-[11px] font-bold uppercase tracking-wider text-muted-ink">
                Research Post-Test · Form B
            </p>
        </div>
    </x-slot>

    @php
        $percentage = max(
            0,
            min(100, (float) $result['percentage']),
        );

        $expired =
            $result['submission_reason'] === 'expired';
    @endphp

    <div class="py-8 sm:py-12">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('status'))
                <div
                    role="status"
                    class="rounded-2xl border-2 border-b-4 border-clinical/30 bg-clinical-tint p-4 text-sm font-bold text-clinical-ink"
                >
                    {{ session('status') }}
                </div>
            @endif

            <section class="overflow-hidden rounded-3xl border-2 border-b-4 border-slate-200 bg-white shadow-xs">
                <div class="grid lg:grid-cols-[0.7fr_1.3fr]">
                    <div class="flex flex-col items-center justify-center bg-primary-tint p-8 text-center sm:p-10">
                        <p class="font-display text-xs font-black uppercase tracking-widest text-primary">
                            Final score
                        </p>

                        <div class="mt-4 flex h-44 w-44 flex-col items-center justify-center rounded-full border-[12px] border-primary bg-white shadow-sm">
                            <p class="font-display text-4xl font-black text-slate-900">
                                {{ $result['correct'] }}
                            </p>

                            <p class="font-display text-sm font-black text-muted-ink">
                                out of {{ $result['total'] }}
                            </p>
                        </div>

                        <p class="mt-5 font-display text-3xl font-black text-primary">
                            {{ number_format($percentage, 2) }}%
                        </p>
                    </div>

                    <div class="space-y-6 p-6 sm:p-8 lg:p-10">
                        <div>
                            <span class="inline-flex rounded-xl border border-b-2 border-strong/30 bg-strong-tint px-3 py-1 font-display text-xs font-black text-strong-ink">
                                Assessment Completed
                            </span>

                            <h2 class="mt-4 font-display text-3xl font-black leading-tight text-slate-900">
                                Your responses have been recorded
                            </h2>

                            <p class="mt-3 text-sm font-medium leading-relaxed text-slate-600">
                                This page provides aggregate research results only.
                                Correct choices, rationales, and distractor feedback are
                                intentionally not displayed.
                            </p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3">
                            <div class="rounded-2xl border-2 border-b-4 border-slate-200 bg-slate-50 p-4">
                                <p class="font-display text-2xl font-black text-slate-900">
                                    {{ $result['answered'] }}
                                </p>

                                <p class="font-display text-[10px] font-black uppercase tracking-wider text-muted-ink">
                                    Answered
                                </p>
                            </div>

                            <div class="rounded-2xl border-2 border-b-4 border-slate-200 bg-slate-50 p-4">
                                <p class="font-display text-2xl font-black text-slate-900">
                                    {{ $result['unanswered'] }}
                                </p>

                                <p class="font-display text-[10px] font-black uppercase tracking-wider text-muted-ink">
                                    Unanswered
                                </p>
                            </div>

                            <div class="rounded-2xl border-2 border-b-4 border-slate-200 bg-slate-50 p-4">
                                <p class="font-display text-lg font-black {{ $expired ? 'text-gold-ink' : 'text-clinical-ink' }}">
                                    {{ $expired ? 'Time Expired' : 'Manual' }}
                                </p>

                                <p class="font-display text-[10px] font-black uppercase tracking-wider text-muted-ink">
                                    Submission
                                </p>
                            </div>
                        </div>

                        @if ($session->completed_at)
                            <p class="font-mono text-[11px] text-muted-ink">
                                Completed:
                                {{ $session->completed_at->format('F j, Y · g:i A') }}
                            </p>
                        @endif
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-6 shadow-xs sm:p-8">
                <div>
                    <h2 class="font-display text-xl font-black text-slate-900">
                        Subject performance
                    </h2>

                    <p class="mt-1 text-sm font-medium text-muted-ink">
                        Aggregate results across the six pharmacy subject areas.
                    </p>
                </div>

                <div class="mt-6 overflow-x-auto">
                    <table class="min-w-full border-separate border-spacing-y-2">
                        <thead>
                            <tr class="text-left">
                                <th class="px-4 py-2 font-display text-[10px] font-black uppercase tracking-wider text-muted-ink">
                                    Subject
                                </th>

                                <th class="px-4 py-2 text-center font-display text-[10px] font-black uppercase tracking-wider text-muted-ink">
                                    Answered
                                </th>

                                <th class="px-4 py-2 text-center font-display text-[10px] font-black uppercase tracking-wider text-muted-ink">
                                    Correct
                                </th>

                                <th class="px-4 py-2 text-center font-display text-[10px] font-black uppercase tracking-wider text-muted-ink">
                                    Score
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($result['per_domain'] as $domain)
                                @php
                                    $domainPercentage = max(
                                        0,
                                        min(
                                            100,
                                            (float) $domain['percentage'],
                                        ),
                                    );
                                @endphp

                                <tr class="bg-slate-50">
                                    <td class="rounded-l-2xl border-y-2 border-l-2 border-slate-200 px-4 py-4">
                                        <p class="font-display text-[10px] font-black uppercase tracking-wider text-primary">
                                            Subject {{ $domain['domain_number'] }}
                                        </p>

                                        <p class="mt-1 font-display text-sm font-black text-slate-900">
                                            {{ $domain['domain_name'] }}
                                        </p>
                                    </td>

                                    <td class="border-y-2 border-slate-200 px-4 py-4 text-center">
                                        <p class="font-display text-sm font-black text-slate-800">
                                            {{ $domain['answered'] }}/{{ $domain['total'] }}
                                        </p>

                                        @if ($domain['unanswered'] > 0)
                                            <p class="mt-1 text-[10px] font-bold text-gold-ink">
                                                {{ $domain['unanswered'] }}
                                                unanswered
                                            </p>
                                        @endif
                                    </td>

                                    <td class="border-y-2 border-slate-200 px-4 py-4 text-center">
                                        <p class="font-display text-sm font-black text-slate-800">
                                            {{ $domain['correct'] }}/{{ $domain['total'] }}
                                        </p>
                                    </td>

                                    <td class="rounded-r-2xl border-y-2 border-r-2 border-slate-200 px-4 py-4">
                                        <div class="min-w-28">
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="font-display text-xs font-black text-primary">
                                                    {{ number_format($domainPercentage, 2) }}%
                                                </span>
                                            </div>

                                            <div
                                                class="mt-2 h-2 overflow-hidden rounded-full bg-slate-200"
                                                role="progressbar"
                                                aria-label="{{ $domain['domain_name'] }} score"
                                                aria-valuemin="0"
                                                aria-valuemax="100"
                                                aria-valuenow="{{ round($domainPercentage) }}"
                                            >
                                                <div
                                                    class="h-full rounded-full bg-primary"
                                                    style="width: {{ $domainPercentage }}%"
                                                ></div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rounded-3xl border-2 border-b-4 border-clinical/30 bg-clinical-tint p-5">
                <h2 class="font-display text-sm font-black text-clinical-ink">
                    Research assessment notice
                </h2>

                <p class="mt-2 text-sm font-medium leading-relaxed text-clinical-ink">
                    This post-test does not award XP, unlock badges, extend a streak,
                    add questions to the Rx Vault, or update your BKT mastery and
                    readiness estimates.
                </p>
            </section>

            <div class="flex justify-center">
                <a
                    href="{{ route('dashboard') }}"
                    style="--lip: #4A2FC4;"
                    class="btn-press inline-flex items-center justify-center rounded-2xl bg-primary px-8 py-3.5 font-display text-sm font-black text-white transition hover:bg-primary/95 focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/25"
                >
                    Return to Dashboard
                </a>
            </div>

            <p class="text-center font-mono text-[10px] text-muted-ink">
                Session: {{ $session->session_id }}
            </p>

        </div>
    </div>
</x-app-layout>
