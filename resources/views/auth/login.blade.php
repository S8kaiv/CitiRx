<x-rx-auth-layout
    title="Welcome back."
    subtitle="Your next step is waiting."
    headline="A little practice today. More confidence tomorrow."
    description="Pick up your review with targeted practice and a clearer view of your progress."
    bubble="Ready when you are."
>
    @if (session('status'))
        <div
            role="status" aria-live="polite"
            class="mb-5 rounded-xl border border-success-strong/30 bg-success-tint p-4 text-sm font-semibold text-success-ink"
        >
            {{ session('status') }}
        </div>
    @endif

    <form
        method="POST"
        action="{{ route('login') }}"
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
            autocomplete="username"
            placeholder="you@gmail.com"
        />

        <x-auth-field
            id="password"
            :label="__('Password')"
            type="password"
            icon="lock"
            :toggle="true"
            required
            autocomplete="current-password"
            :action-href="Route::has('password.request')
                ? route('password.request')
                : null"
            :action-label="__('Forgot password?')"
            :placeholder="__('Your password')"
        />

        <label class="flex min-h-11 cursor-pointer items-center gap-3 rounded-xl px-1">
            <input
                type="checkbox"
                name="remember"
                class="h-5 w-5 rounded border-slate-300 text-primary focus:ring-primary"
            >

            <span class="text-sm text-slate-700">
                {{ __('Keep me signed in on this device') }}
            </span>
        </label>

        <x-auth-button
            :label="__('Sign in')"
            :busy-label="__('Signing in…')"
        />
    </form>

    @if (Route::has('register'))
        <p class="rx-auth-switch mt-8 text-center text-sm text-slate-600">
            {{ __('New to CitiRx?') }}

            <a
                href="{{ route('register') }}"
                class="font-bold text-primary-ink underline-offset-4 hover:text-primary-ink hover:underline"
            >
                {{ __('Create an account') }}
            </a>
        </p>
    @endif
</x-rx-auth-layout>
