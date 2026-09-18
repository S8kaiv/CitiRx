<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-display text-xl font-bold text-slate-900 leading-tight">
                    {{ __('Practice Summary') }}
                </h2>
                <p class="text-xs font-semibold uppercase tracking-wider text-muted-ink">
                    Session Mastery & Performance
                </p>
            </div>

            <a href="{{ route('dashboard') }}" 
               class="font-display text-xs font-bold text-muted-ink hover:text-primary transition">
                Return to Dashboard
            </a>
        </div>
    </x-slot>

    @php
        // Scaled donut chart math (176px box, radius 75)
        $radius = 75;
        $circumference = 2 * M_PI * $radius; // ~439.82
        $scoreRatio = $session->total_items > 0 ? min(1, $session->correct_items / $session->total_items) : 0;
        $ringOffset = $circumference * (1 - $scoreRatio);

        $ringColor = match (true) {
            $rawAccuracy >= 80 => '#22C55E', // Strong Green
            $rawAccuracy >= 60 => '#6D4AFF', // Primary Violet
            default            => '#F5A623', // Gold
        };

        // Identify lowest domain for session takeaway
        $lowestDomain = collect($perDomain)->sortBy(function ($d) {
            return $d['total'] > 0 ? ($d['correct'] / $d['total']) : 0;
        })->first();

        $lowestPct = $lowestDomain && $lowestDomain['total'] > 0 
            ? round((100 * $lowestDomain['correct']) / $lowestDomain['total']) 
            : 0;
    @endphp

    <div class="pt-6 pb-12 font-sans text-slate-900 antialiased">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 space-y-4">

            {{-- Status Alert Message (From Teammate) --}}
            @if (session('status'))
                <div role="status" class="flex items-center gap-3 rounded-2xl border-2 border-b-4 border-primary/30 bg-primary-tint/60 p-4 font-display text-xs font-bold text-primary shadow-sm">
                    <span class="text-base">ℹ️</span>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            {{-- TOP ROW: SIDE-BY-SIDE CARDS --}}
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-stretch">
                
                {{-- LEFT: Score Donut Card --}}
                <div class="md:col-span-5 lg:col-span-4 flex flex-col justify-between rounded-2xl border-2 border-b-4 border-slate-200 bg-white p-5 shadow-sm text-center">
                    <div>
                        <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-0.5 font-display text-[11px] font-bold uppercase tracking-wider text-muted-ink">
                            Session Complete
                        </span>

                        {{-- Expanded 176px Donut --}}
                        <div class="relative mx-auto mt-3 mb-4" style="width: 176px; height: 176px;">
                            <svg style="width: 176px; height: 176px;" class="-rotate-90" viewBox="0 0 176 176">
                                <circle cx="88" cy="88" r="{{ $radius }}" fill="none" stroke="#F1F5F9" stroke-width="15" />
                                <circle cx="88" cy="88" r="{{ $radius }}" fill="none" stroke="{{ $ringColor }}" stroke-width="15"
                                        stroke-linecap="round"
                                        stroke-dasharray="{{ round($circumference, 2) }}"
                                        stroke-dashoffset="{{ round($ringOffset, 2) }}"
                                        class="transition-all duration-1000 ease-out" />
                            </svg>

                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                <span class="font-display text-4xl font-extrabold text-slate-900 leading-none">
                                    {{ round($rawAccuracy) }}<span class="text-xl font-bold text-muted-ink">%</span>
                                </span>
                                <span class="font-display text-[11px] font-bold text-muted-ink uppercase tracking-wider mt-1.5">
                                    Accuracy
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Score & XP Stats --}}
                    <div class="space-y-2">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 py-1.5 px-3 font-display text-xs font-bold text-slate-700">
                            Score: {{ $session->correct_items }} / {{ $session->total_items }} Correct
                        </div>

                        <div class="flex items-center justify-center gap-1.5 rounded-xl border-2 border-b-4 border-[#F5A623]/30 bg-gold-tint py-1.5 px-3 font-display text-xs font-bold text-gold-ink shadow-sm">
                            <svg class="h-3.5 w-3.5 text-gold shrink-0" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M14.615 1.595a.75.75 0 0 1 .359.852L12.982 9.75h7.268a.75.75 0 0 1 .548 1.262l-10.5 11.25a.75.75 0 0 1-1.272-.71l1.992-7.302H3.75a.75.75 0 0 1-.548-1.262l10.5-11.25a.75.75 0 0 1 .913-.143Z"/>
                            </svg>
                            <span>+{{ $session->xp_awarded }} XP Earned</span>
                        </div>

                        @if ($eligibleCount < $session->total_items)
                            <p class="text-[10px] text-muted-ink leading-tight">
                                Eligible: <strong class="text-slate-800">{{ number_format($eligibleAccuracy, 1) }}%</strong> ({{ $eligibleCorrect }}/{{ $eligibleCount }} non-speed-flagged).
                            </p>
                        @endif
                    </div>
                </div>

                {{-- RIGHT: 2-Column Domain Breakdown Card --}}
                <div class="md:col-span-7 lg:col-span-8 flex flex-col justify-between rounded-2xl border-2 border-b-4 border-slate-200 bg-white p-5 shadow-sm">
                    <div>
                        <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-2">
                            <h3 class="font-display text-sm font-bold text-slate-900">
                                Breakdown by Domain
                            </h3>
                            <span class="font-display text-[11px] font-semibold text-muted-ink">
                                Performance
                            </span>
                        </div>

                        @if (count($perDomain) > 0)
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                                @foreach ($perDomain as $domain)
                                    @php
                                        $domainPercentage = $domain['total'] > 0
                                            ? round((100 * $domain['correct']) / $domain['total'])
                                            : 0;

                                        $barColor = match (true) {
                                            $domainPercentage >= 80 => 'bg-strong',
                                            $domainPercentage >= 60 => 'bg-clinical',
                                            default                 => 'bg-weak',
                                        };
                                    @endphp

                                    <div class="space-y-1.5">
                                        <div class="flex items-center justify-between gap-2 text-xs">
                                            <span class="font-display font-bold text-slate-800 truncate" title="{{ $domain['domain_name'] }}">
                                                {{ $domain['domain_name'] }}
                                            </span>
                                            <span class="font-display font-bold text-slate-900 shrink-0">
                                                {{ $domainPercentage }}%
                                            </span>
                                        </div>

                                        <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100 ring-1 ring-inset ring-slate-200/60">
                                            <div class="h-full rounded-full {{ $barColor }} transition-all duration-700"
                                                 style="width: {{ $domainPercentage }}%"></div>
                                        </div>

                                        <div class="text-[11px] font-medium text-muted-ink">
                                            {{ $domain['correct'] }}/{{ $domain['total'] }} answered
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-muted-ink py-8 text-center">
                                No domain data recorded for this session.
                            </p>
                        @endif
                    </div>

                    {{-- Adaptive Remediation Note --}}
                    @if ($lowestDomain)
                        <div class="mt-4 rounded-xl border border-clinical/30 bg-clinical-tint/50 p-3 text-xs text-clinical-ink leading-relaxed">
                            Your accuracy was lowest in <strong>{{ $lowestDomain['domain_name'] }}</strong> ({{ $lowestPct }}%). Targeted questions will be prioritized in your next session.
                        </div>
                    @endif
                </div>

            </div>

            {{-- MIDDLE ROW: LAST QUESTION & BOOKMARK REMEDIATION (Teammate's feature in 3D Card) --}}
            @if ($lastQuestion)
                <div class="relative overflow-hidden rounded-2xl border-2 border-b-4 border-slate-200 bg-white p-5 shadow-sm">
                    {{-- Star / Bookmark Form --}}
                    <form method="POST"
                          action="{{ $lastQuestionIsBookmarked
                              ? route('bookmarks.destroyQuestion', ['question' => $lastQuestion->question_id])
                              : route('bookmarks.store', ['question' => $lastQuestion->question_id]) }}"
                          class="absolute right-4 top-4">
                        @csrf
                        <input type="hidden" name="_method" value="{{ $lastQuestionIsBookmarked ? 'DELETE' : 'PUT' }}">
                        <input type="hidden" name="practice_session_id" value="{{ $session->session_id }}">

                        <button type="submit"
                                aria-label="{{ $lastQuestionIsBookmarked ? 'Remove bookmark' : 'Bookmark last question' }}"
                                title="{{ $lastQuestionIsBookmarked ? 'Remove bookmark' : 'Bookmark last question' }}"
                                class="flex h-9 w-9 items-center justify-center rounded-xl border-2 transition {{ $lastQuestionIsBookmarked ? 'border-[#F5A623]/40 bg-gold-tint text-gold shadow-sm' : 'border-slate-200 bg-slate-50 text-slate-400 hover:text-gold hover:border-[#F5A623]/40' }}">
                            <span class="text-xl leading-none">{{ $lastQuestionIsBookmarked ? '★' : '☆' }}</span>
                        </button>
                    </form>

                    <div class="pr-12">
                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 font-display text-[10px] font-bold uppercase tracking-wider text-muted-ink">
                            Last Question Addressed
                        </span>

                        <p class="mt-2 text-sm font-bold text-slate-900 leading-snug">
                            {{ $lastQuestion->question_text }}
                        </p>

                        <p class="mt-2 text-xs text-muted-ink">
                            Star this question to save it directly to your <strong>Rx Vault</strong> for targeted review.
                        </p>
                    </div>
                </div>
            @endif

            {{-- BOTTOM ROW: UPDATED PHLE ESTIMATE --}}
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3 rounded-2xl border-2 border-b-4 border-slate-200 bg-white p-4 sm:px-6 shadow-sm">
                <div>
                    <span class="font-display text-[10px] font-bold uppercase tracking-wider text-muted-ink">
                        Updated PhLE Estimate
                    </span>
                    <div class="mt-0.5 flex items-baseline gap-2">
                        <span class="font-display text-2xl sm:text-3xl font-extrabold text-slate-900">
                            {{ $currentReadiness !== null ? number_format($currentReadiness, 1) . '%' : 'Pending' }}
                        </span>
                        <span class="text-xs font-medium text-muted-ink">Board Readiness</span>
                    </div>
                </div>

                <div>
                    <a href="{{ route('readiness.show') }}"
                       class="font-display text-xs font-bold text-primary transition hover:underline">
                        View Full Breakdown Report →
                    </a>
                </div>
            </div>

            {{-- ACTION BUTTONS --}}
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-1">
                <a href="{{ route('practice.intro') }}"
                   style="--lip: #4A2FC4;"
                   class="btn-press w-full sm:w-auto rounded-xl bg-primary px-8 py-2.5 text-center font-display text-sm font-bold text-white shadow-sm transition hover:bg-primary/95">
                    Practice Again
                </a>

                <a href="{{ route('dashboard') }}"
                   style="--lip: #CBD5E1;"
                   class="btn-press w-full sm:w-auto rounded-xl border-2 border-slate-200 bg-white px-8 py-2.5 text-center font-display text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                    Back to Dashboard
                </a>
            </div>

        </div>
    </div>
</x-app-layout>