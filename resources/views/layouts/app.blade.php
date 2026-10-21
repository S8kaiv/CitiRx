<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'CitiRx') }} PhLE Review</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

@php
    $themeLevel = Auth::user()?->role === 'student' ? max(1, (int) (Auth::user()->current_level ?? 1)) : 1;
@endphp

<body data-authenticated-page="true" x-data="themeManager(@js($themeLevel))"
    :class="[
        globalTheme,
        {
            'theme-animations-paused': animationsPaused,
        },
    ]"
    class="isolate relative min-h-screen h-full font-sans text-slate-900 antialiased selection:bg-primary-tint selection:text-primary">

    {{-- ============================================================ --}}
    {{-- ATMOSPHERIC THEME ENVIRONMENT & PARTICLE ANIMATIONS         --}}
    {{-- ============================================================ --}}
    <div class="theme-environment pointer-events-none fixed inset-0 z-0 overflow-hidden transition-colors duration-500"
        aria-hidden="true">
        <div class="theme-particles absolute inset-0 h-full w-full">
            {{-- Sakura --}}
            <template x-if="globalTheme === 'theme-sakura'">
                <div class="theme-scene sakura-scene">
                    <span class="sakura-petal"
                        style="--petal-left: 7%; --petal-delay: -13s; --petal-duration: 22s; --petal-drift: 8vw; --petal-top: 14%;"></span>
                    <span class="sakura-petal"
                        style="--petal-left: 18%; --petal-delay: -4s; --petal-duration: 19s; --petal-drift: -6vw; --petal-top: 42%;"></span>
                    <span class="sakura-petal"
                        style="--petal-left: 29%; --petal-delay: -17s; --petal-duration: 24s; --petal-drift: 10vw; --petal-top: 71%;"></span>
                    <span class="sakura-petal"
                        style="--petal-left: 40%; --petal-delay: -8s; --petal-duration: 20s; --petal-drift: -8vw; --petal-top: 27%;"></span>
                    <span class="sakura-petal"
                        style="--petal-left: 52%; --petal-delay: -20s; --petal-duration: 25s; --petal-drift: 7vw; --petal-top: 58%;"></span>
                    <span class="sakura-petal"
                        style="--petal-left: 63%; --petal-delay: -2s; --petal-duration: 18s; --petal-drift: -10vw; --petal-top: 83%;"></span>
                    <span class="sakura-petal"
                        style="--petal-left: 74%; --petal-delay: -11s; --petal-duration: 23s; --petal-drift: 6vw; --petal-top: 35%;"></span>
                    <span class="sakura-petal"
                        style="--petal-left: 85%; --petal-delay: -6s; --petal-duration: 21s; --petal-drift: -7vw; --petal-top: 66%;"></span>
                    <span class="sakura-petal"
                        style="--petal-left: 95%; --petal-delay: -15s; --petal-duration: 24s; --petal-drift: 9vw; --petal-top: 21%;"></span>
                    <span class="sakura-petal"
                        style="--petal-left: 13%; --petal-delay: -19s; --petal-duration: 27s; --petal-drift: 5vw; --petal-top: 55%;"></span>
                    <span class="sakura-petal"
                        style="--petal-left: 58%; --petal-delay: -10s; --petal-duration: 26s; --petal-drift: -5vw; --petal-top: 11%;"></span>
                    <span class="sakura-petal"
                        style="--petal-left: 91%; --petal-delay: -23s; --petal-duration: 28s; --petal-drift: -8vw; --petal-top: 47%;"></span>
                </div>
            </template>

            {{-- Forest --}}
            <template x-if="globalTheme === 'theme-forest'">
                <div class="theme-scene forest-scene">
                    <span class="forest-tree"
                        style="--tree-left: -2%; --tree-height: 68vh; --tree-width: clamp(120px, 17vw, 250px); --tree-opacity: .22;"></span>
                    <span class="forest-tree"
                        style="--tree-left: 10%; --tree-height: 48vh; --tree-width: clamp(100px, 13vw, 190px); --tree-opacity: .12;"></span>
                    <span class="forest-tree"
                        style="--tree-left: 78%; --tree-height: 52vh; --tree-width: clamp(105px, 14vw, 205px); --tree-opacity: .13;"></span>
                    <span class="forest-tree"
                        style="--tree-left: 88%; --tree-height: 72vh; --tree-width: clamp(125px, 18vw, 265px); --tree-opacity: .20;"></span>

                    <span class="forest-leaf"
                        style="--scene-left: 7%; --scene-top: 18%; --scene-delay: -12s; --scene-duration: 25s; --scene-drift: 8vw; --leaf-scale: .75;"></span>
                    <span class="forest-leaf"
                        style="--scene-left: 18%; --scene-top: 59%; --scene-delay: -3s; --scene-duration: 22s; --scene-drift: -7vw; --leaf-scale: .60;"></span>
                    <span class="forest-leaf"
                        style="--scene-left: 30%; --scene-top: 35%; --scene-delay: -19s; --scene-duration: 28s; --scene-drift: 10vw; --leaf-scale: .90;"></span>
                    <span class="forest-leaf"
                        style="--scene-left: 42%; --scene-top: 76%; --scene-delay: -8s; --scene-duration: 24s; --scene-drift: -9vw; --leaf-scale: .70;"></span>
                    <span class="forest-leaf"
                        style="--scene-left: 54%; --scene-top: 12%; --scene-delay: -22s; --scene-duration: 29s; --scene-drift: 6vw; --leaf-scale: .85;"></span>
                    <span class="forest-leaf"
                        style="--scene-left: 66%; --scene-top: 53%; --scene-delay: -5s; --scene-duration: 23s; --scene-drift: -8vw; --leaf-scale: .65;"></span>
                    <span class="forest-leaf"
                        style="--scene-left: 78%; --scene-top: 29%; --scene-delay: -15s; --scene-duration: 27s; --scene-drift: 9vw; --leaf-scale: .80;"></span>
                    <span class="forest-leaf"
                        style="--scene-left: 90%; --scene-top: 72%; --scene-delay: -1s; --scene-duration: 26s; --scene-drift: -6vw; --leaf-scale: .70;"></span>
                </div>
            </template>

            {{-- Midnight --}}
            <template x-if="globalTheme === 'theme-midnight'">
                <div class="theme-scene galaxy-scene">
                    <span class="galaxy-rock"
                        style="--scene-left: 12%; --scene-top: 24%; --rock-size: 82px; --scene-delay: -8s;"></span>
                    <span class="galaxy-rock"
                        style="--scene-left: 36%; --scene-top: 72%; --rock-size: 54px; --scene-delay: -15s;"></span>
                    <span class="galaxy-rock"
                        style="--scene-left: 69%; --scene-top: 19%; --rock-size: 68px; --scene-delay: -4s;"></span>
                    <span class="galaxy-rock"
                        style="--scene-left: 87%; --scene-top: 67%; --rock-size: 94px; --scene-delay: -12s;"></span>
                </div>
            </template>

            {{-- Ocean --}}
            <template x-if="globalTheme === 'theme-ocean'">
                <div class="theme-scene ocean-scene">
                    <span class="ocean-fish"
                        style="--fish-left: 14%; --fish-top: 24%; --fish-delay: -7s; --fish-duration: 34s; --fish-rise: 4vh;"></span>
                    <span class="ocean-fish ocean-fish-reverse"
                        style="--fish-left: 70%; --fish-top: 43%; --fish-delay: -19s; --fish-duration: 39s; --fish-rise: -5vh; --fish-scale: .72;"></span>
                    <span class="ocean-fish"
                        style="--fish-left: 38%; --fish-top: 64%; --fish-delay: -27s; --fish-duration: 42s; --fish-rise: -3vh; --fish-scale: .88;"></span>
                    <span class="ocean-fish ocean-fish-reverse"
                        style="--fish-left: 82%; --fish-top: 79%; --fish-delay: -11s; --fish-duration: 36s; --fish-rise: 3vh; --fish-scale: .62;"></span>
                </div>
            </template>

            {{-- Solar --}}
            <template x-if="globalTheme === 'theme-solar'">
                <div class="theme-scene solar-scene">
                    <span class="solar-ray" style="--ray-left: 12%; --ray-delay: -8s;"></span>
                    <span class="solar-ray" style="--ray-left: 37%; --ray-delay: -14s;"></span>
                    <span class="solar-ray" style="--ray-left: 63%; --ray-delay: -4s;"></span>
                    <span class="solar-ray" style="--ray-left: 88%; --ray-delay: -19s;"></span>

                    <span class="golden-ember"
                        style="--scene-left: 14%; --scene-top: 24%; --scene-delay: -8s; --scene-duration: 18s; --scene-drift: 5vw;"></span>
                    <span class="golden-ember"
                        style="--scene-left: 31%; --scene-top: 68%; --scene-delay: -15s; --scene-duration: 22s; --scene-drift: -6vw;"></span>
                    <span class="golden-ember"
                        style="--scene-left: 49%; --scene-top: 39%; --scene-delay: -3s; --scene-duration: 19s; --scene-drift: 7vw;"></span>
                    <span class="golden-ember"
                        style="--scene-left: 68%; --scene-top: 79%; --scene-delay: -18s; --scene-duration: 23s; --scene-drift: -5vw;"></span>
                    <span class="golden-ember"
                        style="--scene-left: 85%; --scene-top: 51%; --scene-delay: -11s; --scene-duration: 20s; --scene-drift: 6vw;"></span>
                </div>
            </template>
        </div>
    </div>

    @php
        /*
         * Hide navigation while the student is actively
         * answering Practice or Diagnostic questions.
         */
        $isFocusMode = request()->routeIs(
            'practice.show',
            'practice.answer',
            'diagnostic.take',
            'diagnostic.answer',
            'mock-board.take',
            'mock-board.answer',
        );

        /*
         * Auth::user() is appropriate in this shared layout.
         * Route middleware still provides the actual security.
         */
        $authUser = Auth::user();

        /*
         * Levels only apply to student accounts.
         */
        if ($authUser?->role === 'student') {
            $authUser->loadMissing('level.tier');
        }

        /*
         * User display name.
         */
        $displayName = trim($authUser?->fullName() ?? '');

        if ($displayName === '') {
            $displayName = 'User';
        }

        /*
         * Build profile initials from the first and last
         * parts of the user's full name.
 */
