<x-app-layout surface="admin">
    <x-slot name="header">
        <x-admin.page-header
            title="Audit history">
            <x-slot:actions>
                <a
                    href="{{ route(
                        'admin.audit-logs.export',
                        request()->query(),
                    ) }}"
                    class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-bold text-slate-700 transition-colors hover:bg-slate-50 focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/25"
                >
                    Export filtered CSV
                </a>
            </x-slot:actions>
        </x-admin.page-header>
    </x-slot>

    <div class="pt-6 pb-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <form
                method="GET"
                action="{{ route('admin.audit-logs.index') }}"
                class="grid gap-4 rounded-3xl border-2 border-slate-200 bg-white p-5 shadow-sm md:grid-cols-3"
            >
                <label class="text-sm font-bold text-slate-700">
                    From
                    <input
                        type="date"
                        name="date_from"
                        value="{{ $filters['date_from'] }}"
                        class="mt-1 w-full rounded-xl border-slate-300"
                    >
                </label>

                <label class="text-sm font-bold text-slate-700">
                    To
                    <input
                        type="date"
                        name="date_to"
                        value="{{ $filters['date_to'] }}"
                        class="mt-1 w-full rounded-xl border-slate-300"
                    >
                </label>

                <label class="text-sm font-bold text-slate-700">
                    Administrator
                    <select name="admin_id" class="mt-1 w-full rounded-xl border-slate-300">
                        <option value="">All administrators</option>
                        @foreach ($administrators as $administrator)
                            <option value="{{ $administrator->user_id }}" @selected($filters['admin_id'] === $administrator->user_id)>
                                {{ $administrator->fullName() }}
                                {{ $administrator->trashed() ? '(Deleted)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="text-sm font-bold text-slate-700">
                    Action
                    <select name="action" class="mt-1 w-full rounded-xl border-slate-300">
                        <option value="">All actions</option>
                        @foreach ($actions as $action)
                            <option value="{{ $action }}" @selected($filters['action'] === $action)>
                                {{ $action }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="text-sm font-bold text-slate-700">
                    Target type
                    <select name="target_type" class="mt-1 w-full rounded-xl border-slate-300">
                        <option value="">All target types</option>
                        @foreach ($targetTypes as $type => $label)
                            <option value="{{ $type }}" @selected($filters['target_type'] === $type)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="text-sm font-bold text-slate-700">
                    Target record ID
                    <input
                        name="target_id"
                        value="{{ $filters['target_id'] }}"
                        placeholder="UUID"
                        class="mt-1 w-full rounded-xl border-slate-300"
                    >
                </label>

                <div class="rx-admin-actions flex flex-wrap gap-2 md:col-span-3">
                    <button class="rounded-xl bg-primary px-4 py-2 text-sm font-bold text-white">
                        Apply filters
                    </button>
                    <a
                        href="{{ route('admin.audit-logs.index') }}"
                        class="rounded-xl border-2 border-slate-200 px-4 py-2 text-sm font-bold text-slate-700"
                    >
                        Reset
                    </a>
                </div>
            </form>

            @php
                $days = $logs->getCollection()->groupBy(fn ($log) => $log->created_at->toDateString());
                $formatValue = fn ($value) => match (true) {
                    $value === null => 'Not set',
                    is_string($value) => $value === '' ? '""' : $value,
                    default => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                };
            @endphp

            @forelse ($days as $entries)
                <section class="space-y-2" aria-labelledby="audit-day-{{ $entries->first()->log_id }}">
                    <h2 id="audit-day-{{ $entries->first()->log_id }}" class="text-sm font-bold text-ink-muted">
                        {{ $entries->first()->created_at->format('l, M j, Y') }}
                    </h2>
                    <ol class="space-y-2">
                        @foreach ($entries as $log)
                            <li>
                                <x-admin.disclosure :title="str($log->action)->replace(['.', '_'], ' ')->headline()->toString().' by '.$log->actorDisplayName()">
                                    <x-slot:meta>
                                        <time datetime="{{ $log->created_at->toIso8601String() }}">{{ $log->created_at->format('g:i:s A') }}</time>
                                    </x-slot:meta>
                                    <div class="space-y-3 p-4 text-sm">
                                        <p class="text-ink">
                                            {{ $targetTypes[$log->target_type] ?? class_basename($log->target_type) }}
                                            @if ($log->admin?->trashed())
                                                <span class="text-danger-ink"> &middot; Deleted administrator account</span>
                                            @endif
                                        </p>
                                        <p class="break-all font-mono text-xs text-ink-muted">{{ $log->action }} &middot; {{ $log->target_id }}</p>
                                        @if (isset($targetLinks[$log->log_id]))
                                            <a href="{{ $targetLinks[$log->log_id] }}" class="font-bold text-primary-ink underline underline-offset-4">Open record</a>
                                        @endif
                                        @if ($log->reason)
                                            <p class="break-words text-ink-muted"><strong>Reason</strong> {{ $log->reason }}</p>
                                        @endif
                                        @if ($log->changes)
                                            <dl class="grid gap-2 sm:grid-cols-2">
                                                @foreach ($log->changes as $field => $change)
                                                    <div class="min-w-0 rounded-xl bg-surface-2 p-3">
                                                        <dt class="break-words font-bold text-ink">{{ $field }}</dt>
                                                        <dd class="mt-1 break-all text-ink-muted">
                                                            {{ $formatValue($change['old'] ?? null) }}
                                                            <span aria-hidden="true">&rarr;</span><span class="sr-only"> changed to </span>
                                                            {{ $formatValue($change['new'] ?? null) }}
                                                        </dd>
                                                    </div>
                                                @endforeach
                                            </dl>
                                        @else
                                            <p class="text-ink-muted">No field-level changes recorded.</p>
                                        @endif
                                    </div>
                                </x-admin.disclosure>
                            </li>
                        @endforeach
                    </ol>
                </section>
            @empty
                <p class="rounded-2xl border border-line bg-surface p-6 text-sm text-ink-muted">No audit records match these filters.</p>
            @endforelse

            <x-admin.pager :paginator="$logs" label="Audit history" />
        </div>
    </div>
</x-app-layout>
