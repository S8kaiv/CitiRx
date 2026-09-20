<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-display text-xl font-bold text-slate-900 leading-tight">
                    {{ __('Account Settings') }}
                </h2>
                <p class="text-xs font-semibold uppercase tracking-wider text-muted-ink">
                    Manage Profile & Security
                </p>
            </div>

            <a href="{{ route('dashboard') }}" 
               class="font-display text-xs font-bold text-muted-ink hover:text-primary transition">
                Back to Dashboard
            </a>
        </div>
    </x-slot>

    @php
        $user = $user ?? Auth::user();

        // Teammate's standardized full name and initials resolution
        $displayName = method_exists($user, 'fullName') ? trim($user->fullName()) : trim($user->name ?? 'User');
        if ($displayName === '') {
            $displayName = 'User';
        }

        $nameParts = preg_split('/\s+/', $displayName, -1, PREG_SPLIT_NO_EMPTY);
        $firstName = $nameParts[0] ?? 'U';
        $lastName = count($nameParts) > 1 ? $nameParts[array_key_last($nameParts)] : '';
        $initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
    @endphp

    <div class="pt-6 pb-16 font-sans text-slate-900 antialiased">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 space-y-6">

            {{-- 1. Student Identity Crest --}}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 rounded-2xl border-2 border-b-4 border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border-2 border-b-4 border-primary/30 bg-primary-tint font-display text-xl font-extrabold text-primary shadow-sm">
                        {{ $initials }}
                    </div>
                    <div>
                        <h3 class="font-display text-lg font-bold text-slate-900">
                            {{ $displayName }}
                        </h3>
                        <p class="text-xs text-muted-ink font-medium">
                            {{ $user->email }}
                            @if ($user->cohort)
                                · <span class="font-semibold text-slate-700">{{ $user->cohort->cohort_name }}</span>
                            @endif
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    {{-- Teammate's corrected Level -> Tier relationship check --}}
                    <span class="rounded-full border border-primary/30 bg-primary-tint px-3 py-1 font-display text-xs font-bold text-primary">
                        @if ($user->role === 'student')
                            {{ $user->level?->tier?->tier_name ?? 'Beginner' }} Tier
                        @else
                            {{ ucfirst($user->role) }}
                        @endif
                    </span>
                    <span class="rounded-full border border-[#F5A623]/40 bg-gold-tint px-3 py-1 font-display text-xs font-bold text-gold-ink">
                        {{ number_format($user->total_xp ?? 0) }} XP
                    </span>
                </div>
            </div>

            {{-- 2. Profile Details Form --}}
            <div class="rounded-2xl border-2 border-b-4 border-slate-200 bg-white p-6 sm:p-8 shadow-sm">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            {{-- 3. Password / Security Form --}}
            <div class="rounded-2xl border-2 border-b-4 border-slate-200 bg-white p-6 sm:p-8 shadow-sm">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            {{-- 4. Danger Zone (Delete Account) --}}
            <div class="rounded-2xl border-2 border-b-4 border-weak/30 bg-white p-6 sm:p-8 shadow-sm">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>

        </div>
    </div>
</x-app-layout>