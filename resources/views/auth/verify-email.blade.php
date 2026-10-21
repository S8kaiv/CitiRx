<x-guest-layout fullscreen>
    <div class="flex min-h-screen flex-col items-center justify-center bg-slate-50 p-4 sm:p-6 lg:p-12 xl:p-16">
        <div class="w-full max-w-lg text-center">
            
            {{-- Brand Header (Centered) --}}
            <div class="mb-12 flex items-center justify-center gap-4">
                <div class="flex h-16 w-16 items-center justify-center rounded-3xl bg-indigo-600 text-white font-display text-2xl font-extrabold shadow-lg rotate-[-6deg]">
                    Rx
                </div>
                <div class="text-left">
                    <p class="font-display text-3xl font-extrabold leading-none text-slate-900">CitiRx</p>
                    <p class="mt-1.5 text-sm font-medium text-slate-400">PHLE adaptive prep</p>
                </div>
            </div>

            {{-- Verification Card --}}
            <div class="rounded-[40px] border border-slate-200 bg-white p-10 shadow-xl shadow-indigo-100/60 lg:p-16">
                
                {{-- Email Icon Header (Centered) --}}
                <div class="mb-6 flex justify-center">
                    <div class="inline-flex h-16 w-16 items-center justify-center rounded-[20px] bg-indigo-50 text-indigo-600">
                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                        </svg>
                    </div>
                </div>

                {{-- Card Heading & Text (Centered) --}}
                <div class="mb-10 text-center">
                    <h1 class="font-display text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900">Check your inbox</h1>
                    <p class="mt-4 text-sm sm:text-base text-slate-500 leading-relaxed max-w-md mx-auto">
                        Thanks for signing up! Before getting started, please verify your email address by clicking on the link we just emailed to you.
                    </p>
                </div>

                {{-- Status Message (Centered) --}}
                @if (session('status') == 'verification-link-sent')
                    <div class="mb-8 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-5 text-left text-sm font-medium text-emerald-700" role="status">
                        <svg class="mt-0.5 h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                        </svg>
                        <span>A new verification link has been sent to the email address you provided during registration.</span>
                    </div>
                @endif

                {{-- Action Buttons (Centered & Stacked) --}}
                <div class="space-y-4">
                    <form method="POST" action="{{ route('verification.send') }}"
                        x-data="{ busy: false }"
                        @submit="busy = true"
                        @pageshow.window="busy = false">
                        @csrf

                        <button type="submit"
                            :disabled="busy"
                            style="--lip: #4A2FC4;"
                            class="btn-press flex w-full items-center justify-center rounded-[20px] bg-primary px-8 py-4 font-display text-lg font-bold text-white shadow-md hover:bg-primary-lip disabled:opacity-70 focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/25 transition">
                            <span x-text="busy ? 'Resending Link…' : 'Resend Verification Email'">Resend Verification Email</span>
                        </button>
                    </form>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <button type="submit" class="w-full rounded-[20px] border border-slate-200 py-4 text-center text-base font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            Log Out
                        </button>
                    </form>
                </div>

                {{-- Footer Info Text (Centered) --}}
                <p class="mt-12 text-center text-sm text-slate-400 leading-relaxed max-w-sm mx-auto">
                    Didn't receive the email? Check your spam folder or click above to send another.
                </p>
            </div>
        </div>
    </div>
</x-guest-layout>