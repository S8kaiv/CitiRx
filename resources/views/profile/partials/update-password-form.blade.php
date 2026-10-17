<section>
    <header>
        <h2 class="font-display text-lg font-black text-slate-900">
            {{ __('Update Password') }}
        </h2>

        <p class="mt-1 text-sm leading-relaxed text-muted-ink">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('put')

        <div>
            <x-input-label for="update_password_current_password" :value="__('Current Password')" class="block font-display text-xs font-bold uppercase tracking-wider text-slate-700" />
            <x-text-input id="update_password_current_password" name="current_password" type="password" class="mt-1 block w-full rounded-xl border-2 border-slate-200 bg-white px-3.5 py-2.5 text-sm font-semibold text-slate-900 shadow-xs transition focus:border-primary focus:ring-0" autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password" :value="__('New Password')" class="block font-display text-xs font-bold uppercase tracking-wider text-slate-700" />
            <x-text-input id="update_password_password" name="password" type="password" class="mt-1 block w-full rounded-xl border-2 border-slate-200 bg-white px-3.5 py-2.5 text-sm font-semibold text-slate-900 shadow-xs transition focus:border-primary focus:ring-0" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" :value="__('Confirm Password')" class="block font-display text-xs font-bold uppercase tracking-wider text-slate-700" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full rounded-xl border-2 border-slate-200 bg-white px-3.5 py-2.5 text-sm font-semibold text-slate-900 shadow-xs transition focus:border-primary focus:ring-0" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" style="--lip: rgb(var(--color-primary-lip));"
                class="btn-press inline-flex items-center justify-center rounded-xl border-2 border-primary bg-primary px-5 py-2.5 font-display text-xs font-black uppercase tracking-wider text-white shadow-sm transition hover:bg-primary/95 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                {{ __('Update Password') }}
            </button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    role="status"
                    class="rounded-xl border-2 border-primary/25 bg-primary-tint/60 px-3 py-2 font-display text-xs font-bold text-primary"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
