<x-guest-layout fullscreen>
    <style>
        @keyframes capsule-float {

            0%,
            100% {
                transform: translateY(0) rotate(var(--r));
            }

            50% {
                transform: translateY(-14px) rotate(calc(var(--r) + 6deg));
            }
        }

        .capsule {
            animation: capsule-float 7s ease-in-out infinite;
        }

        .capsule:nth-child(2) {
            animation-delay: -2s;
            animation-duration: 9s;
        }

        .capsule:nth-child(3) {
            animation-delay: -4s;
            animation-duration: 8s;
        }

        .capsule:nth-child(4) {
            animation-delay: -1s;
            animation-duration: 10s;
        }

        @media (prefers-reduced-motion: reduce) {
            .capsule {
                animation: none;
            }
        }
    </style>

    {{-- Full-screen breakout container compatible across all devices --}}
    <div class="min-h-screen overflow-y-auto bg-slate-50 lg:bg-white text-slate-800">
        <div class="min-h-full grid grid-cols-1 lg:grid-cols-[0.80fr_1fr]">

            {{-- ============ LEFT: brand + what happens next (Desktop / Large Screens) ============ --}}
            <aside class="relative hidden lg:flex flex-col justify-between overflow-hidden bg-[#4A2FC4] p-8 xl:p-12 text-white min-h-screen">

                {{-- Floating capsules background --}}
                <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                    <div class="capsule absolute -top-4 right-16 h-10 w-28 rounded-full bg-[linear-gradient(90deg,#fbbf24_50%,#ffffff_50%)] opacity-90 shadow-lg" style="--r:-28deg"></div>
                    <div class="capsule absolute top-1/3 -right-6 h-9 w-24 rounded-full bg-[linear-gradient(90deg,#34d399_50%,#ffffff_50%)] opacity-80 shadow-lg" style="--r:35deg"></div>
                    <div class="capsule absolute bottom-24 -left-6 h-10 w-28 rounded-full bg-[linear-gradient(90deg,#fb7185_50%,#ffffff_50%)] opacity-80 shadow-lg" style="--r:20deg"></div>
                    <div class="capsule absolute bottom-6 right-24 h-8 w-20 rounded-full bg-[linear-gradient(90deg,#38bdf8_50%,#ffffff_50%)] opacity-80 shadow-lg" style="--r:-15deg"></div>
                </div>

                {{-- Brand Header --}}
                <a href="{{ route('login') }}" class="relative flex w-fit items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-indigo-600 font-display text-xl font-extrabold shadow-lg rotate-[-6deg]">
                        Rx
                    </div>
                    <div>
                        <p class="font-display text-2xl font-extrabold leading-none">CitiRx</p>
                        <p class="mt-1 text-xs font-medium text-indigo-100">PHLE adaptive prep</p>
                    </div>
                </a>

                {{-- Password Recovery Steps Showcase --}}
                <div class="relative max-w-lg my-auto py-8">
                    <h2 class="font-display text-4xl xl:text-5xl font-extrabold leading-[1.08] tracking-tight">
                        Locked out? You'll be back in shortly.
                    </h2>
                    <p class="mt-4 text-sm xl:text-base text-indigo-100">
                        Your progress and question history are safe. Resetting your password takes three quick steps.
                    </p>

                    <ol class="mt-8 space-y-3">
                        @foreach ([
                            ['Enter your email', 'Use the address you registered with.'],
                            ['Open the reset link', 'It arrives in your inbox within a few minutes.'],
                            ['Choose a new password', 'Then sign in and continue reviewing.'],
                        ] as $i => [$title, $text])
                            <li class="flex items-start gap-4 rounded-2xl bg-white/15 p-4 backdrop-blur-sm ring-1 ring-white/20">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white font-display text-sm font-extrabold text-indigo-600">{{ $i + 1 }}</span>
                                <div>
                                    <p class="font-semibold text-white text-sm xl:text-base">{{ $title }}</p>
                                    <p class="text-xs xl:text-sm text-indigo-100">{{ $text }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>

                <p class="relative text-xs text-indigo-100">Built for future pharmacists.</p>
            </aside>

            {{-- ============ RIGHT: reset form ============ --}}
            <main class="flex flex-col items-center justify-center px-10 py-12 lg:px-12 xl:px-16 bg-slate-50 lg:bg-white min-h-screen">
                
                {{-- Mobile / Tablet Brand Header --}}
                <div class="mb-8 flex items-center gap-3 lg:hidden">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-600 to-fuchsia-600 text-white font-display text-xl font-extrabold shadow-lg rotate-[-6deg]">
                        Rx
                    </div>
                    <div>
                        <p class="font-display text-2xl font-extrabold leading-none text-slate-900">CitiRx</p>
                        <p class="mt-1 text-xs font-medium text-slate-400">PHLE adaptive prep</p>
                    </div>
                </div>

                <div class="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-8 shadow-xl shadow-indigo-100/60 lg:border-0 lg:p-0 lg:shadow-none">
                    <div class="mb-7">
                        <h1 class="font-display text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">Reset your password</h1>
                        <p class="mt-2 text-sm text-slate-500">Enter your email and we'll send you a link to create a new password.</p>
                    </div>

                    @if (session('status'))
                        <div class="mb-5 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-700" role="status">
                            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                            <span>{{ session('status') }}</span>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.email') }}" class="space-y-5"
                        x-data="{ busy: false }"
                        @submit="busy = true"
                        @pageshow.window="busy = false">
                        @csrf

                        <div>
                            <label for="email" class="mb-2 block text-sm font-semibold text-slate-700">Email address</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
                                </span>
                                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50 py-3 pl-12 pr-4 text-sm transition placeholder:text-slate-400 focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-100"
                                    placeholder="student@citirx.edu">
                            </div>
                            @error('email') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <button type="submit"
                            :disabled="busy"
                            style="--lip: #4A2FC4;"
                            class="btn-press flex w-full items-center justify-center rounded-2xl bg-indigo-600 px-7 py-3.5 font-display text-base font-bold text-white shadow-md hover:bg-indigo-700 disabled:opacity-70 focus:outline-none focus-visible:ring-4 focus-visible:ring-indigo-200">
                            <span x-text="busy ? 'Sending Link…' : 'Send reset link'">Send reset link</span>
                        </button>
                    </form>

                    <p class="mt-8 text-center text-sm text-slate-500">
                        Remembered it?
                        <a href="{{ route('login') }}" class="font-bold text-indigo-600 hover:text-fuchsia-600">Back to sign in</a>
                    </p>
                </div>
            </main>
        </div>
    </div>
</x-guest-layout>
