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
    <div class="rx-welcome">
        <a class="rx-skip" href="#main-content">Skip to content</a>
        <header class="rx-header">
            <nav class="rx-wrap rx-nav" aria-label="Main navigation">
                <a class="rx-brand" href="{{ url('/') }}" aria-label="CitiRx home">
                    <span class="rx-mark" aria-hidden="true">Rx</span>
                    CitiRx
                </a>
                <div class="rx-links">
                    <a href="#how">How it works</a>
                    <a href="#rewards">Rewards</a>
                    <a href="#faq">FAQ</a>
                </div>
                <div class="rx-account">
                    @if (Route::has('login'))
                        <a href="{{ route('login') }}">Log in</a>
                    @endif
                    <span class="rx-header-cta">
                        <a class="rx-button rx-secondary" href="{{ route('register') }}">Get started</a>
                    </span>
                    <x-ui.theme-toggle />
                </div>
            </nav>
        </header>

        <main id="main-content" tabindex="-1">
            <section class="rx-wrap rx-hero" aria-labelledby="hero-title">
                <div class="rx-hero-copy">
                    <h1 id="hero-title">
                        Review for the <span>PHLE</span> with practice that adapts to you
                    </h1>
                    <p class="rx-intro">Start with a diagnostic, follow a review path built from your results, and watch your readiness grow.</p>
                    <div class="rx-actions">
                        <a class="rx-button" href="{{ route('register') }}">Get started <span aria-hidden="true">→</span>
                        </a>
                        <a class="rx-button rx-secondary" href="#how">See how it works</a>
                    </div>
                    <p class="rx-note">Already reviewing with us? <a href="{{ route('login') }}">Pick up where you left off</a>
                    </p>
                </div>
                <div class="rx-art">
                    <div class="rx-art-back" aria-hidden="true"></div>
                    <img
                        class="rx-mascot"
                        src="{{ asset('image/pill-mascot.png') }}"
                        alt="CitiRx’s smiling pill mascot welcomes you"
                        width="420"
                        height="420"
                        fetchpriority="high"
                    >
                    <span class="rx-bubble">A little practice. A step forward.</span>
                    <span class="rx-art-label">Your next chapter starts here.</span>
                </div>
            </section>

            <section class="rx-domains" aria-label="PHLE coverage">
                <div class="rx-wrap">
                    <p class="rx-domains-title">Built around the PhLE domains</p>
                    <ul class="rx-domain-list">
                        @foreach ($domains as $domain)
                            <li>{{ $domain }}</li>
                        @endforeach
                    </ul>
                </div>
            </section>

            <section class="rx-wrap rx-section" id="how" aria-labelledby="how-title">
                <h2 id="how-title">A clearer path through your review.</h2>
                <p class="rx-section-intro">Know where you stand. Focus your effort. Build your confidence one practice session at a time.</p>
                <ol class="rx-steps">
                    @foreach ($steps as $i => [$title, $text])
                        <li>
                            <span class="rx-step-num">{{ $i + 1 }}</span>
                            <h3>{{ $title }}</h3>
                            <p>{{ $text }}</p>
                        </li>
                    @endforeach
                </ol>
            </section>

            <section class="rx-wrap" id="rewards" aria-labelledby="rewards-title">
                <div class="rx-rewards">
                    <div>
                        <h2 id="rewards-title">Small steps.<br>A habit worth keeping.</h2>
                        <p>Earn XP, build your review streak, and progress from Beginner to Expert through continued practice.</p>
                        <ul class="rx-badges" aria-label="Milestone badges">
                            @foreach ($badges as $badge)
                                <li>{{ $badge }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <dl>
                        @foreach ($rewards as [$value, $label])
                            <div>
                                <dt>{{ $value }}</dt>
                                <dd>{{ $label }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </section>

            <section class="rx-wrap rx-section rx-faq" id="faq" aria-labelledby="faq-title">
                <div>
                    <h2 id="faq-title">Before you jump in.</h2>
                    <p class="rx-section-intro">A few things to know about your review companion.</p>
                </div>
                <div>
                    @foreach ($faqs as [$question, $answer])
                        <details>
                            <summary>{{ $question }}</summary>
                            <p>{{ $answer }}</p>
                        </details>
                    @endforeach
                </div>
            </section>

            <section class="rx-wrap rx-final" aria-labelledby="final-title">
                <h2 id="final-title">Your PhLE preparation starts here.</h2>
                <p>Take the diagnostic, see where you stand, and start closing the gaps today.</p>
                <a class="rx-button" href="{{ route('register') }}">Let’s get started <span aria-hidden="true">→</span>
                </a>
            </section>

        </main>

        <footer class="rx-wrap rx-footer">
            <strong>CitiRx</strong>
            <p>&copy; {{ date('Y') }} CitiRx. Built for pharmacy learners.</p>
            <a href="#hero-title">Back to top ↑</a>
        </footer>
    </div>
</x-guest-layout>
