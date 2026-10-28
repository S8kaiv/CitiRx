<x-auth-split-layout
    :title="__('Create an account')"
    :subtitle="__('Sign up to begin your personalized board review.')"
    :headline="__('Start your journey toward becoming a registered pharmacist.')"
    :description="__('Use adaptive practice to focus on weaker topics and build confidence across the PhLE domains.')"
    :items="[
        [
            'icon' => 'target',
            'title' => __('Adaptive practice'),
            'description' => __('Focus your review using current mastery estimates.'),
        ],
        [
            'icon' => 'chart',
            'title' => __('Progress analytics'),
            'description' => __('Track readiness and competency development.'),
        ],
        [
            'icon' => 'book',
            'title' => __('Reviewed learning content'),
            'description' => __('Study questions and lessons organized by PhLE domain.'),
        ],
    ]"
>
    @php
        $visibleFields = [
            'first_name',
            'middle_name',
            'last_name',
            'email',
            'password',
            'password_confirmation',
        ];

        $otherErrors = collect($errors->keys())
            ->diff($visibleFields);
    @endphp

    @if ($otherErrors->isNotEmpty())
        <div
            role="alert"
            class="mb-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-800"
        >
            <ul class="list-disc space-y-1 pl-5">
                @foreach ($otherErrors as $key)
                    <li>{{ $errors->first($key) }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form
        method="POST"
        action="{{ route('register') }}"
        class="space-y-5"
        x-data="{ busy: false }"
        x-on:submit="busy = true"
        x-on:pageshow.window="busy = false"
    >
        @csrf

        <div class="grid gap-4 sm:grid-cols-2">
            <x-auth-field
                id="first_name"
                :label="__('First name')"
                :value="old('first_name')"
                maxlength="100"
                required
                :autofocus="true"
                autocomplete="given-name"
                placeholder="Juan"
            />

            <x-auth-field
                id="middle_name"
                :label="__('Middle name')"
                :hint="__('Optional')"
                :value="old('middle_name')"
                maxlength="100"
                autocomplete="additional-name"
                placeholder="Santos"
            />
        </div>

        <x-auth-field
            id="last_name"
            :label="__('Last name')"
            :value="old('last_name')"
            maxlength="100"
            required
            autocomplete="family-name"
            placeholder="Dela Cruz"
        />

        <x-auth-field
            id="email"
            :label="__('Email address')"
            type="email"
            icon="mail"
            :value="old('email')"
            :hint="__('Use an address you can access. Temporary and disposable email services are not accepted.')"
            required
            autocomplete="email"
            placeholder="you@gmail.com"
        />

        <x-auth-field
            id="password"
            :label="__('Password')"
            type="password"
            icon="lock"
            :toggle="true"
            required
            autocomplete="new-password"
            :placeholder="__('Create a password')"
        />

        <x-auth-field
            id="password_confirmation"
            :label="__('Confirm password')"
            type="password"
            icon="shield"
            :toggle="true"
            required
            autocomplete="new-password"
            :placeholder="__('Confirm your password')"
        />

        <x-auth-button
            :label="__('Create account')"
            :busy-label="__('Creating account…')"
        />
    </form>

    <p class="mt-8 text-center text-sm text-slate-600">
        {{ __('Already have an account?') }}

        <a
            href="{{ route('login') }}"
            class="font-bold text-primary underline-offset-4 hover:text-primary-lip hover:underline"
        >
            {{ __('Sign in') }}
        </a>
    </p>
</x-auth-split-layout>
