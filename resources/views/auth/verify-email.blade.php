<x-guest-layout fullscreen>
    <main
        class="flex min-h-screen items-center justify-center bg-canvas px-4 py-8 sm:px-6 lg:px-8"
    >
        <div class="w-full max-w-lg">
            <div class="mb-8 flex items-center justify-center gap-4">
                <div
                    class="flex h-16 w-16 -rotate-6 items-center justify-center rounded-3xl bg-primary font-display text-2xl font-black text-white shadow-lg"
                    aria-hidden="true"
                >
                    Rx
                </div>

                <div>
                    <p class="font-display text-3xl font-black leading-none text-slate-900">
                        CitiRx
                    </p>

                    <p class="mt-1.5 text-sm font-semibold text-muted-ink">
                        {{ __('PhLE adaptive preparation') }}
                    </p>
                </div>
            </div>

            <section
                aria-labelledby="verification-heading"
                class="rounded-[2rem] border-2 border-slate-200 bg-white p-6 shadow-xl sm:p-10"
            >
                <div class="mb-6 flex justify-center">
                    <div
                        class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-primary-tint text-primary"
                        aria-hidden="true"
                    >
                        <svg
                            class="h-8 w-8"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M21.75 6.75v10.5A2.25 2.25 0 0 1 19.5 19.5h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"
                            />
                        </svg>
                    </div>
                </div>

                <div class="text-center">
                    <h1
                        id="verification-heading"
                        class="font-display text-3xl font-black tracking-tight text-slate-900 sm:text-4xl"
                    >
                        {{ __('Check your inbox') }}
                    </h1>

                    <p class="mx-auto mt-4 max-w-md text-sm leading-relaxed text-muted-ink sm:text-base">
                        {{ __('Before continuing, verify your email address using the link CitiRx sent to your inbox.') }}
                    </p>
                </div>

                @if (session('status') === 'verification-link-sent')
                    <div
                        class="mt-8 flex items-start gap-3 rounded-xl border-2 border-emerald-200 bg-emerald-50 p-4 text-left text-sm font-semibold text-emerald-800"
                        role="status"
                        aria-live="polite"
                    >
                        <svg
                            class="mt-0.5 h-5 w-5 shrink-0"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                            aria-hidden="true"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="m4.5 12.75 6 6 9-13.5"
                            />
                        </svg>

                        <span>
                            {{ __('A new verification link has been sent to your email address.') }}
                        </span>
                    </div>
                @endif

                <div class="mt-8 space-y-4">
                    <form
                        method="POST"
                        action="{{ route('verification.send') }}"
                        x-data="{ busy: false }"
                        x-on:submit="busy = true"
                        x-on:pageshow.window="busy = false"
                    >
                        @csrf

                        <button
                            type="submit"
                            x-bind:disabled="busy"
                            class="btn-press flex w-full items-center justify-center gap-2 rounded-2xl border-2 border-primary bg-primary px-6 py-3.5 font-display text-sm font-black uppercase tracking-wider text-white shadow-md transition hover:bg-primary/95 focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/25 disabled:cursor-wait disabled:opacity-70"
                            style="--lip: rgb(var(--color-primary-lip));"
                        >
                            <svg
                                x-cloak
                                x-show="busy"
                                class="h-4 w-4 animate-spin motion-reduce:animate-none"
                                viewBox="0 0 24 24"
                                fill="none"
                                aria-hidden="true"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                ></circle>

                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"
                                ></path>
                            </svg>

                            <span
                                x-text="busy
                                    ? @js(__('Sending…'))
                                    : @js(__('Resend Verification Email'))"
                            >
                                {{ __('Resend Verification Email') }}
                            </span>
                        </button>
                    </form>

                    <form
                        method="POST"
                        action="{{ route('logout') }}"
                    >
                        @csrf

                        <button
                            type="submit"
                            class="w-full rounded-2xl border-2 border-slate-200 bg-white px-6 py-3.5 font-display text-sm font-black uppercase tracking-wider text-slate-700 transition hover:bg-slate-50 focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/25"
                        >
                            {{ __('Log Out') }}
                        </button>
                    </form>
                </div>

                <p class="mx-auto mt-8 max-w-sm text-center text-sm leading-relaxed text-muted-ink">
                    {{ __("Didn't receive the email? Check your spam folder, then request another link.") }}
                </p>
            </section>
        </div>
    </main>
</x-guest-layout>
