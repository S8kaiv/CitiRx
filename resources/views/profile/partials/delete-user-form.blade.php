<section class="space-y-5">
    <header>
        <h2 class="font-display text-lg font-black text-weak-ink">
            {{ __('Delete Account') }}
        </h2>

        <p class="mt-1 text-sm leading-relaxed text-muted-ink">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
        </p>
    </header>

    <button type="button" x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
        class="btn-press inline-flex items-center justify-center rounded-xl border-2 border-rose-600 bg-rose-500 px-5 py-2.5 font-display text-xs font-black uppercase tracking-wider text-white shadow-sm transition hover:bg-rose-600 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2"
        style="--lip: #9f1239;">
        {{ __('Delete Account') }}
    </button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="space-y-5 p-6">
            @csrf
            @method('delete')

            <h2 class="font-display text-lg font-black text-slate-900">
                {{ __('Are you sure you want to delete your account?') }}
            </h2>

            <p class="text-sm leading-relaxed text-muted-ink">
                {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
            </p>

            <div>
                <x-input-label for="password" :value="__('Password')" class="block font-display text-xs font-bold uppercase tracking-wider text-slate-700" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-full rounded-xl border-2 border-slate-200 bg-white px-3.5 py-2.5 text-sm font-semibold text-slate-900 shadow-xs transition focus:border-primary focus:ring-0 sm:w-3/4"
                    placeholder="{{ __('Password') }}"
                    autocomplete="current-password"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button type="button" x-on:click="$dispatch('close')"
                    class="btn-press inline-flex items-center justify-center rounded-xl border-2 border-b-4 border-slate-200 bg-white px-5 py-2.5 font-display text-xs font-black uppercase tracking-wider text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
                    style="--lip: #cbd5e1;">
                    {{ __('Cancel') }}
                </button>

                <button type="submit"
                    class="btn-press inline-flex items-center justify-center rounded-xl border-2 border-rose-600 bg-rose-500 px-5 py-2.5 font-display text-xs font-black uppercase tracking-wider text-white shadow-sm transition hover:bg-rose-600 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2"
                    style="--lip: #9f1239;">
                    {{ __('Delete Account') }}
                </button>
            </div>
        </form>
    </x-modal>
</section>
