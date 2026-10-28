@props([
    'title',
    'subtitle',
    'headline',
    'description',
    'items' => [],
    'ordered' => false,
])

<x-guest-layout fullscreen>
    <div class="auth-screen grid bg-canvas lg:grid-cols-[0.8fr_1fr]">
        <aside
            class="relative hidden overflow-hidden bg-primary p-8 text-white lg:flex lg:flex-col xl:p-12"
        >
            <div
                class="pointer-events-none absolute inset-0"
                aria-hidden="true"
            >
                <div
                    class="capsule absolute -top-4 right-16 h-10 w-28 rounded-full"
                    style="--r: -28deg; --capsule-start: #f5a623;"
                ></div>

                <div
                    class="capsule absolute -right-6 top-1/3 h-9 w-24 rounded-full"
                    style="--r: 35deg; --capsule-start: #22c55e;"
                ></div>

                <div
                    class="capsule absolute -left-6 bottom-24 h-10 w-28 rounded-full"
                    style="--r: 20deg; --capsule-start: #f0524f;"
                ></div>
            </div>

            <a
                href="{{ route('login') }}"
                class="relative flex w-fit items-center gap-3 rounded-xl focus:outline-none focus-visible:ring-4 focus-visible:ring-white/40"
            >
                <span
                    class="flex h-12 w-12 -rotate-6 items-center justify-center rounded-2xl bg-white font-display text-xl font-black text-primary shadow-lg"
                    aria-hidden="true"
                >
                    Rx
                </span>

                <span>
                    <span class="block font-display text-2xl font-black leading-none">
                        CitiRx
                    </span>

                    <span class="mt-1 block text-xs font-semibold text-primary-tint">
                        PhLE adaptive preparation
                    </span>
                </span>
            </a>

            <div class="relative my-auto max-w-lg py-8">
                <h2 class="font-display text-4xl font-black leading-[1.08] tracking-tight xl:text-5xl">
                    {{ $headline }}
                </h2>

                <p class="mt-4 text-sm leading-relaxed text-primary-tint xl:text-base">
                    {{ $description }}
                </p>

                @if ($ordered)
                    <ol class="mt-8 space-y-3">
                        @foreach ($items as $item)
                            <li class="flex items-start gap-4 rounded-2xl bg-white/10 p-4 ring-1 ring-white/15">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white font-display text-sm font-black text-primary">
                                    {{ $loop->iteration }}
                                </span>

                                <span>
                                    <span class="block text-sm font-bold text-white">
                                        {{ $item['title'] }}
                                    </span>

                                    <span class="mt-1 block text-xs leading-relaxed text-primary-tint">
                                        {{ $item['description'] }}
                                    </span>
                                </span>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <ul class="mt-8 space-y-3">
                        @foreach ($items as $item)
                            <li class="flex items-start gap-4 rounded-2xl bg-white/10 p-4 ring-1 ring-white/15">
                                <span
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/15"
                                    aria-hidden="true"
                                >
                                    <svg
                                        class="h-5 w-5"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                    >
                                        @switch($item['icon'] ?? null)
                                            @case('chart')
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M4 19V10m6 9V5m6 14v-7m4 7H2"
                                                />
                                                @break

                                            @case('book')
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M4.5 5.75A2.75 2.75 0 0 1 7.25 3H12v16.5H7.25A2.75 2.75 0 0 0 4.5 22V5.75Zm15 0A2.75 2.75 0 0 0 16.75 3H12v16.5h4.75A2.75 2.75 0 0 1 19.5 22V5.75Z"
                                                />
                                                @break

                                            @default
                                                <circle cx="12" cy="12" r="8.5" />
                                                <circle cx="12" cy="12" r="4.5" />
                                                <circle cx="12" cy="12" r="1.25" />
                                        @endswitch
                                    </svg>
                                </span>

                                <span>
                                    <span class="block text-sm font-bold text-white">
                                        {{ $item['title'] }}
                                    </span>

                                    <span class="mt-1 block text-xs leading-relaxed text-primary-tint">
                                        {{ $item['description'] }}
                                    </span>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <p class="relative text-xs font-semibold text-primary-tint">
                Built for future pharmacists.
            </p>
        </aside>

        <main class="flex items-center justify-center bg-canvas px-4 py-10 sm:px-6 lg:bg-white lg:px-12 xl:px-16">
            <div class="w-full max-w-md">
                <a
                    href="{{ route('login') }}"
                    class="mb-8 flex w-fit items-center gap-3 rounded-xl focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/25 lg:hidden"
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

                <section class="rounded-2xl bg-white p-6 shadow-xl shadow-slate-200/70 sm:p-8 lg:rounded-none lg:p-0 lg:shadow-none">
                    <header class="mb-7">
                        <h1 class="font-display text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">
                            {{ $title }}
                        </h1>

                        <p class="mt-2 text-sm leading-relaxed text-slate-600">
                            {{ $subtitle }}
                        </p>
                    </header>

                    {{ $slot }}
                </section>
            </div>
        </main>
    </div>
</x-guest-layout>
