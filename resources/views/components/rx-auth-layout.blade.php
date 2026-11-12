@props(['title', 'subtitle', 'headline', 'description', 'bubble'])

<x-guest-layout fullscreen>
    <div class="rx-auth">
        <a class="rx-auth-skip" href="#auth-content">Skip to form</a>
        <header class="rx-auth-header">
            <a class="rx-auth-brand" href="{{ url('/') }}" aria-label="CitiRx home">
                <span class="rx-auth-mark" aria-hidden="true">Rx</span>
                CitiRx
            </a>
            <div class="rx-auth-tools">
                <a class="rx-auth-home" href="{{ url('/') }}">Back to home</a>
                <x-ui.theme-toggle />
            </div>
        </header>

        <main class="rx-auth-body" id="auth-content" tabindex="-1">
            <aside class="rx-auth-story" aria-labelledby="story-heading">
                <div class="rx-auth-story-copy">
                    <h2 id="story-heading">{{ $headline }}</h2>
                    <p>{{ $description }}</p>
                </div>
                <div class="rx-auth-art">
                    <div class="rx-auth-art-back" aria-hidden="true"></div>
                    <picture>
                        <source media="(min-width: 801px)" type="image/webp"
                            srcset="{{ asset('image/pill-mascot-auth.webp') }}">
                        <img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                            alt="CitiRx’s smiling pill mascot" width="420" height="420" decoding="async">
                    </picture>
                    <span class="rx-auth-bubble">{{ $bubble }}</span>
                </div>
                <p class="rx-auth-story-note">Built for future pharmacists. One session at a time.</p>
            </aside>

            <section class="rx-auth-content" aria-labelledby="auth-heading">
                <div class="rx-auth-form-wrap">
                    <header class="rx-auth-form-heading">
                        <h1 id="auth-heading">{{ $title }}</h1>
                        <p>{{ $subtitle }}</p>
                    </header>

                    {{ $slot }}
                </div>
            </section>
        </main>

        <footer class="rx-auth-footer">
            <p>&copy; {{ date('Y') }} CitiRx. Built for pharmacy learners.</p>
        </footer>
    </div>
</x-guest-layout>
