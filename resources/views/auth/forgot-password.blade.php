<x-auth-split-layout
    :title="__('Reset your password')"
    :subtitle="__('Enter your email and CitiRx will send you a password-reset link.')"
    :headline="__('Locked out? You will be back shortly.')"
    :description="__('Your learning progress remains connected to your account while you reset its password.')"
    :ordered="true"
    :items="[
        [
            'title' => __('Enter your email'),
            'description' => __('Use the address registered with CitiRx.'),
        ],
        [
            'title' => __('Open the reset link'),
            'description' => __('Check your inbox and follow the secure link.'),
        ],
        [
            'title' => __('Choose a new password'),
            'description' => __('Return to CitiRx and continue reviewing.'),
        ],
    ]"
>
    @if (session('status'))
        <div
            role="status"
            class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800"
        >
            {{ session('status') }}
        </div>
    @endif

    <form
        method="POST"
        action="{{ route('password.email') }}"
        class="space-y-5"
        x-data="{ busy: false }"
        x-on:submit="busy = true"
        x-on:pageshow.window="busy = false"
    >
        @csrf

        <x-auth-field
            id="email"
            :label="__('Email address')"
            type="email"
            icon="mail"
            :value="old('email')"
            required
            :autofocus="true"
            autocomplete="email"
            placeholder="you@gmail.com"
        />

        <x-auth-button
            :label="__('Send reset link')"
            :busy-label="__('Sending link…')"
        />
    </form>

    <p class="mt-8 text-center text-sm text-slate-600">
        {{ __('Remembered your password?') }}

        <a
            href="{{ route('login') }}"
            class="font-bold text-primary underline-offset-4 hover:text-primary-lip hover:underline"
        >
            {{ __('Back to sign in') }}
        </a>
    </p>
</x-auth-split-layout>
