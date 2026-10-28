<x-auth-card
    :title="__('Check your inbox')"
    :subtitle="__('Before continuing, verify your email address using the link CitiRx sent to you.')"
>
    @if (session('status') === 'verification-link-sent')
        <div
            role="status"
            class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800"
        >
            {{ __('A new verification link has been sent to your email address.') }}
        </div>
    @endif

    <form
        method="POST"
        action="{{ route('verification.send') }}"
        x-data="{ busy: false }"
        x-on:submit="busy = true"
        x-on:pageshow.window="busy = false"
    >
        @csrf

        <x-auth-button
            :label="__('Resend verification email')"
            :busy-label="__('Sending verification email…')"
        />
    </form>

    <form
        method="POST"
        action="{{ route('logout') }}"
        class="mt-4"
    >
        @csrf

        <button
            type="submit"
            class="min-h-11 w-full rounded-xl px-4 py-2 text-sm font-bold text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/25"
        >
            {{ __('Log out') }}
        </button>
    </form>
</x-auth-card>