$nameParts = preg_split('/\s+/', $displayName, -1, PREG_SPLIT_NO_EMPTY);
$firstName = $nameParts[0] ?? 'U';
$lastName = count($nameParts) > 1 ? $nameParts[array_key_last($nameParts)] : '';
$initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));

/*
 * Resolve Level and Tier using the CitiRx User relationship.
 */
$userLevel = $authUser?->role === 'student' ? $authUser->level : null;
$levelNumber = (int) ($userLevel?->level_number ?? ($authUser?->current_level ?? 1));
$tierName = strtolower($userLevel?->tier?->tier_name ?? 'beginner');

/*
 * Text shown below the user's name.
         */
        $profileLabel =
            $authUser?->role === 'student'
                ? $userLevel?->tier?->tier_name ?? 'Beginner'
                : ucfirst($authUser?->role ?? 'Reviewee');

        /*
         * Avatar border styling based on the student's tier.
 */
$tierRingClasses = match (true) {
    str_contains($tierName, 'expert')
        => 'ring-2 ring-primary border-primary/40 bg-primary-tint text-primary shadow-[0_0_10px_rgba(109,74,255,0.35)]',

    str_contains($tierName, 'advanced')
        => 'ring-2 ring-[#0EA5A4] border-[#0EA5A4]/40 bg-[#0EA5A4]/10 text-[#0E7A79] shadow-[0_0_10px_rgba(14,165,164,0.35)]',

    str_contains($tierName, 'intermediate')
        => 'ring-2 ring-[#F5A623] border-[#F5A623]/40 bg-[#F5A623]/10 text-[#B45309] shadow-[0_0_10px_rgba(245,166,35,0.3)]',

    default => 'ring-2 ring-slate-300 border-slate-200 bg-slate-100 text-slate-700',
};

