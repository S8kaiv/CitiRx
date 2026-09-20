<x-guest-layout>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,800&display=swap" rel="stylesheet">

    <style>
        .font-display { font-family: 'Baloo 2', sans-serif; }

        @keyframes capsule-float {
            0%, 100% { transform: translateY(0) rotate(var(--r)); }
            50%      { transform: translateY(-14px) rotate(calc(var(--r) + 6deg)); }
        }
        .capsule { animation: capsule-float 7s ease-in-out infinite; }
        .capsule:nth-child(2) { animation-delay: -2s; animation-duration: 9s; }
        .capsule:nth-child(3) { animation-delay: -4s; animation-duration: 8s; }
        .capsule:nth-child(4) { animation-delay: -1s; animation-duration: 10s; }

        @media (prefers-reduced-motion: reduce) {
            .capsule { animation: none; }
        }
    </style>

    {{-- Full-screen breakout container compatible across all devices --}}
    <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-50 lg:bg-white text-slate-800">
        <div class="min-h-full grid grid-cols-1 lg:grid-cols-[0.80fr_1fr]">

            {{-- ============ LEFT: brand + live feature highlights (Desktop / Large Screens) ============ --}}
            <aside class="relative hidden lg:flex flex-col justify-start overflow-hidden bg-gradient-to-br from-indigo-600 via-violet-600 to-fuchsia-600 p-8 xl:p-12 text-white min-h-screen">

                {{-- Floating capsules --}}
                <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                    <div class="capsule absolute -top-4 right-16 h-10 w-28 rounded-full bg-[linear-gradient(90deg,#fbbf24_50%,#ffffff_50%)] opacity-90 shadow-lg" style="--r:-28deg"></div>
                    <div class="capsule absolute top-1/3 -right-6 h-9 w-24 rounded-full bg-[linear-gradient(90deg,#34d399_50%,#ffffff_50%)] opacity-80 shadow-lg" style="--r:35deg"></div>
                    <div class="capsule absolute bottom-24 -left-6 h-10 w-28 rounded-full bg-[linear-gradient(90deg,#fb7185_50%,#ffffff_50%)] opacity-80 shadow-lg" style="--r:20deg"></div>
                    <div class="capsule absolute bottom-6 right-24 h-8 w-20 rounded-full bg-[linear-gradient(90deg,#38bdf8_50%,#ffffff_50%)] opacity-80 shadow-lg" style="--r:-15deg"></div>
                    <div class="absolute -bottom-32 -left-24 h-96 w-96 rounded-full bg-white/10 blur-3xl"></div>
                </div>

                {{-- Brand Header --}}
                <div class="relative flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-indigo-600 font-display text-xl font-extrabold shadow-lg rotate-[-6deg]">
                        Rx
                    </div>
                    <div>
                        <p class="font-display text-2xl font-extrabold leading-none">CitiRx</p>
                        <p class="mt-1 text-xs font-medium text-indigo-100">PHLE adaptive prep</p>
                    </div>
                </div>

                {{-- Headline + Features showcase --}}
                <div class="relative max-w-lg mt-10 xl:mt-9">
                    <h2 class="font-display text-4xl xl:text-4xl font-extrabold leading-[1.08] tracking-tight">
                        Start your journey to becoming a registered pharmacist.
                    </h2>
                    <p class="mt-4 text-sm xl:text-base text-indigo-100">
                        Join thousands of PHLE candidates using adaptive spaced repetition to master high-yield topics faster.
                    </p>

                    <div class="mt-8 space-y-3">
                        <div class="flex items-center gap-4 rounded-2xl bg-white/10 p-4 backdrop-blur-md border border-white/10">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/20 text-white font-bold">
                                ⚡
                            </div>
                            <div>
                                <p class="font-display text-sm font-bold text-white">Adaptive Learning Engine</p>
                                <p class="text-xs text-indigo-100">Tailors quiz difficulty based on your strengths and weaknesses.</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 rounded-2xl bg-white/10 p-4 backdrop-blur-md border border-white/10">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/20 text-white font-bold">
                                📊
                            </div>
                            <div>
                                <p class="font-display text-sm font-bold text-white">Real-Time Performance Analytics</p>
                                <p class="text-xs text-indigo-100">Track subject readiness and predicted board exam scores.</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 rounded-2xl bg-white/10 p-4 backdrop-blur-md border border-white/10">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/20 text-white font-bold">
                                💊
                            </div>
                            <div>
                                <p class="font-display text-sm font-bold text-white">Comprehensive Question Bank</p>
                                <p class="text-xs text-indigo-100">Thousands of rationale-backed questions across all PHLE modules.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <p class="relative mt-auto pt-8 text-xs text-indigo-100">Built for future pharmacists.</p>
            </aside>

            {{-- ============ RIGHT: register form ============ --}}
            <main class="flex flex-col items-center justify-center px-6 py-12 lg:px-12 xl:px-16 bg-slate-50 lg:bg-white min-h-screen">

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
                        <h1 class="font-display text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">Create an account</h1>
                        <p class="mt-2 text-sm text-slate-500">Sign up to begin your personalized board review.</p>
                    </div>

                    {{-- Native Validation Errors --}}
                    @if ($errors->any())
                        <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 p-4 text-xs font-semibold text-rose-700">
                            <div class="font-bold">Whoops! Something went wrong.</div>
                            <ul class="mt-2 list-disc pl-5 space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('register') }}" class="space-y-4" x-data="{ showPass: false, showConfirm: false }">
                        @csrf

                        {{-- Name --}}
                        <div>
                            <label for="name" class="mb-1.5 block text-sm font-semibold text-slate-700">Full name</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                                </span>
                                <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50 py-3 pl-12 pr-4 text-sm transition placeholder:text-slate-400 focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-100"
                                    placeholder="Juan Dela Cruz">
                            </div>
                        </div>

                        {{-- Email Address --}}
                        <div>
                            <label for="email" class="mb-1.5 block text-sm font-semibold text-slate-700">Email address</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
                                </span>
                                <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50 py-3 pl-12 pr-4 text-sm transition placeholder:text-slate-400 focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-100"
                                    placeholder="student@citirx.edu">
                            </div>
                        </div>

                        {{-- Password --}}
                        <div>
                            <label for="password" class="mb-1.5 block text-sm font-semibold text-slate-700">Password</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                                </span>
                                <input id="password" :type="showPass ? 'text' : 'password'" name="password" required autocomplete="new-password"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50 py-3 pl-12 pr-16 text-sm transition placeholder:text-slate-400 focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-100"
                                    placeholder="Create a password">
                                <button type="button" @click="showPass = !showPass"
                                    class="absolute inset-y-0 right-0 px-4 text-xs font-semibold text-slate-500 hover:text-indigo-600 focus:outline-none focus-visible:text-indigo-600"
                                    x-text="showPass ? 'Hide' : 'Show'">Show</button>
                            </div>
                        </div>

                        {{-- Confirm Password --}}
                        <div>
                            <label for="password_confirmation" class="mb-1.5 block text-sm font-semibold text-slate-700">Confirm password</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751A11.959 11.959 0 0 1 12 2.714Z"/></svg>
                                </span>
                                <input id="password_confirmation" :type="showConfirm ? 'text' : 'password'" name="password_confirmation" required autocomplete="new-password"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50 py-3 pl-12 pr-16 text-sm transition placeholder:text-slate-400 focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-100"
                                    placeholder="Confirm password">
                                <button type="button" @click="showConfirm = !showConfirm"
                                    class="absolute inset-y-0 right-0 px-4 text-xs font-semibold text-slate-500 hover:text-indigo-600 focus:outline-none focus-visible:text-indigo-600"
                                    x-text="showConfirm ? 'Hide' : 'Show'">Show</button>
                            </div>
                        </div>

                        <button type="submit"
                            class="mt-2 w-full rounded-xl bg-gradient-to-r from-indigo-600 to-fuchsia-600 py-3.5 text-sm font-bold text-white shadow-lg shadow-indigo-200 transition hover:shadow-xl hover:shadow-fuchsia-200 hover:brightness-110 focus:outline-none focus-visible:ring-4 focus-visible:ring-indigo-200 active:scale-[0.99]">
                            Create Account
                        </button>
                    </form>

                    <p class="mt-8 text-center text-sm text-slate-500">
                        Already have an account?
                        <a href="{{ route('login') }}" class="font-bold text-indigo-600 hover:text-fuchsia-600">Sign in</a>
                    </p>
                </div>
            </main>
        </div>
    </div>
</x-guest-layout>