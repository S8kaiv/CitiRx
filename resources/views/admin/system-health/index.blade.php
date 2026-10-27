<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-display text-xl font-bold text-slate-900">
                System Health
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                Read-only operational checks. Credentials and connection strings are never displayed.
            </p>
        </div>
    </x-slot>

    @php
        $statusClasses = [
            'ok' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
            'warning' => 'border-amber-200 bg-amber-50 text-amber-900',
            'error' => 'border-red-200 bg-red-50 text-red-900',
            'unknown' => 'border-slate-200 bg-slate-50 text-slate-700',
        ];
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="flex flex-wrap items-center justify-between gap-4 rounded-3xl border-2 border-slate-200 bg-white p-6 shadow-sm">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">
                        Overall status
                    </p>
                    <p class="mt-1 text-2xl font-extrabold text-slate-900">
                        {{ ucfirst($report['overall']) }}
                    </p>
                </div>
                <time class="text-sm text-slate-500" datetime="{{ $report['checked_at']->toIso8601String() }}">
                    Checked {{ $report['checked_at']->format('M j, Y g:i:s A') }}
                </time>
            </section>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($report['checks'] as $check)
                    <article class="rounded-3xl border-2 p-5 {{ $statusClasses[$check['status']] ?? $statusClasses['unknown'] }}">
                        <div class="flex items-start justify-between gap-3">
                            <h3 class="font-display font-extrabold">
                                {{ $check['label'] }}
                            </h3>
                            <span class="rounded-full bg-white/70 px-2.5 py-1 text-xs font-bold uppercase">
                                {{ $check['status'] }}
                            </span>
                        </div>
                        <p class="mt-3 text-lg font-bold">
                            {{ $check['summary'] }}
                        </p>
                        @if (filled($check['detail']))
                            <p class="mt-2 text-sm opacity-80">
                                {{ $check['detail'] }}
                            </p>
                        @endif
                    </article>
                @endforeach
            </section>

            <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
                This page reports status only. It cannot run shell commands, create backups, reveal environment variables, or inspect queue payloads.
            </div>
        </div>
    </div>
</x-app-layout>
