<x-auth-card
    :title="__('Confirm your password')"
    :subtitle="__('This is a protected area. Confirm your password to continue.')"
>
    <form
        method="POST"
        action="{{ route('password.confirm') }}"
        class="space-y-5"
        x-data="{ busy: false }"
        x-on:submit="busy = true"
        x-on:pageshow.window="busy = false"
    >
        @csrf

        <x-auth-field
            id="password"
            :label="__('Password')"
            type="password"
            icon="lock"
            :toggle="true"
            required
            :autofocus="true"
            autocomplete="current-password"
            :placeholder="__('Your password')"
        />

        <x-auth-button
            :label="__('Confirm')"
            :busy-label="__('Confirming…')"
        />
    </form>
</x-auth-card>