/*
 * Small Level badge attached to the avatar.
 */
$levelBadgeBg = match (true) {
    str_contains($tierName, 'expert') => 'bg-primary text-white',
    str_contains($tierName, 'advanced') => 'bg-[#0EA5A4] text-white',
    str_contains($tierName, 'intermediate') => 'bg-[#F5A623] text-white',
    default => 'bg-slate-700 text-white',
        };
    @endphp

    <div class="relative z-10 min-h-screen flex flex-col md:flex-row">

        @if (!$isFocusMode)
            {{-- DESKTOP PERSISTENT LEFT SIDEBAR --}}
            <aside
                class="hidden md:flex md:w-64 md:flex-col md:fixed md:inset-y-0 z-30 border-r-2 border-slate-200 bg-white px-4 py-6 justify-between">
                <div class="space-y-6">

                    {{-- Logo --}}
                    <div class="px-3">
                        <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-2xl border-2 border-b-4 border-primary/30 bg-primary font-display text-xl font-extrabold text-white shadow-sm">
                                Rx
                            </div>
                            <div>
                                <span class="block font-display text-xl font-extrabold tracking-tight text-slate-900">
                                    CitiRx
                                </span>

                                <span class="block text-[10px] font-bold uppercase tracking-wider text-muted-ink">
                                    PhLE Adaptive Prep
                                </span>
                            </div>
                        </a>
                    </div>

                    {{-- Navigation Rail --}}
                    <nav class="space-y-1.5 font-display text-sm font-bold">
                        @php $active = request()->routeIs('dashboard'); @endphp
                        <a href="{{ route('dashboard') }}"
                            class="flex items-center gap-3.5 rounded-2xl px-4 py-3 transition-all {{ $active ? 'border-2 border-b-4 border-primary/30 bg-primary-tint/50 text-primary' : 'border-2 border-transparent text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                            <svg class="h-5 w-5 {{ $active ? 'text-primary' : 'text-slate-400' }}" fill="none"
                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                            </svg>
                            <span>Dashboard</span>
                        </a>

                        @if ($authUser?->role === 'student')
                            @php $active = request()->routeIs('practice.*'); @endphp
                            <a href="{{ route('practice.intro') }}"
                                class="flex items-center gap-3.5 rounded-2xl px-4 py-3 transition-all {{ $active ? 'border-2 border-b-4 border-primary/30 bg-primary-tint/50 text-primary' : 'border-2 border-transparent text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                                <svg class="h-5 w-5 {{ $active ? 'text-primary' : 'text-slate-400' }}" fill="none"
                                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15.59 14.37a6 6 0 0 1-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 0 0 6.16-12.12A14.98 14.98 0 0 0 9.631 8.41m5.96 5.96a14.926 14.926 0 0 1-5.841 2.58m-.119-8.54a6 6 0 0 0-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 0 0-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 0 1-2.448-2.448 14.9 14.9 0 0 1 .06-.312m-2.24 2.39a4.493 4.493 0 0 0-1.757 4.306 4.493 4.493 0 0 0 4.306-1.758M16.5 9a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z" />
                                </svg>
                                <span>Practice</span>
                            </a>

                            @php
                                $pearlsActive = request()->routeIs('pearls.*');
                            @endphp

                            <a href="{{ route('pearls.index') }}"
                                class="flex items-center gap-3.5 rounded-2xl px-4 py-3 transition-all
                                    {{ $pearlsActive
                                        ? 'border-2 border-b-4 border-primary/30 bg-primary-tint/50 text-primary'
                                        : 'border-2 border-transparent text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                                <svg class="h-5 w-5 {{ $pearlsActive ? 'text-primary' : 'text-slate-400' }}"
                                    fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                    aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 18.75a6 6 0 0 0 6-6c0-3.314-2.686-6-6-6s-6 2.686-6 6a6 6 0 0 0 6 6Zm0 0v2.25m-3 0h6M9.75 3h4.5" />
                                </svg>

                                <span>Micro Pearls</span>
                            </a>

                            @php
                                $leaderboardActive = request()->routeIs('leaderboard.*');
                            @endphp

                            <a href="{{ route('leaderboard.index') }}"
                                class="flex items-center gap-3.5 rounded-2xl px-4 py-3 transition-all
                                    {{ $leaderboardActive
                                        ? 'border-2 border-b-4 border-primary/30 bg-primary-tint/50 text-primary'
                                        : 'border-2 border-transparent text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                                <svg class="h-5 w-5 {{ $leaderboardActive ? 'text-primary' : 'text-slate-400' }}"
                                    fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                    aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M7.5 14.25v2.25m4.5-6v6m4.5-9v9M6 20.25h12A2.25 2.25 0 0 0 20.25 18V6A2.25 2.25 0 0 0 18 3.75H6A2.25 2.25 0 0 0 3.75 6v12A2.25 2.25 0 0 0 6 20.25Z" />
                                </svg>

                                <span>Leaderboard</span>
                            </a>

                            @php
                                $active = request()->routeIs('mock-board.*');
                            @endphp

                            <a href="{{ route('mock-board.intro') }}"
                                class="flex items-center gap-3.5 rounded-2xl px-4 py-3 transition-all
                                    {{ $active
                                        ? 'border-2 border-b-4 border-primary/30 bg-primary-tint/50 text-primary'
                                        : 'border-2 border-transparent text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                                <svg class="h-5 w-5 {{ $active ? 'text-primary' : 'text-slate-400' }}" fill="none"
                                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12.75 11.25 15 15 9.75M9 3.75h6m-6 0A2.25 2.25 0 0 0 6.75 6v12A2.25 2.25 0 0 0 9 20.25h6A2.25 2.25 0 0 0 17.25 18V6A2.25 2.25 0 0 0 15 3.75m-6 0v1.5h6v-1.5" />
                                </svg>

                                <span>Mock Board</span>
                            </a>

                            @php $active = request()->routeIs('progress.*'); @endphp
                            <a href="{{ route('progress.index') }}"
                                class="flex items-center gap-3.5 rounded-2xl px-4 py-3 transition-all {{ $active ? 'border-2 border-b-4 border-primary/30 bg-primary-tint/50 text-primary' : 'border-2 border-transparent text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                                <svg class="h-5 w-5 {{ $active ? 'text-primary' : 'text-slate-400' }}" fill="none"
                                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941" />
                                </svg>
                                <span>Progress</span>
                            </a>

                            @php $active = request()->routeIs('vault.*'); @endphp
                            <a href="{{ route('vault.index') }}"
                                class="flex items-center gap-3.5 rounded-2xl px-4 py-3 transition-all {{ $active ? 'border-2 border-b-4 border-primary/30 bg-primary-tint/50 text-primary' : 'border-2 border-transparent text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                                <svg class="h-5 w-5 {{ $active ? 'text-primary' : 'text-slate-400' }}" fill="none"
                                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                </svg>
                                <span>Rx Vault</span>
                            </a>

                            @php $active = request()->routeIs('readiness.*'); @endphp
                            <a href="{{ route('readiness.show') }}"
                                class="flex items-center gap-3.5 rounded-2xl px-4 py-3 transition-all {{ $active ? 'border-2 border-b-4 border-primary/30 bg-primary-tint/50 text-primary' : 'border-2 border-transparent text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                                <svg class="h-5 w-5 {{ $active ? 'text-primary' : 'text-slate-400' }}" fill="none"
                                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                                </svg>
                                <span>Readiness</span>
                            </a>

                            @php $active = request()->routeIs('badges.*'); @endphp
                            <a href="{{ route('badges.index') }}"
                                class="flex items-center gap-3.5 rounded-2xl px-4 py-3 transition-all {{ $active ? 'border-2 border-b-4 border-primary/30 bg-primary-tint/50 text-primary' : 'border-2 border-transparent text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                                <svg class="h-5 w-5 {{ $active ? 'text-primary' : 'text-slate-400' }}" fill="none"
                                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.003 0H9.497m5.003 0a7.476 7.476 0 0 0 1.996-3.874l.443-2.124a1.875 1.875 0 0 0-1.834-2.252h-1.07a4.875 4.875 0 0 0-4.068 2.193l-.467.7m0 0a4.875 4.875 0 0 0-4.068-2.193H4.427a1.875 1.875 0 0 0-1.834 2.252l.443 2.124c.338 1.62 1.096 3.09 2.196 4.174" />
                                </svg>
                                <span>Badges</span>
                            </a>
                        @endif

                        @if ($authUser?->role === 'faculty')
                            @php
                                $facultyDashboardActive = request()->routeIs('faculty.dashboard');
                            @endphp

                            <a href="{{ route('faculty.dashboard') }}"
                                class="flex items-center gap-3.5 rounded-2xl px-4 py-3 transition-all
                                    {{ $facultyDashboardActive
                                        ? 'border-2 border-b-4 border-primary/30 bg-primary-tint/50 text-primary'
                                        : 'border-2 border-transparent text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                                <span>Faculty Dashboard</span>
                            </a>

                            @php
                                $interventionsActive = request()->routeIs('faculty.interventions.*');
                            @endphp

                            <a href="{{ route('faculty.interventions.index') }}"
                                class="flex items-center gap-3.5 rounded-2xl px-4 py-3 transition-all
                                    {{ $interventionsActive
                                        ? 'border-2 border-b-4 border-primary/30 bg-primary-tint/50 text-primary'
                                        : 'border-2 border-transparent text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                                <span>Interventions</span>
                            </a>
                        @endif

                        @if ($authUser?->role === 'admin')
                            @php
                                $active = request()->routeIs('admin.research-analytics.*');
                            @endphp

                            <a href="{{ route('admin.research-analytics.index') }}"
                                class="flex items-center gap-3.5 rounded-2xl px-4 py-3 transition-all
                                    {{ $active
                                        ? 'border-2 border-b-4 border-primary/30 bg-primary-tint/50 text-primary'
                                        : 'border-2 border-transparent text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                                <svg class="h-5 w-5 {{ $active ? 'text-primary' : 'text-slate-400' }}" fill="none"
                                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                                </svg>

                                <span>Research Analytics</span>
                            </a>

                            @php
                                $questionBankActive = request()->routeIs('admin.questions.*');
                            @endphp

                            <a href="{{ route('admin.questions.index') }}"
                                class="flex items-center gap-3.5 rounded-2xl px-4 py-3 transition-all
                                    {{ $questionBankActive
                                        ? 'border-2 border-b-4 border-primary/30 bg-primary-tint/50 text-primary'
                                        : 'border-2 border-transparent text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                                <svg class="h-5 w-5 {{ $questionBankActive ? 'text-primary' : 'text-slate-400' }}"
                                    fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                    aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9.75 3.104v5.714a2.25 2.25 0 0 1-.659 1.591L5 14.5m4.75-11.396c.251-.023.501-.04.75-.054m-.75.054a24.55 24.55 0 0 0-5.623.769c-.653.163-1.127.74-1.127 1.413v5.714c0 .621.504 1.125 1.125 1.125h4.875m.75-9.021V3.75A2.25 2.25 0 0 0 12 6h4.5m-6.75-2.896c.251-.023.501-.04.75-.054M19.5 6.75v9.75A2.25 2.25 0 0 1 17.25 18.75h-9A2.25 2.25 0 0 1 6 16.5v-2.25" />
                                </svg>

                                <span>Question Bank</span>
                            </a>

                            <a href="{{ route('admin.research-forms.index') }}"
                                class="flex items-center gap-3.5 rounded-2xl px-4 py-3 transition-all
                                    {{ request()->routeIs('admin.research-forms.*')
                                        ? 'border-2 border-b-4 border-primary/30 bg-primary-tint/50 text-primary'
                                        : 'border-2 border-transparent text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                                <svg class="h-5 w-5 {{ request()->routeIs('admin.research-forms.*') ? 'text-primary' : 'text-slate-400' }}"
                                    fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                    aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12h6m-6 4h6m2.25 5H6.75A2.25 2.25 0 0 1 4.5 18.75V5.25A2.25 2.25 0 0 1 6.75 3h6.879c.597 0 1.17.237 1.591.659l2.121 2.121c.422.422.659.994.659 1.591V18.75A2.25 2.25 0 0 1 17.25 21Z" />
                                </svg>

                                <span>Research Forms</span>
                            </a>

                            <a href="{{ route('admin.users.index') }}"
                                class="flex items-center gap-3.5 rounded-2xl px-4 py-3 transition-all
                                    {{ request()->routeIs('admin.users.*')
                                        ? 'border-2 border-b-4 border-primary/30 bg-primary-tint/50 text-primary'
                                        : 'border-2 border-transparent text-slate-600 hover:bg-slate-100/80' }}">
                                <span>Users</span>
                            </a>

                            <a href="{{ route('admin.cohorts.index') }}"
                                class="flex items-center gap-3.5 rounded-2xl px-4 py-3 transition-all
                                    {{ request()->routeIs('admin.cohorts.*')
                                        ? 'border-2 border-b-4 border-primary/30 bg-primary-tint/50 text-primary'
                                        : 'border-2 border-transparent text-slate-600 hover:bg-slate-100/80' }}">
                                <span>Cohorts</span>
                            </a>

                            <a href="{{ route('admin.audit-logs.index') }}"
                                class="flex items-center gap-3.5 rounded-2xl px-4 py-3 transition-all
                                    {{ request()->routeIs('admin.audit-logs.*')
                                        ? 'border-2 border-b-4 border-primary/30 bg-primary-tint/50 text-primary'
                                        : 'border-2 border-transparent text-slate-600 hover:bg-slate-100/80' }}">
                                <span>Audit History</span>
                            </a>

                            @php
                                $pilotControlsActive = request()->routeIs('admin.pilot.*');
                            @endphp

                            <a href="{{ route('admin.pilot.index') }}"
                                class="flex items-center gap-3.5 rounded-2xl px-4 py-3 transition-all
                                    {{ $pilotControlsActive
                                        ? 'border-2 border-b-4 border-primary/30 bg-primary-tint/50 text-primary'
                                        : 'border-2 border-transparent text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                                <svg class="h-5 w-5 {{ $pilotControlsActive ? 'text-primary' : 'text-slate-400' }}"
                                    fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                    aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M6.75 3v2.25M17.25 3v2.25M3.75 8.25h16.5M5.25 4.5h13.5A1.5 1.5 0 0 1 20.25 6v13.5A1.5 1.5 0 0 1 18.75 21H5.25a1.5 1.5 0 0 1-1.5-1.5V6a1.5 1.5 0 0 1 1.5-1.5ZM9.75 12.75l1.5 1.5 3-3" />
                                </svg>

                                <span>Pilot Controls</span>
                            </a>
                        @endif
                    </nav>
                </div>

                {{-- User Profile Card with Dynamic Tier Avatar Ring --}}
                <div class="border-t border-slate-100 pt-3 px-1">
                    <div class="flex items-center justify-between gap-2">
                        <a href="{{ route('profile.edit') }}" title="Account Settings"
                            class="group flex flex-1 items-center gap-2.5 rounded-xl p-1.5 transition hover:bg-slate-100/80 min-w-0">

                            {{-- DYNAMIC TIER-BASED AVATAR RING & LEVEL BADGE --}}
                            <div class="relative shrink-0">
                                <div
                                    class="flex h-9 w-9 items-center justify-center rounded-xl border-2 border-b-4 font-display text-xs font-black transition-all {{ $tierRingClasses }}">
                                    {{ $initials }}
                                </div>
                                <span
                                    class="absolute -bottom-1 -right-1 flex h-4 min-w-4 px-1 items-center justify-center rounded-full font-mono text-[9px] font-black ring-2 ring-white shadow-xs {{ $levelBadgeBg }}">
                                    {{ $levelNumber }}
                                </span>
                            </div>

                            <div class="min-w-0 flex-1 text-left">
                                <span
                                    class="block font-display text-xs font-extrabold text-slate-900 truncate group-hover:text-primary transition">
                                    {{ $displayName }}
                                </span>
                                <span class="block text-[10px] font-semibold text-muted-ink capitalize truncate">
                                    {{ $profileLabel }}
                                </span>
                            </div>
                        </a>

                        <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                            @csrf
                            <button type="submit" title="Log Out"
                                class="rounded-xl border-2 border-slate-200 p-2 text-slate-400 hover:bg-slate-50 hover:text-slate-700 transition">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </aside>

            {{-- MOBILE BOTTOM NAVIGATION BAR --}}
            <nav aria-label="Mobile navigation"
                class="fixed inset-x-0 bottom-0 z-40 flex items-center border-t-2 border-slate-200
                    bg-white/95 py-2 shadow-lg backdrop-blur-md font-display text-[10px] font-bold md:hidden
                    {{ $authUser?->role === 'admin' ? 'justify-start gap-6 overflow-x-auto px-4' : 'justify-around px-2' }}">
                {{-- Home --}}
                <a href="{{ route('dashboard') }}"
                    class="flex flex-col items-center gap-1 {{ request()->routeIs('dashboard') ? 'text-primary' : 'text-slate-500' }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                    </svg>
                    <span>Home</span>
                </a>

                @if ($authUser?->role === 'student')
                    {{-- Practice --}}
                    <a href="{{ route('practice.intro') }}"
                        class="flex flex-col items-center gap-1 {{ request()->routeIs('practice.*') ? 'text-primary' : 'text-slate-500' }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15.59 14.37a6 6 0 0 1-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 0 0 6.16-12.12A14.98 14.98 0 0 0 9.631 8.41m5.96 5.96a14.926 14.926 0 0 1-5.841 2.58m-.119-8.54a6 6 0 0 0-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 0 0-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 0 1-2.448-2.448 14.9 14.9 0 0 1 .06-.312m-2.24 2.39a4.493 4.493 0 0 0-1.757 4.306 4.493 4.493 0 0 0 4.306-1.758M16.5 9a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z" />
                        </svg>
                        <span>Practice</span>
                    </a>

                    {{-- Progress --}}
                    <a href="{{ route('progress.index') }}"
                        class="flex flex-col items-center gap-1 {{ request()->routeIs('progress.*') ? 'text-primary' : 'text-slate-500' }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941" />
                        </svg>
                        <span>Progress</span>
                    </a>

                    {{-- Leaderboard --}}
                    <a href="{{ route('leaderboard.index') }}"
                        class="flex flex-col items-center gap-1
                            {{ request()->routeIs('leaderboard.*') ? 'text-primary' : 'text-slate-500' }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M7.5 14.25v2.25m4.5-6v6m4.5-9v9M6 20.25h12A2.25 2.25 0 0 0 20.25 18V6A2.25 2.25 0 0 0 18 3.75H6A2.25 2.25 0 0 0 3.75 6v12A2.25 2.25 0 0 0 6 20.25Z" />
                        </svg>

                        <span>Ranks</span>
                    </a>

                    {{-- Vault (Includes Bookmarks tab inside) --}}
                    <a href="{{ route('vault.index') }}"
                        class="flex flex-col items-center gap-1 {{ request()->routeIs('vault.*') ? 'text-primary' : 'text-slate-500' }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                        </svg>
                        <span>Vault</span>
                    </a>
                @endif

                @if ($authUser?->role === 'faculty')
                    <a href="{{ route('faculty.dashboard') }}"
                        class="flex flex-col items-center gap-1
                            {{ request()->routeIs('faculty.dashboard') ? 'text-primary' : 'text-slate-500' }}">
                        <span>Dashboard</span>
                    </a>

                    <a href="{{ route('faculty.interventions.index') }}"
                        class="flex flex-col items-center gap-1
                            {{ request()->routeIs('faculty.interventions.*') ? 'text-primary' : 'text-slate-500' }}">
                        <span>Interventions</span>
                    </a>
                @endif

                @if ($authUser?->role === 'admin')
                    {{-- Research Analytics --}}
                    <a href="{{ route('admin.research-analytics.index') }}"
                        class="flex min-w-[3.5rem] shrink-0 flex-col items-center gap-1
                            {{ request()->routeIs('admin.research-analytics.*') ? 'text-primary' : 'text-slate-500' }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                        </svg>

                        <span>Analytics</span>
                    </a>

                    {{-- Question Bank --}}
                    <a href="{{ route('admin.questions.index') }}"
                        class="flex min-w-[3.5rem] shrink-0 flex-col items-center gap-1
                            {{ request()->routeIs('admin.questions.*') ? 'text-primary' : 'text-slate-500' }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5A3.375 3.375 0 0 0 10.125 2.25H8.25m0 12.75h7.5m-7.5 3h4.5M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.625a9 9 0 0 0-9-9Z" />
                        </svg>

                        <span>Questions</span>
                    </a>

                    {{-- Research Form Readiness --}}
                    <a href="{{ route('admin.research-forms.index') }}"
                        class="flex min-w-[3.5rem] shrink-0 flex-col items-center gap-1
                            {{ request()->routeIs('admin.research-forms.*') ? 'text-primary' : 'text-slate-500' }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12h6m-6 4h6m2.25 5H6.75A2.25 2.25 0 0 1 4.5 18.75V5.25A2.25 2.25 0 0 1 6.75 3h6.879c.597 0 1.17.237 1.591.659l2.121 2.121c.422.422.659.994.659 1.591V18.75A2.25 2.25 0 0 1 17.25 21Z" />
                        </svg>

                        <span>Forms</span>
                    </a>

                    {{-- User Management --}}
                    <a href="{{ route('admin.users.index') }}"
                        class="flex min-w-[3.5rem] shrink-0 flex-col items-center gap-1
                            {{ request()->routeIs('admin.users.*') ? 'text-primary' : 'text-slate-500' }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.205-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>

                        <span>Users</span>
                    </a>

                    {{-- Cohort Management --}}
                    <a href="{{ route('admin.cohorts.index') }}"
                        class="flex min-w-[3.5rem] shrink-0 flex-col items-center gap-1
                            {{ request()->routeIs('admin.cohorts.*') ? 'text-primary' : 'text-slate-500' }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-4.5h6V21" />
                        </svg>

                        <span>Cohorts</span>
                    </a>

                    {{-- Administrative Audit History --}}
                    <a href="{{ route('admin.audit-logs.index') }}"
                        class="flex min-w-[3.5rem] shrink-0 flex-col items-center gap-1
                            {{ request()->routeIs('admin.audit-logs.*') ? 'text-primary' : 'text-slate-500' }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 6v6h4.5m4.5 0a9 9 0 1 1-9-9 9 9 0 0 1 9 9Z" />
                        </svg>

                        <span>Audits</span>
                    </a>

                    {{-- Official Pilot Controls --}}
                    <a href="{{ route('admin.pilot.index') }}"
                        class="flex min-w-[3.5rem] shrink-0 flex-col items-center gap-1
                            {{ request()->routeIs('admin.pilot.*') ? 'text-primary' : 'text-slate-500' }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M6.75 3v2.25M17.25 3v2.25M3.75 8.25h16.5M5.25 4.5h13.5A1.5 1.5 0 0 1 20.25 6v13.5A1.5 1.5 0 0 1 18.75 21H5.25a1.5 1.5 0 0 1-1.5-1.5V6a1.5 1.5 0 0 1 1.5-1.5ZM9.75 12.75l1.5 1.5 3-3" />
                        </svg>

                        <span>Pilot</span>
                    </a>
                @endif

                {{-- Profile --}}
                <a href="{{ route('profile.edit') }}"
                    class="flex flex-col items-center gap-1 {{ request()->routeIs('profile.*') ? 'text-primary' : 'text-slate-500' }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                    </svg>
                    <span>Profile</span>
                </a>
            </nav>
        @endif

        {{-- MAIN CONTENT AREA --}}
        <div class="flex-1 flex flex-col min-w-0 {{ !$isFocusMode ? 'md:pl-64 pb-16 md:pb-0' : '' }}">
            @isset($header)
                <header class="border-b-2 border-slate-200/80 bg-white/70 backdrop-blur-md sticky top-0 z-20">
                    <div class="max-w-7xl mx-auto py-3 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main class="flex-1">
                {{ $slot }}
            </main>
        </div>

    </div>
</body>

</html>
