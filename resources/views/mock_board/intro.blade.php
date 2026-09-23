<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="font-display text-xl font-black text-slate-900">
                Research Post-Test
            </h1>

            <p class="font-display text-[11px] font-bold uppercase tracking-wider text-muted-ink">
                Mock Board Examination · Form B
            </p>
        </div>
    </x-slot>

    @php
        $canStart = (bool) $mockBoardStatus['can_start'];
        $reason = $mockBoardStatus['reason'];
        $availability = $mockBoardStatus['availability'];
        $inProgress = $mockBoardStatus['in_progress'];
        $completed = $mockBoardStatus['completed'];
        $durationMinutes = $mockBoardStatus['duration_minutes'];
        $formVersion = $mockBoardStatus['form_version'];

        $formIsReady = $availability->every(
            fn (array $subject): bool =>
                $subject['available'] === $subject['required']
        );
    @endphp

    <div class="py-8 sm:py-12">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('error'))
                <div
                    role="alert"
                    class="rounded-2xl border-2 border-b-4 border-weak/40 bg-weak-tint p-4 text-sm font-bold text-weak-ink"
                >
                    {{ session('error') }}
                </div>
            @endif

            @if (session('status'))
                <div
                    role="status"
                    class="rounded-2xl border-2 border-b-4 border-clinical/30 bg-clinical-tint p-4 text-sm font-bold text-clinical-ink"
                >
                    {{ session('status') }}
                </div>
            @endif

            <section class="overflow-hidden rounded-3xl border-2 border-b-4 border-slate-200 bg-white shadow-xs">
                <div class="grid lg:grid-cols-[1.35fr_0.65fr]">

                    <div class="space-y-6 p-6 sm:p-8 lg:p-10">
                        <div class="space-y-3">
                            <div class="flex flex-wrap gap-2">
                                <span class="rounded-xl border border-b-2 border-primary/30 bg-primary-tint px-3 py-1 font-display text-xs font-black text-primary">
                                    Research Post-Test
                                </span>

                                <span class="rounded-xl border border-b-2 border-clinical/30 bg-clinical-tint px-3 py-1 font-display text-xs font-black text-clinical-ink">
                                    Form B
                                </span>
                            </div>

                            <h2 class="font-display text-3xl font-black leading-tight text-slate-900 sm:text-4xl">
                                Pharmacy Mock Board Assessment
                            </h2>

                            <p class="max-w-2xl text-sm font-medium leading-relaxed text-muted-ink sm:text-base">
                                This assessment measures your knowledge after using CitiRx.
                                Your responses are saved automatically, and no correctness
                                feedback is shown while the assessment is active.
                            </p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3">
                            <div class="rounded-2xl border-2 border-b-4 border-slate-200 bg-slate-50 p-4">
                                <p class="font-display text-2xl font-black text-primary">
                                    60
                                </p>

                                <p class="font-display text-xs font-bold uppercase tracking-wider text-muted-ink">
                                    Questions
                                </p>
                            </div>

                            <div class="rounded-2xl border-2 border-b-4 border-slate-200 bg-slate-50 p-4">
                                <p class="font-display text-2xl font-black text-primary">
                                    {{ $durationMinutes }}
                                </p>

                                <p class="font-display text-xs font-bold uppercase tracking-wider text-muted-ink">
                                    Minutes
                                </p>
                            </div>

                            <div class="rounded-2xl border-2 border-b-4 border-slate-200 bg-slate-50 p-4">
                                <p class="font-display text-2xl font-black text-primary">
                                    6
                                </p>

                                <p class="font-display text-xs font-bold uppercase tracking-wider text-muted-ink">
                                    Subjects
                                </p>
                            </div>
                        </div>

                        <div class="rounded-2xl border-2 border-slate-200 bg-slate-50 p-5">
                            <h3 class="font-display text-sm font-black text-slate-900">
                                Before you begin
                            </h3>

                            <ul class="mt-3 space-y-2 text-sm font-medium leading-relaxed text-slate-600">
                                <li class="flex gap-2">
                                    <span class="font-black text-primary">•</span>
                                    <span>
                                        The questions are presented in a stored randomized order.
                                    </span>
                                </li>

                                <li class="flex gap-2">
                                    <span class="font-black text-primary">•</span>
                                    <span>
                                        You may move between questions and leave questions unanswered.
                                    </span>
                                </li>

                                <li class="flex gap-2">
                                    <span class="font-black text-primary">•</span>
                                    <span>
                                        Unanswered questions remain part of the 60-item score.
                                    </span>
                                </li>

                                <li class="flex gap-2">
                                    <span class="font-black text-primary">•</span>
                                    <span>
                                        No XP, badges, streaks, or mastery values are changed.
                                    </span>
                                </li>

                                <li class="flex gap-2">
                                    <span class="font-black text-primary">•</span>
                                    <span>
                                        Saved responses are submitted automatically when time expires.
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <aside class="border-t-2 border-slate-200 bg-slate-50 p-6 sm:p-8 lg:border-l-2 lg:border-t-0">
                        <div class="flex h-full flex-col justify-between gap-6">
                            <div class="space-y-5">
                                <div>
                                    <p class="font-display text-[11px] font-black uppercase tracking-wider text-muted-ink">
                                        Assessment status
                                    </p>

                                    @if ($completed)
                                        <div class="mt-3 inline-flex rounded-xl border-2 border-b-4 border-strong/30 bg-strong-tint px-4 py-2 font-display text-sm font-black text-strong-ink">
                                            Completed
                                        </div>
                                    @elseif ($inProgress)
                                        <div class="mt-3 inline-flex rounded-xl border-2 border-b-4 border-gold/30 bg-gold-tint px-4 py-2 font-display text-sm font-black text-gold-ink">
                                            In Progress
                                        </div>
                                    @elseif ($canStart)
                                        <div class="mt-3 inline-flex rounded-xl border-2 border-b-4 border-primary/30 bg-primary-tint px-4 py-2 font-display text-sm font-black text-primary">
                                            Available
                                        </div>
                                    @else
                                        <div class="mt-3 inline-flex rounded-xl border-2 border-b-4 border-slate-300 bg-white px-4 py-2 font-display text-sm font-black text-slate-600">
                                            Unavailable
                                        </div>
                                    @endif
                                </div>

                                @if ($reason)
                                    <p class="text-sm font-semibold leading-relaxed text-slate-600">
                                        {{ $reason }}
                                    </p>
                                @endif

                                <div class="border-t border-slate-200 pt-4">
                                    <p class="font-mono text-[10px] uppercase tracking-wider text-muted-ink">
                                        Form version
                                    </p>

                                    <p class="mt-1 font-mono text-xs font-bold text-slate-700">
                                        {{ $formVersion }}
                                    </p>
                                </div>
                            </div>

                            <div>
                                @if ($completed)
                                    <a
                                        href="{{ route('mock-board.results', [
                                            'session' => $completed->session_id,
                                        ]) }}"
                                        style="--lip: #4A2FC4;"
                                        class="btn-press inline-flex w-full items-center justify-center rounded-2xl bg-primary px-6 py-3.5 font-display text-sm font-black text-white shadow-sm transition hover:bg-primary/95 focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/25"
                                    >
                                        View Results
                                    </a>
                                @elseif ($inProgress)
                                    <a
                                        href="{{ route('mock-board.take', [
                                            'session' => $inProgress->session_id,
                                        ]) }}"
                                        style="--lip: #7A4B06;"
                                        class="btn-press inline-flex w-full items-center justify-center rounded-2xl bg-gold px-6 py-3.5 font-display text-sm font-black text-white shadow-sm transition hover:brightness-95 focus:outline-none focus-visible:ring-4 focus-visible:ring-gold/25"
                                    >
                                        Resume Post-Test
                                    </a>
                                @elseif ($canStart)
                                    <form
                                        method="POST"
                                        action="{{ route('mock-board.start') }}"
                                    >
                                        @csrf

                                        <button
                                            type="submit"
                                            style="--lip: #4A2FC4;"
                                            class="btn-press inline-flex w-full items-center justify-center rounded-2xl bg-primary px-6 py-3.5 font-display text-sm font-black text-white shadow-sm transition hover:bg-primary/95 focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/25"
                                        >
                                            Start Post-Test
                                        </button>
                                    </form>
                                @else
                                    <button
                                        type="button"
                                        disabled
                                        class="w-full cursor-not-allowed rounded-2xl border-b-4 border-slate-300 bg-slate-200 px-6 py-3.5 font-display text-sm font-black text-slate-500"
                                    >
                                        Post-Test Unavailable
                                    </button>
                                @endif
                            </div>
                        </div>
                    </aside>
                </div>
            </section>

            <section class="rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-6 shadow-xs">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="font-display text-lg font-black text-slate-900">
                            Form B subject availability
                        </h2>

                        <p class="mt-1 text-sm font-medium text-muted-ink">
                            Each subject requires exactly 10 validated questions.
                        </p>
                    </div>

                    <span
                        class="rounded-xl border-2 px-3 py-1 font-display text-xs font-black
                            {{ $formIsReady
                                ? 'border-strong/30 bg-strong-tint text-strong-ink'
                                : 'border-gold/30 bg-gold-tint text-gold-ink' }}"
                    >
                        {{ $formIsReady ? 'Form Ready' : 'Form Incomplete' }}
                    </span>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($availability as $subject)
                        @php
                            $subjectReady =
                                $subject['available'] === $subject['required'];
                        @endphp

                        <div class="rounded-2xl border-2 border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-display text-[10px] font-black uppercase tracking-wider text-primary">
                                        Subject {{ $subject['domain_number'] }}
                                    </p>

                                    <h3 class="mt-1 font-display text-sm font-black leading-snug text-slate-900">
                                        {{ $subject['domain_name'] }}
                                    </h3>
                                </div>

                                <span
                                    class="shrink-0 rounded-lg px-2 py-1 font-mono text-[11px] font-bold
                                        {{ $subjectReady
                                            ? 'bg-strong-tint text-strong-ink'
                                            : 'bg-weak-tint text-weak-ink' }}"
                                >
                                    {{ $subject['available'] }}/{{ $subject['required'] }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

        </div>
    </div>
</x-app-layout>
