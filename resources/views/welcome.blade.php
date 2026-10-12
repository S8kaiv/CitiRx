@php
    $steps = [
        ['Diagnostic assessment', 'Establish your starting point across the official PHLE domains.'],
        ['Personalized review path', 'Give more attention to competencies that need improvement.'],
        ['Practice and improve', 'Answer structured questions and review their rationales.'],
        ['Track your readiness', 'Monitor how your estimated readiness changes over time.'],
    ];

    $rewards = [
        ['XP', 'Earned through practice'],
        ['Streaks', 'Build review consistency'],
        ['Levels', 'Beginner to Expert'],
        ['Badges', 'Recognize milestones'],
    ];

    $badges = ['First Step', '7-Day Streak', 'Baseline Set', 'Competency Master'];

    $domains = [
        'Pharmaceutical Chemistry',
        'Pharmacognosy/Biochemistry',
        'Practice of Pharmacy',
        'Pharmacology-Pharmacokinetics',
        'Pharmaceutics',
        'Quality Control/Quality Assurance',
    ];

    $faqs = [
        [
            'Is CitiRx designed for PHLE preparation?',
            'Yes. CitiRx organizes its diagnostic, practice, and progress features around the official PHLE domains and competencies.',
        ],
        [
            'How does adaptive practice work?',
            'Your diagnostic establishes a starting estimate, while later practice responses help the system identify competencies that may need additional review.',
        ],
        [
            'Does the readiness score guarantee passing the PHLE?',
            'No. The readiness score is an educational estimate based on activity inside CitiRx and is not a guarantee of examination performance.',
        ],
        [
            'Can I use CitiRx on my phone?',
            'Yes. The interface is designed to work on phones, tablets, laptops, and desktop computers.',
        ],
    ];
