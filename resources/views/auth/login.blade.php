<x-auth-split-layout
    :title="__('Welcome back')"
    :subtitle="__('Sign in to continue your pharmacy board review.')"
    :headline="__('Build a smarter path toward PhLE readiness.')"
    :description="__('Continue your diagnostic, targeted practice, progress tracking, and competency review.')"
    :items="[
        [
            'icon' => 'target',
            'title' => __('Targeted practice'),
            'description' => __('Focus on competencies that need more work.'),
        ],
        [
            'icon' => 'chart',
            'title' => __('Readiness tracking'),
            'description' => __('See how your competency mastery changes over time.'),
        ],
        [
            'icon' => 'book',
            'title' => __('Structured PhLE review'),
            'description' => __('Review content organized around the official domains.'),
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
        action="{{ route('login') }}"
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
        <p class="mt-8 text-center text-sm text-slate-600">
            {{ __('New to CitiRx?') }}

            <a
                href="{{ route('register') }}"
                class="font-bold text-primary underline-offset-4 hover:text-primary-lip hover:underline"
            >
                {{ __('Create an account') }}
            </a>
        </p>
    @endif
</x-auth-split-layout>
