<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-[#FAF8FF]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'CitiRx') }} - PhLE Review</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans text-slate-900 antialiased selection:bg-primary-tint selection:text-primary relative">

    {{-- ============================================================ --}}
    {{-- ATMOSPHERIC CANVAS: VIOLET AMBIENT GLOW + CLINICAL DOTS      --}}
    {{-- ============================================================ --}}
    <div class="fixed inset-0 pointer-events-none overflow-hidden -z-10" aria-hidden="true">
        <div class="absolute inset-0 opacity-[0.035]" 
             style="background-image: radial-gradient(#4A2FC4 1.2px, transparent 1.2px); background-size: 22px 22px;">
        </div>

        <div class="absolute -top-24 right-10 h-[36rem] w-[36rem] rounded-full bg-[#6D4AFF]/14 blur-[120px]"></div>
        <div class="absolute -bottom-24 -left-10 h-[32rem] w-[32rem] rounded-full bg-[#4A2FC4]/10 blur-[130px]"></div>
        <div class="absolute top-1/2 left-1/3 h-[26rem] w-[26rem] rounded-full bg-[#0EA5A4]/8 blur-[140px]"></div>
    </div>

    @php
        /*
         * Hide navigation while the student is actively
         * answering Practice or Diagnostic questions.
         */
        $isFocusMode = request()->routeIs('practice.show', 'practice.answer', 'diagnostic.take', 'diagnostic.answer');

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
        * Resolve Level and Tier using the actual CitiRx
        * User relationship.
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

    <div class="min-h-screen flex flex-col md:flex-row">

        @if (!$isFocusMode)
            {{-- DESKTOP PERSISTENT LEFT SIDEBAR --}}
            <aside class="hidden md:flex md:w-64 md:flex-col md:fixed md:inset-y-0 z-30 border-r-2 border-slate-200 bg-white px-4 py-6 justify-between">
                <div class="space-y-6">
                    
                    {{-- Logo --}}
                    <div class="px-3">
                        <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl border-2 border-b-4 border-primary/30 bg-primary font-display text-xl font-extrabold text-white shadow-sm">
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
                            <svg class="h-5 w-5 {{ $active ? 'text-primary' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                            </svg>
                            <span>Dashboard</span>
                        </a>

                        @if ($authUser?->role === 'student')
                            @php $active = request()->routeIs('practice.*'); @endphp
                            <a href="{{ route('practice.intro') }}"
                               class="flex items-center gap-3.5 rounded-2xl px-4 py-3 transition-all {{ $active ? 'border-2 border-b-4 border-primary/30 bg-primary-tint/50 text-primary' : 'border-2 border-transparent text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                                <svg class="h-5 w-5 {{ $active ? 'text-primary' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 0 1-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 0 0 6.16-12.12A14.98 14.98 0 0 0 9.631 8.41m5.96 5.96a14.926 14.926 0 0 1-5.841 2.58m-.119-8.54a6 6 0 0 0-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 0 0-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 0 1-2.448-2.448 14.9 14.9 0 0 1 .06-.312m-2.24 2.39a4.493 4.493 0 0 0-1.757 4.306 4.493 4.493 0 0 0 4.306-1.758M16.5 9a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z" />
                                </svg>
                                <span>Practice</span>
                            </a>

                            @php $active = request()->routeIs('progress.*'); @endphp
                            <a href="{{ route('progress.index') }}"
                               class="flex items-center gap-3.5 rounded-2xl px-4 py-3 transition-all {{ $active ? 'border-2 border-b-4 border-primary/30 bg-primary-tint/50 text-primary' : 'border-2 border-transparent text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                                <svg class="h-5 w-5 {{ $active ? 'text-primary' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941" />
                                </svg>
                                <span>Progress</span>
                            </a>

                            @php $active = request()->routeIs('vault.*') && request('tab') !== 'bookmarks'; @endphp
                            <a href="{{ route('vault.index') }}"
                               class="flex items-center gap-3.5 rounded-2xl px-4 py-3 transition-all {{ $active ? 'border-2 border-b-4 border-primary/30 bg-primary-tint/50 text-primary' : 'border-2 border-transparent text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                                <svg class="h-5 w-5 {{ $active ? 'text-primary' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                </svg>
                                <span>Rx Vault</span>
                            </a>

                            @php $active = request()->routeIs('readiness.*'); @endphp
                            <a href="{{ route('readiness.show') }}"
                               class="flex items-center gap-3.5 rounded-2xl px-4 py-3 transition-all {{ $active ? 'border-2 border-b-4 border-primary/30 bg-primary-tint/50 text-primary' : 'border-2 border-transparent text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                                <svg class="h-5 w-5 {{ $active ? 'text-primary' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                                </svg>
                                <span>Readiness</span>
                            </a>

                            @php $active = request()->routeIs('badges.*'); @endphp
                            <a href="{{ route('badges.index') }}"
                               class="flex items-center gap-3.5 rounded-2xl px-4 py-3 transition-all {{ $active ? 'border-2 border-b-4 border-primary/30 bg-primary-tint/50 text-primary' : 'border-2 border-transparent text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                                <svg class="h-5 w-5 {{ $active ? 'text-primary' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.003 0H9.497m5.003 0a7.476 7.476 0 0 0 1.996-3.874l.443-2.124a1.875 1.875 0 0 0-1.834-2.252h-1.07a4.875 4.875 0 0 0-4.068 2.193l-.467.7m0 0a4.875 4.875 0 0 0-4.068-2.193H4.427a1.875 1.875 0 0 0-1.834 2.252l.443 2.124c.338 1.62 1.096 3.09 2.196 4.174" />
                                </svg>
                                <span>Badges</span>
                            </a>
                        @endif
                    </nav>
                </div>

                {{-- User Profile Card with Dynamic Tier Avatar Ring --}}
                <div class="border-t border-slate-100 pt-3 px-1">
                    <div class="flex items-center justify-between gap-2">
                        <a href="{{ route('profile.edit') }}" 
                           title="Account Settings"
                           class="group flex flex-1 items-center gap-2.5 rounded-xl p-1.5 transition hover:bg-slate-100/80 min-w-0">
                            
                            {{-- DYNAMIC TIER-BASED AVATAR RING & LEVEL BADGE --}}
                            <div class="relative shrink-0">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl border-2 border-b-4 font-display text-xs font-black transition-all {{ $tierRingClasses }}">
                                    {{ $initials }}
                                </div>
                                <span class="absolute -bottom-1 -right-1 flex h-4 min-w-4 px-1 items-center justify-center rounded-full font-mono text-[9px] font-black ring-2 ring-white shadow-xs {{ $levelBadgeBg }}">
                                    {{ $levelNumber }}
                                </span>
                            </div>

                            <div class="min-w-0 flex-1 text-left">
                                <span class="block font-display text-xs font-extrabold text-slate-900 truncate group-hover:text-primary transition">
                                    {{ $displayName }}
                                </span>
                                <span class="block text-[10px] font-semibold text-muted-ink capitalize truncate">
                                    {{ $profileLabel }}
                                </span>
                            </div>
                        </a>

                        <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                            @csrf
                            <button type="submit" 
                                    title="Log Out"
                                    class="rounded-xl border-2 border-slate-200 p-2 text-slate-400 hover:bg-slate-50 hover:text-slate-700 transition">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </aside>

            {{-- MOBILE BOTTOM NAVIGATION BAR --}}
            <nav class="fixed bottom-0 inset-x-0 z-40 flex md:hidden items-center justify-around border-t-2 border-slate-200 bg-white/95 backdrop-blur-md py-2 px-2 shadow-lg font-display text-[10px] font-bold">
                <a href="{{ route('dashboard') }}" class="flex flex-col items-center gap-1 {{ request()->routeIs('dashboard') ? 'text-primary' : 'text-slate-500' }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                    </svg>
                    <span>Home</span>
                </a>

                @if ($authUser?->role === 'student')
                    <a href="{{ route('practice.intro') }}" class="flex flex-col items-center gap-1 {{ request()->routeIs('practice.*') ? 'text-primary' : 'text-slate-500' }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 0 1-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 0 0 6.16-12.12A14.98 14.98 0 0 0 9.631 8.41m5.96 5.96a14.926 14.926 0 0 1-5.841 2.58m-.119-8.54a6 6 0 0 0-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 0 0-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 0 1-2.448-2.448 14.9 14.9 0 0 1 .06-.312m-2.24 2.39a4.493 4.493 0 0 0-1.757 4.306 4.493 4.493 0 0 0 4.306-1.758M16.5 9a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z" />
                        </svg>
                        <span>Practice</span>
                    </a>

                    <a href="{{ route('progress.index') }}" class="flex flex-col items-center gap-1 {{ request()->routeIs('progress.*') ? 'text-primary' : 'text-slate-500' }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941" />
                        </svg>
                        <span>Progress</span>
                    </a>

                    <a href="{{ route('vault.index') }}" class="flex flex-col items-center gap-1 {{ request()->routeIs('vault.*') && request('tab') !== 'bookmarks' ? 'text-primary' : 'text-slate-500' }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                        </svg>
                        <span>Vault</span>
                    </a>

                    <a href="{{ route('vault.index', ['tab' => 'bookmarks']) }}" class="flex flex-col items-center gap-1 {{ request()->routeIs('vault.*') && request('tab') === 'bookmarks' ? 'text-primary' : 'text-slate-500' }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                        </svg>
                        <span>Saved</span>
                    </a>
                @endif

                <a href="{{ route('profile.edit') }}" class="flex flex-col items-center gap-1 {{ request()->routeIs('profile.*') ? 'text-primary' : 'text-slate-500' }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
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
