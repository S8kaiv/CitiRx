<section>
    <header>
        <h2 class="font-display text-lg font-black text-slate-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm leading-relaxed text-muted-ink">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="first_name" :value="__('First Name')" class="block font-display text-xs font-bold uppercase tracking-wider text-slate-700" />
            <x-text-input id="first_name" name="first_name" type="text" class="mt-1 block w-full rounded-xl border-2 border-slate-200 bg-white px-3.5 py-2.5 text-sm font-semibold text-slate-900 shadow-sm transition focus:border-primary focus:ring-0" :value="old('first_name', $user->first_name)"
                required autofocus autocomplete="given-name" />
            <x-input-error class="mt-2" :messages="$errors->get('first_name')" />
        </div>

        <div>
            <x-input-label for="middle_name" :value="__('Middle Name')" class="block font-display text-xs font-bold uppercase tracking-wider text-slate-700" />
            <x-text-input id="middle_name" name="middle_name" type="text" class="mt-1 block w-full rounded-xl border-2 border-slate-200 bg-white px-3.5 py-2.5 text-sm font-semibold text-slate-900 shadow-sm transition focus:border-primary focus:ring-0" :value="old('middle_name', $user->middle_name)"
                autocomplete="additional-name" />
            <x-input-error class="mt-2" :messages="$errors->get('middle_name')" />
        </div>

        <div>
            <x-input-label for="last_name" :value="__('Last Name')" class="block font-display text-xs font-bold uppercase tracking-wider text-slate-700" />
            <x-text-input id="last_name" name="last_name" type="text" class="mt-1 block w-full rounded-xl border-2 border-slate-200 bg-white px-3.5 py-2.5 text-sm font-semibold text-slate-900 shadow-sm transition focus:border-primary focus:ring-0" :value="old('last_name', $user->last_name)"
                required autocomplete="family-name" />
            <x-input-error class="mt-2" :messages="$errors->get('last_name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" class="block font-display text-xs font-bold uppercase tracking-wider text-slate-700" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full rounded-xl border-2 border-slate-200 bg-white px-3.5 py-2.5 text-sm font-semibold text-slate-900 shadow-sm transition focus:border-primary focus:ring-0" :value="old('email', $user->email)"
                required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !$user->hasVerifiedEmail())
                <div>
                    <p                     class="mt-2 text-sm text-slate-700">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification"
                            class="rounded-lg font-semibold text-primary underline decoration-primary/40 underline-offset-2 transition hover:text-primary-lip focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-display text-xs font-bold text-strong-ink">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" style="--lip: rgb(var(--color-primary-lip));"
                class="btn-press inline-flex items-center justify-center rounded-xl border-2 border-primary bg-primary px-5 py-2.5 font-display text-xs font-black uppercase tracking-wider text-white shadow-sm transition hover:bg-primary/95 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                {{ __('Save Changes') }}
            </button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
                    role="status"
                    class="rounded-xl border-2 border-primary/25 bg-primary-tint/60 px-3 py-2 font-display text-xs font-bold text-primary">{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
