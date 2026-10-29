<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="font-display text-xl font-black leading-tight text-ink">
                Research Post-Test
            </h1>

            <p class="mt-1 font-display text-[11px] font-bold uppercase tracking-wider text-ink-muted">
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

            <section class="overflow-hidden rounded-3xl border-2 border-b-4 border-line bg-surface shadow-xs">
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

                            <h2 class="font-display text-3xl font-black leading-tight text-ink sm:text-4xl">
                                Pharmacy Mock Board Assessment
                            </h2>

                            <p class="max-w-2xl text-sm font-medium leading-relaxed text-ink-muted sm:text-base">
                                This assessment measures your knowledge after using CitiRx.
                                Your responses are saved automatically, and no correctness
                                feedback is shown while the assessment is active.
                            </p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3">
                            <div class="rounded-2xl border-2 border-b-4 border-line bg-surface-2 p-4">
                                <p class="font-display text-2xl font-black text-primary">
                                    60
                                </p>

                                <p class="font-display text-xs font-bold uppercase tracking-wider text-ink-muted">
                                    Questions
                                </p>
                            </div>

                            <div class="rounded-2xl border-2 border-b-4 border-line bg-surface-2 p-4">
                                <p class="font-display text-2xl font-black text-primary">
                                    {{ $durationMinutes }}
                                </p>

                                <p class="font-display text-xs font-bold uppercase tracking-wider text-ink-muted">
                                    Minutes
                                </p>
                            </div>

                            <div class="rounded-2xl border-2 border-b-4 border-line bg-surface-2 p-4">
                                <p class="font-display text-2xl font-black text-primary">
                                    6
                                </p>

                                <p class="font-display text-xs font-bold uppercase tracking-wider text-ink-muted">
                                    Subjects
                                </p>
                            </div>
                        </div>

                        <div class="rounded-2xl border-2 border-line bg-surface-2 p-5">
                            <h3 class="font-display text-sm font-black text-ink">
                                Before you begin
                            </h3>

                            <ul class="mt-3 space-y-2 text-sm font-medium leading-relaxed text-ink-muted">
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

                    <aside class="border-t-2 border-line bg-surface-2 p-6 sm:p-8 lg:border-l-2 lg:border-t-0">
                        <div class="flex h-full flex-col justify-between gap-6">
                            <div class="space-y-5">
                                <div>
                                    <p class="font-display text-[11px] font-black uppercase tracking-wider text-ink-muted">
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
                                        <div class="mt-3 inline-flex rounded-xl border-2 border-b-4 border-line-strong bg-surface px-4 py-2 font-display text-sm font-black text-ink-muted">
                                            Unavailable
                                        </div>
                                    @endif
                                </div>

                                @if ($reason)
                                    <p class="text-sm font-semibold leading-relaxed text-ink-muted">
                                        {{ $reason }}
                                    </p>
                                @endif

                                <div class="border-t border-line pt-4">
                                    <p class="font-mono text-[10px] uppercase tracking-wider text-ink-muted">
                                        Form version
                                    </p>

                                    <p class="mt-1 font-mono text-xs font-bold text-ink">
                                        {{ $formVersion }}
                                    </p>
                                </div>
                            </div>

                            <div>
                                @if ($completed)
                                    <x-ui.button
                                        :href="route('mock-board.results', [
                                            'session' => $completed->session_id,
                                        ])"
                                        class="w-full"
                                    >
                                        View Results
                                    </x-ui.button>
                                @elseif ($inProgress)
                                    <x-ui.button
                                        :href="route('mock-board.take', [
                                            'session' => $inProgress->session_id,
                                        ])"
                                        class="w-full"
                                    >
                                        Resume Post-Test
                                    </x-ui.button>
                                @elseif ($canStart)
                                    <form
                                        method="POST"
                                        action="{{ route('mock-board.start') }}"
                                    >
                                        @csrf

                                        <x-ui.button
                                            type="submit"
                                            class="w-full"
                                        >
                                            Start Post-Test
                                        </x-ui.button>
                                    </form>
                                @else
                                    <x-ui.button
                                        type="button"
                                        :disabled="true"
                                        variant="secondary"
                                        class="w-full"
                                    >
                                        Post-Test Unavailable
                                    </x-ui.button>
                                @endif
                            </div>
                        </div>
                    </aside>
                </div>
            </section>

            <section class="rounded-3xl border-2 border-b-4 border-line bg-surface p-6 shadow-xs">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="font-display text-lg font-black text-ink">
                            Form B subject availability
                        </h2>

                        <p class="mt-1 text-sm font-medium text-ink-muted">
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

                        <div class="rounded-2xl border-2 border-line bg-surface-2 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-display text-[10px] font-black uppercase tracking-wider text-primary">
                                        Subject {{ $subject['domain_number'] }}
                                    </p>

                                    <h3 class="mt-1 font-display text-sm font-black leading-snug text-ink">
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
