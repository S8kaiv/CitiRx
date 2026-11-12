<x-rx-auth-layout
    title="Let’s get you back in."
    subtitle="Enter your registered email and we’ll send you a password-reset link."
    headline="A fresh start, without starting over."
    description="Your learning progress stays connected to your account while you reset your password."
    bubble="We’ve got your back."
>
    @if (session('status'))
        <div
            role="status" aria-live="polite"
            class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800"
        >
            {{ session('status') }}
        </div>
    @endif

    <form
        method="POST"
        action="{{ route('password.email') }}"
        class="rx-auth-form space-y-5"
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

    <p class="rx-auth-switch mt-8 text-center text-sm text-slate-600">
        {{ __('Remembered your password?') }}

        <a
            href="{{ route('login') }}"
            class="font-bold text-primary underline-offset-4 hover:text-primary-lip hover:underline"
        >
            {{ __('Back to sign in') }}
        </a>
    </p>
</x-rx-auth-layout>
