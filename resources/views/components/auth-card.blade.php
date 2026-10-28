@props([
    'title',
    'subtitle' => null,
])

<x-guest-layout fullscreen>
    <main
        class="auth-screen flex items-center justify-center bg-canvas px-4 py-8 sm:px-6"
    >
        <div class="w-full max-w-md">
            <a
                href="{{ route('login') }}"
                class="mb-8 flex w-fit items-center gap-3 rounded-xl focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/25"
            >
                <span
                    class="flex h-12 w-12 -rotate-6 items-center justify-center rounded-2xl bg-primary font-display text-xl font-black text-white shadow-lg"
                    aria-hidden="true"
                >
                    Rx
                </span>

                <span>
                    <span class="block font-display text-2xl font-black leading-none text-slate-900">
                        CitiRx
                    </span>

                    <span class="mt-1 block text-xs font-semibold text-slate-600">
                        PhLE adaptive preparation
                    </span>
                </span>
            </a>

            <section class="rounded-2xl bg-white p-6 shadow-xl shadow-slate-200/70 sm:p-8">
                <header class="mb-6">
                    <h1 class="font-display text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">
                        {{ $title }}
                    </h1>

                    @if ($subtitle)
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">
                            {{ $subtitle }}
                        </p>
                    @endif
                </header>

                {{ $slot }}
            </section>
        </div>
    </main>
</x-guest-layout>
