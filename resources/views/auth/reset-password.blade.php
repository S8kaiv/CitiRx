<x-auth-card
    :title="__('Choose a new password')"
    :subtitle="__('Choose a password you have not used for another account.')"
>
    <form
        method="POST"
        action="{{ route('password.store') }}"
        class="space-y-5"
        x-data="{ busy: false }"
        x-on:submit="busy = true"
        x-on:pageshow.window="busy = false"
    >
        @csrf

        <input
            type="hidden"
            name="token"
            value="{{ $request->route('token') }}"
        >

        <x-auth-field
            id="email"
            :label="__('Email address')"
            type="email"
            icon="mail"
            :value="old('email', $request->email)"
            required
            autocomplete="username"
        />

        <x-auth-field
            id="password"
            :label="__('New password')"
            type="password"
            icon="lock"
            :toggle="true"
            required
            :autofocus="true"
            autocomplete="new-password"
            :placeholder="__('Create a password')"
        />

        <x-auth-field
            id="password_confirmation"
            :label="__('Confirm new password')"
            type="password"
            icon="shield"
            :toggle="true"
            required
            autocomplete="new-password"
            :placeholder="__('Confirm your password')"
        />

        <x-auth-button
            :label="__('Reset password')"
            :busy-label="__('Resetting password…')"
        />
    </form>
</x-auth-card>
