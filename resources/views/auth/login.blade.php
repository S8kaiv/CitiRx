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

            {{-- ============ LEFT: brand + live sample question (Desktop / Large Screens) ============ --}}
            <aside class="relative hidden lg:flex flex-col justify-between overflow-hidden bg-gradient-to-br from-indigo-600 via-violet-600 to-fuchsia-600 p-8 xl:p-12 text-white min-h-screen">

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

                {{-- Headline + interactive sample --}}
                <div class="relative max-w-lg my-auto py-8">
                    <h2 class="font-display text-4xl xl:text-4xl font-extrabold leading-[1.08] tracking-tight">
                        Every question you answer gets you closer to your license.
                    </h2>
                    <p class="mt-4 text-sm xl:text-base text-indigo-100">
                        Adaptive practice that spends your time on the topics you miss most. Try one right now.
                    </p>

                    <div
                        x-data="{ picked: null, correct: 'b' }"
                        class="mt-8 rounded-3xl bg-white p-6 text-slate-800 shadow-2xl shadow-indigo-900/30"
                    >
                        <div class="flex items-center justify-between text-xs font-semibold text-slate-500">
                            <span class="rounded-full bg-indigo-50 px-3 py-1 text-indigo-700">Pharmacology</span>
                            <span>Sample question</span>
                        </div>

                        <p class="mt-4 font-display text-base xl:text-lg font-semibold leading-snug text-slate-900">
                            Which drug is the antidote for acetaminophen overdose?
                        </p>

                        <div class="mt-4 grid gap-1">
                            @foreach ([
                                'a' => 'Naloxone',
                                'b' => 'N-acetylcysteine',
                                'c' => 'Flumazenil',
                                'd' => 'Atropine',
                            ] as $key => $label)
                                <button
                                    type="button"
                                    @click="picked = '{{ $key }}'"
                                    :disabled="picked !== null"
                                    class="flex items-center gap-3 rounded-xl border px-4 py-2.5 text-left text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                                    :class="{
                                        'border-slate-200 hover:border-indigo-300 hover:bg-indigo-50': picked === null,
                                        'border-emerald-400 bg-emerald-50 text-emerald-800': picked !== null && '{{ $key }}' === correct,
                                        'border-rose-300 bg-rose-50 text-rose-800': picked === '{{ $key }}' && '{{ $key }}' !== correct,
                                        'border-slate-100 text-slate-400': picked !== null && '{{ $key }}' !== correct && picked !== '{{ $key }}'
                                    }"
                                >
                                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-600">{{ strtoupper($key) }}</span>
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>

                        <div x-show="picked !== null" x-cloak x-transition class="mt-4 rounded-xl bg-slate-50 p-3 text-xs leading-relaxed text-slate-600">
                            <template x-if="picked === correct">
                                <p><span class="font-bold text-emerald-700">Correct.</span> N-acetylcysteine restores glutathione and protects the liver.</p>
                            </template>
                            <template x-if="picked !== correct">
                                <p><span class="font-bold text-rose-700">Not quite.</span> The answer is N-acetylcysteine. It restores glutathione and protects the liver.</p>
                            </template>
                            <button type="button" @click="picked = null" class="mt-2 font-semibold text-indigo-600 hover:text-indigo-800">Try again</button>
                        </div>
                    </div>
                </div>

                <p class="relative mt-auto pt-8 text-xs text-indigo-100">Built for future pharmacists.</p>
            </aside>

            {{-- ============ RIGHT: sign-in form ============ --}}
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
                        <h1 class="font-display text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">Welcome back</h1>
                        <p class="mt-2 text-sm text-slate-500">Sign in to pick up your review where you left off.</p>
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

                    @session('status')
                        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-semibold text-emerald-700">
                            {{ $value }}
                        </div>
                    @endsession

                    <form method="POST" action="{{ route('login') }}" class="space-y-5" x-data="{ show: false }">
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
                        </div>

                        <div>
                            <div class="mb-2 flex items-center justify-between">
                                <label for="password" class="block text-sm font-semibold text-slate-700">Password</label>
                                @if (Route::has('password.request'))
                                    <a href="{{ route('password.request') }}" class="text-xs font-semibold text-indigo-600 hover:text-fuchsia-600">Forgot password?</a>
                                @endif
                            </div>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                                </span>
                                <input id="password" :type="show ? 'text' : 'password'" name="password" required autocomplete="current-password"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50 py-3 pl-12 pr-16 text-sm transition placeholder:text-slate-400 focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-100"
                                    placeholder="Your password">
                                <button type="button" @click="show = !show"
                                    class="absolute inset-y-0 right-0 px-4 text-xs font-semibold text-slate-500 hover:text-indigo-600 focus:outline-none focus-visible:text-indigo-600"
                                    x-text="show ? 'Hide' : 'Show'">Show</button>
                            </div>
                        </div>

                        <label for="remember_me" class="flex cursor-pointer items-center gap-2">
                            <input id="remember_me" type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm text-slate-600">Keep me signed in on this device</span>
                        </label>

                        <button type="submit"
                            class="w-full rounded-xl bg-gradient-to-r from-indigo-600 to-fuchsia-600 py-3.5 text-sm font-bold text-white shadow-lg shadow-indigo-200 transition hover:shadow-xl hover:shadow-fuchsia-200 hover:brightness-110 focus:outline-none focus-visible:ring-4 focus-visible:ring-indigo-200 active:scale-[0.99]">
                            Sign in
                        </button>
                    </form>

                    @if (Route::has('register'))
                        <p class="mt-8 text-center text-sm text-slate-500">
                            New to CitiRx?
                            <a href="{{ route('register') }}" class="font-bold text-indigo-600 hover:text-fuchsia-600">Create a free account</a>
                        </p>
                    @endif
                </div>
            </main>
        </div>
    </div>
</x-guest-layout>