@endphp
<x-guest-layout fullscreen>
    <style>
        :root {
            --primary: #6D4AFF;
            --primary-lip: #4A2FC4;
            --primary-tint: #F1EDFF;
            --gold: #F5A623;
            --gold-tint: #FDF1DC;
            --gold-ink: #7A4B06;
            --teal: #0EA5A4;
            --teal-tint: #E4FBF6;
            --teal-ink: #0B5C52;
            --success: #22C55E;
            --error: #F0524F;
            --muted: #6E6E78;
            --muted-line: #E6E6EC;
            --canvas: #F8FAFC;
        }

        /* Initial Page Refresh Fade-In */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(6px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fadeIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes pill-float {

            0%,
            100% {
                transform: translate3d(0, 0, 0) rotate(var(--r));
            }

            50% {
                transform: translate3d(0, -14px, 0) rotate(calc(var(--r) + 6deg));
            }
        }

        .pill {
            animation: pill-float var(--duration, 8s) ease-in-out infinite;
        }

        .pill:nth-child(2) {
            animation-delay: -1.5s;
        }

        .pill:nth-child(3) {
            animation-delay: -3s;
        }

        .pill:nth-child(4) {
            animation-delay: -4.5s;
        }

        .pill:nth-child(5) {
            animation-delay: -2s;
        }

        .pill:nth-child(6) {
            animation-delay: -5.5s;
        }

        .pill:nth-child(7) {
            animation-delay: -0.75s;
        }

        .pill:nth-child(8) {
            animation-delay: -6.5s;
        }

        .btn-press {
            transition: transform .08s ease, box-shadow .08s ease;
        }

        .btn-press:active {
            transform: translateY(2px);
        }

        .mascot-figure {
            filter: none;
        }

        summary::-webkit-details-marker {
            display: none;
        }

        details[open] .chev {
            transform: rotate(180deg);
        }

        @media (prefers-reduced-motion: reduce) {
            .pill {
                animation: none;
            }

            .animate-fade-in {
                animation: none;
            }
        }
    </style>

    <div class="animate-fade-in bg-white font-sans text-slate-900 antialiased">

        {{-- =========================
             NAV
             ========================= --}}
        <header class="sticky top-0 z-30 border-b border-[var(--muted-line)] bg-white/95 backdrop-blur">
            <nav class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6" aria-label="Main">
                <a href="{{ url('/') }}" class="flex items-center gap-3">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#4A2FC4] text-white text-xl font-extrabold shadow-lg rotate-[-6deg]">
                        Rx
                    </div>
                    <div>
                        <p class="text-2xl font-extrabold leading-none text-slate-900">CitiRx</p>
                        <p class="mt-1 text-xs font-medium text-[var(--muted)]">PHLE adaptive prep</p>
                    </div>
                </a>
                <ul class="hidden items-center gap-7 text-sm font-medium text-slate-700 md:flex">
                    <li><a href="#how" class="hover:text-[var(--primary)]">How it works</a></li>
                    <li><a href="#rewards" class="hover:text-[var(--primary)]">Rewards</a></li>
                    <li><a href="#faq" class="hover:text-[var(--primary)]">FAQ</a></li>
                </ul>
                <div class="flex items-center gap-3">
                    @if (Route::has('login'))
                        <a href="{{ route('login') }}"
                            class="text-sm font-semibold text-[var(--primary)] hover:underline">Log in</a>
                    @endif
                    <a href="{{ Route::has('register') ? route('register') : '#' }}"
                        class="btn-press rounded-xl bg-[#4A2FC4] px-4 py-2 font-display text-sm font-bold text-white shadow-md">Get
                        started</a>
                </div>
            </nav>
        </header>

        <main class="relative overflow-hidden">

            {{-- Decorative pills, scattered across the whole page --}}
            <div class="pointer-events-none absolute inset-0 z-0" aria-hidden="true">
                <div class="pill absolute left-[8%] top-[1%] h-6 w-14 rounded-full shadow-md"
                    style="--r:-15deg; --duration:8s; background:linear-gradient(90deg,#22c55e 0 50%,#fff 50% 100%);">
                </div>

                <div class="pill absolute right-[21%] top-[27%] h-6 w-14 rounded-full shadow-md"
                    style="--r:20deg; --duration:9s; background:linear-gradient(90deg,#F0524F 0 50%,#fff 50% 100%);">
                </div>

                <div class="pill absolute left-[5%] top-[19%] h-6 w-14 rounded-full shadow-md"
                    style="--r:35deg; --duration:10s; background:linear-gradient(90deg,#0EA5A4 0 50%,#fff 50% 100%);">
                </div>

                <div class="pill absolute left-[93%] top-[8%] h-6 w-14 rounded-full shadow-md"
                    style="--r:-30deg; --duration:7.5s; background:linear-gradient(90deg,#6D4AFF 0 50%,#fff 50% 100%);">
                </div>

                <div class="pill absolute left-[2%] top-[35%] h-6 w-14 rounded-full shadow-md"
                    style="--r:-20deg; --duration:9.5s; background:linear-gradient(90deg,#ec4899 0 50%,#fff 50% 100%);">
                </div>

                <div class="pill absolute right-[6%] top-[44%] h-6 w-14 rounded-full shadow-md"
                    style="--r:20deg; --duration:8.5s; background:linear-gradient(90deg,#0EA5A4 0 50%,#fff 50% 100%);">
                </div>

                <div class="pill absolute left-[6%] top-[57%] h-6 w-14 rounded-full shadow-md"
                    style="--r:25deg; --duration:11s; background:linear-gradient(90deg,#ec4899 0 50%,#fff 50% 100%);">
                </div>

                <div class="pill absolute left-[13%] top-[75%] h-6 w-14 rounded-full shadow-md"
                    style="--r:-25deg; --duration:9s; background:linear-gradient(90deg,#f97316 0 50%,#fff 50% 100%);">
                </div>

                <div class="pill absolute right-[9%] top-[79%] h-6 w-14 rounded-full shadow-md"
                    style="--r:-15deg; --duration:7s; background:linear-gradient(90deg,#22c55e 0 50%,#fff 50% 100%);">
                </div>
            </div>

            {{-- =========================
                 HERO
                 ========================= --}}
            <section class=" relative z-10 mx-auto max-w-6xl px-4 pt-8 sm:px-6 sm:pt-12">
                <div
                    class="grid items-center gap-8 rounded-[1.75rem] bg-[var(--primary-tint)] px-6 py-10 sm:px-12 md:grid-cols-5">
                    <div class="md:col-span-3">
                        <h1 class="font-display text-4xl font-bold leading-tight tracking-tight sm:text-5xl">
                            <span class="block">
                                Prepare for the <span class="text-amber-500">PHLE</span>
                                with practice that adapts to you
                            </span>
                        </h1>
                        <p class="mt-4 max-w-xl text-lg text-slate-700">
                            Take a diagnostic, receive a review path based on your
                            competency estimates, and track how your readiness changes
                            through continued practice.
                        </p>
                        <div class="mt-7 flex flex-wrap items-center gap-x-6 gap-y-3">
                            <a href="{{ Route::has('register') ? route('register') : '#' }}"
                                class="btn-press flex items-center justify-center rounded-2xl bg-primary px-7 py-3 font-display text-base font-bold text-white shadow-md hover:bg-primary-lip disabled:opacity-70 focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/25">
                                Get Started
                            </a>
                        </div>
                        @if (Route::has('login'))
                            <p class="mt-4 text-sm text-[var(--muted)]">
                                Already reviewing?
                                <a href="{{ route('login') }}"
                                    class="font-semibold text-[var(--primary)] hover:underline">Log in</a>
                            </p>
                        @endif
                    </div>
                    <div class="flex justify-center md:col-span-2">
                        <img src="{{ asset('image/pill-mascot.png') }}"
                            alt="Cheerful purple CitiRx pill mascot waving hello" width="2000" height="2000"
                            decoding="async" fetchpriority="high"
                            class="h-32 w-auto object-contain sm:h-48 md:h-64 lg:h-72 xl:h-96">
                    </div>
                </div>
            </section>

            {{-- =========================
                 HOW IT WORKS
                 ========================= --}}
            <section id="how" class=" relative z-10 mx-auto max-w-6xl px-4 py-16 sm:px-6"
                aria-labelledby="how-h">
                <h2 id="how-h" class="font-display text-3xl font-bold">Four steps from diagnostic to exam-ready</h2>
                <ol class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($steps as $i => [$title, $text])
                        <li class=" rounded-2xl border border-[var(--muted-line)] bg-white p-5">
                            <span
                                class="flex h-8 w-8 items-center justify-center rounded-full bg-[var(--primary)] font-display text-sm font-bold text-white">{{ $i + 1 }}</span>
                            <h3 class="mt-4 font-display text-lg font-bold">{{ $title }}</h3>
                            <p class="mt-1 text-sm text-[var(--muted)]">{{ $text }}</p>
                        </li>
                    @endforeach
                </ol>
            </section>

            {{-- =========================
                 REWARDS
                 ========================= --}}
            <section id="rewards" class=" relative z-10 mx-auto max-w-6xl px-4 pb-16 sm:px-6"
                aria-labelledby="rew-h">
                <div class="rounded-[1.75rem] border border-[var(--muted-line)] bg-white p-6 sm:p-10">
                    <h2 id="rew-h" class="font-display text-3xl font-bold">Make reviewing a habit</h2>
                    <p class="mt-2 max-w-xl text-[var(--muted)]">Earn XP, build your review streak, and progress from
                        Beginner to Expert through continued practice.</p>
                    <dl class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
                        @foreach ($rewards as [$v, $l])
                            <div class="rounded-2xl p-4 text-center" style="background: var(--gold-tint);">
                                <dt class="order-2 text-sm" style="color: var(--gold-ink);">{{ $l }}</dt>
                                <dd class="font-display text-2xl font-bold" style="color: var(--gold-ink);">
                                    {{ $v }}</dd>
                            </div>
                        @endforeach
                    </dl>
                    <ul class="mt-6 flex flex-wrap gap-2">
                        @foreach ($badges as $b)
                            <li class="rounded-full border bg-white px-3.5 py-1.5 font-display text-sm font-semibold"
                                style="border-color: var(--gold); color: var(--gold-ink);">{{ $b }}</li>
                        @endforeach
                    </ul>
                </div>
            </section>

            {{-- =========================
                 DOMAINS + FAQ
                 ========================= --}}
            <section class=" relative z-10 mx-auto max-w-6xl px-4 pb-16 sm:px-6"
                aria-labelledby="cov-h">
                <h2 id="cov-h" class="font-display text-3xl font-bold">Built around the PhLE domains</h2>
                <ul class="mt-5 flex flex-wrap gap-2">
                    @foreach ($domains as $d)
                        <li class="rounded-full px-4 py-2 font-display text-sm font-semibold"
                            style="background: var(--teal-tint); color: var(--teal-ink);">{{ $d }}</li>
                    @endforeach
                </ul>

                <div id="faq" class="mx-auto mt-12 max-w-3xl">
                    <h2 class="font-display text-3xl font-bold">Frequently asked questions</h2>
                    <div
                        class="mt-5 divide-y divide-[var(--muted-line)] rounded-2xl border border-[var(--muted-line)] bg-white">
                        @foreach ($faqs as [$q, $a])
                            <details class="group px-5 py-4">
                                <summary
                                    class="flex cursor-pointer list-none items-center justify-between gap-4 font-medium">
                                    {{ $q }}
                                    <svg class="chev h-5 w-5 shrink-0 text-[var(--muted)] transition-transform"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                    </svg>
                                </summary>
                                <p class="mt-2 text-sm text-[var(--muted)]">{{ $a }}</p>
                            </details>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- =========================
                 FINAL CTA
                 ========================= --}}
            <section class=" relative z-10 mx-auto max-w-6xl px-4 pb-16 sm:px-6">
                <div class="rounded-[1.75rem] bg-[#4A2FC4] px-6 py-12 text-center sm:px-12">
                    <h2 class="font-display text-3xl font-bold text-white sm:text-4xl">Your PhLE preparation starts
                        here</h2>
                    <p class="mx-auto mt-2 max-w-md text-white/90">Take the diagnostic, see where you stand, and start
                        closing the gaps today.</p>
                    <a href="{{ Route::has('register') ? route('register') : '#' }}"
                        class="btn-press mt-7 inline-block rounded-2xl bg-white px-7 py-3.5 font-display text-lg font-bold text-primary shadow-md">
                        Start free diagnostic
                    </a>
                </div>
            </section>
        </main>

        {{-- =========================
             FOOTER
             ========================= --}}
        <footer class="relative z-10 border-t border-[var(--muted-line)] bg-white">
            <div
                class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-4 py-8 text-sm text-[var(--muted)] sm:flex-row sm:px-6">
                <span class="font-display text-lg font-bold text-slate-900">CitiRx</span>
                <p>&copy; {{ date('Y') }} CitiRx. Built for pharmacy learners.</p>
            </div>
        </footer>
    </div>

</x-guest-layout>
