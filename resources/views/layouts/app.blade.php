<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'CitiRx') }} - PhLE Review</title>

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    {{-- Scripts & Styles --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans text-slate-900 antialiased selection:bg-primary-tint selection:text-primary">

    @php
        // Active focus mode during timed or active question answering
        $isFocusMode = request()->routeIs('practice.show', 'practice.answer', 'diagnostic.take', 'diagnostic.answer');

        // Dynamic User Name & Initials Extraction
        $authUser = Auth::user();
        $displayName = 'Student';
        if ($authUser) {
            if (method_exists($authUser, 'fullName') && !empty($authUser->fullName())) {
                $displayName = $authUser->fullName();
            } elseif (!empty($authUser->name)) {
                $displayName = $authUser->name;
            } elseif (!empty($authUser->first_name) || !empty($authUser->last_name)) {
                $displayName = trim(($authUser->first_name ?? '') . ' ' . ($authUser->last_name ?? ''));
            }
        }

        $nameParts = array_values(array_filter(explode(' ', trim($displayName))));
        $initials = count($nameParts) >= 2
            ? strtoupper(substr($nameParts[0], 0, 1) . substr(end($nameParts), 0, 1))
            : strtoupper(substr($displayName, 0, 2));
    @endphp

    <div class="min-h-screen flex flex-col md:flex-row">

        @if (!$isFocusMode)
            {{-- DESKTOP PERSISTENT LEFT SIDEBAR --}}
            <aside class="hidden md:flex md:w-64 md:flex-col md:fixed md:inset-y-0 z-30 border-r-2 border-slate-200 bg-white px-4 py-6 justify-between">
                <div class="space-y-6">
                    
                    {{-- Logo & Brand --}}
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
                        
                        {{-- 1. Dashboard / Home --}}
                        @php $active = request()->routeIs('dashboard'); @endphp
                        <a href="{{ route('dashboard') }}"
                           class="flex items-center gap-3.5 rounded-2xl px-4 py-3 transition-all {{ $active ? 'border-2 border-b-4 border-primary/30 bg-primary-tint/50 text-primary' : 'border-2 border-transparent text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                            <svg class="h-5 w-5 {{ $active ? 'text-primary' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                            </svg>
                            <span>Dashboard</span>
                        </a>

                        {{-- 2. Practice Hub --}}
                        @php $active = request()->routeIs('practice.*'); @endphp
                        <a href="{{ route('practice.intro') }}"
                           class="flex items-center gap-3.5 rounded-2xl px-4 py-3 transition-all {{ $active ? 'border-2 border-b-4 border-primary/30 bg-primary-tint/50 text-primary' : 'border-2 border-transparent text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                            <svg class="h-5 w-5 {{ $active ? 'text-primary' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 0 1-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 0 0 6.16-12.12A14.98 14.98 0 0 0 9.631 8.41m5.96 5.96a14.926 14.926 0 0 1-5.841 2.58m-.119-8.54a6 6 0 0 0-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 0 0-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 0 1-2.448-2.448 14.9 14.9 0 0 1 .06-.312m-2.24 2.39a4.493 4.493 0 0 0-1.757 4.306 4.493 4.493 0 0 0 4.306-1.758M16.5 9a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z" />
                            </svg>
                            <span>Practice</span>
                        </a>

                        {{-- 3. Rx Vault --}}
                        @php $active = request()->routeIs('vault.*'); @endphp
                        <a href="{{ route('vault.index') }}"
                           class="flex items-center gap-3.5 rounded-2xl px-4 py-3 transition-all {{ $active ? 'border-2 border-b-4 border-primary/30 bg-primary-tint/50 text-primary' : 'border-2 border-transparent text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                            <svg class="h-5 w-5 {{ $active ? 'text-primary' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                            </svg>
                            <span>Rx Vault</span>
                        </a>

                        {{-- 4. Readiness Report --}}
                        @php $active = request()->routeIs('readiness.*'); @endphp
                        <a href="{{ route('readiness.show') }}"
                           class="flex items-center gap-3.5 rounded-2xl px-4 py-3 transition-all {{ $active ? 'border-2 border-b-4 border-primary/30 bg-primary-tint/50 text-primary' : 'border-2 border-transparent text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                            <svg class="h-5 w-5 {{ $active ? 'text-primary' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                            </svg>
                            <span>Readiness</span>
                        </a>

                        {{-- 5. Badges & Achievements --}}
                        @php $active = request()->routeIs('badges.*'); @endphp
                        <a href="{{ route('badges.index') }}"
                           class="flex items-center gap-3.5 rounded-2xl px-4 py-3 transition-all {{ $active ? 'border-2 border-b-4 border-primary/30 bg-primary-tint/50 text-primary' : 'border-2 border-transparent text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                            <svg class="h-5 w-5 {{ $active ? 'text-primary' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.003 0H9.497m5.003 0a7.476 7.476 0 0 0 1.996-3.874l.443-2.124a1.875 1.875 0 0 0-1.834-2.252h-1.07a4.875 4.875 0 0 0-4.068 2.193l-.467.7m0 0a4.875 4.875 0 0 0-4.068-2.193H4.427a1.875 1.875 0 0 0-1.834 2.252l.443 2.124c.338 1.62 1.096 3.09 2.196 4.174" />
                            </svg>
                            <span>Badges</span>
                        </a>
                    </nav>
                </div>

                {{-- User Profile Card (Prominent Student Name) --}}
                <div class="border-t border-slate-100 pt-3 px-1">
                    <div class="flex items-center justify-between gap-2">
                        <a href="{{ route('profile.edit') }}" 
                           title="Account Settings"
                           class="group flex flex-1 items-center gap-2.5 rounded-xl p-1.5 transition hover:bg-slate-100/80 min-w-0">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border-2 border-b-4 border-primary/20 bg-primary-tint font-display text-xs font-extrabold text-primary shadow-sm">
                                {{ $initials }}
                            </div>
                            <div class="min-w-0 flex-1 text-left">
                                <span class="block font-display text-xs font-extrabold text-slate-900 truncate group-hover:text-primary transition">
                                    {{ $displayName }}
                                </span>
                                <span class="block text-[10px] font-semibold text-muted-ink capitalize truncate">
                                    {{ $authUser->role ?? 'Reviewee' }}
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
            <nav class="fixed bottom-0 inset-x-0 z-40 flex md:hidden items-center justify-around border-t-2 border-slate-200 bg-white py-2 px-3 shadow-lg font-display text-[10px] font-bold">
                <a href="{{ route('dashboard') }}" class="flex flex-col items-center gap-1 {{ request()->routeIs('dashboard') ? 'text-primary' : 'text-slate-500' }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                    </svg>
                    <span>Home</span>
                </a>

                <a href="{{ route('practice.intro') }}" class="flex flex-col items-center gap-1 {{ request()->routeIs('practice.*') ? 'text-primary' : 'text-slate-500' }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 0 1-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 0 0 6.16-12.12A14.98 14.98 0 0 0 9.631 8.41m5.96 5.96a14.926 14.926 0 0 1-5.841 2.58m-.119-8.54a6 6 0 0 0-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 0 0-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 0 1-2.448-2.448 14.9 14.9 0 0 1 .06-.312m-2.24 2.39a4.493 4.493 0 0 0-1.757 4.306 4.493 4.493 0 0 0 4.306-1.758M16.5 9a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z" />
                    </svg>
                    <span>Practice</span>
                </a>

                <a href="{{ route('vault.index') }}" class="flex flex-col items-center gap-1 {{ request()->routeIs('vault.*') ? 'text-primary' : 'text-slate-500' }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                    <span>Vault</span>
                </a>

                <a href="{{ route('readiness.show') }}" class="flex flex-col items-center gap-1 {{ request()->routeIs('readiness.*') ? 'text-primary' : 'text-slate-500' }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                    </svg>
                    <span>Report</span>
                </a>

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
            
            {{-- Optional Top Header Slot --}}
            @isset($header)
                <header class="border-b-2 border-slate-200/80 bg-white/80 backdrop-blur-sm sticky top-0 z-20">
                    <div class="max-w-7xl mx-auto py-3 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            {{-- Body Slot --}}
            <main class="flex-1">
                {{ $slot }}
            </main>
        </div>

    </div>
</body>
</html>