@php($isStudent = auth()->user()->role === 'student')

<x-auth-card
    :title="$isStudent ? __('Email verification pending') : __('Check your inbox')"
    :subtitle="$isStudent
        ? __('An administrator will verify your email address in CitiRx. Return here to check your status.')
        : __('Verify your email using the link CitiRx sent to you.')"
>
    @if ($isStudent)
        <x-ui.button :href="route('verification.notice')">
            Check verification status
        </x-ui.button>
    @else
        @if (session('status') === 'verification-link-sent')
            <x-ui.alert variant="success" class="mb-4">
                A new verification link has been sent to your email address.
            </x-ui.alert>
        @endif

        <form method="POST" action="{{ route('verification.send') }}"
            x-data="{ busy: false }" x-on:submit="busy = true" x-on:pageshow.window="busy = false">
            @csrf
            <x-auth-button
                :label="__('Resend verification email')"
                :busy-label="__('Sending verification email…')"
            />
        </form>
    @endif

    <form method="POST" action="{{ route('logout') }}" class="mt-4">
        @csrf
        <x-ui.button type="submit" variant="secondary">Log out</x-ui.button>
    </form>
</x-auth-card>
