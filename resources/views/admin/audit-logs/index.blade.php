<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-display text-xl font-bold text-slate-900">
                    Administrative Audit Center
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    Immutable history of sensitive administrative actions.
                </p>
            </div>

            <a
                href="{{ route('admin.audit-logs.export', request()->query()) }}"
                class="rounded-xl bg-primary px-4 py-2 text-sm font-bold text-white transition hover:opacity-90"
            >
                Export filtered CSV
            </a>
        </div>
    </x-slot>

    <div class="py-8">
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

                <div class="flex flex-wrap gap-2 md:col-span-3">
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

            <p class="text-sm text-slate-600">
                Showing {{ $logs->firstItem() ?? 0 }}–{{ $logs->lastItem() ?? 0 }}
                of {{ $logs->total() }} records
            </p>

            <div class="overflow-x-auto rounded-3xl border-2 border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="p-4">Date</th>
                            <th class="p-4">Administrator</th>
                            <th class="p-4">Action</th>
                            <th class="p-4">Target</th>
                            <th class="p-4">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($logs as $log)
                            <tr class="align-top">
                                <td class="whitespace-nowrap p-4">
                                    <div class="font-semibold text-slate-800">
                                        {{ $log->created_at->format('M j, Y') }}
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        {{ $log->created_at->format('g:i:s A') }}
                                    </div>
                                </td>
                                <td class="p-4">
                                    <div class="font-semibold text-slate-800">
                                        {{ $log->actorDisplayName() }}
                                    </div>
                                    @if ($log->admin?->trashed())
                                        <span class="text-xs font-semibold text-red-600">
                                            Deleted account
                                        </span>
                                    @endif
                                </td>
                                <td class="p-4">
                                    <span class="rounded-full bg-primary-tint px-2.5 py-1 font-mono text-xs font-bold text-primary">
                                        {{ $log->action }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    <div class="font-semibold text-slate-800">
                                        {{ $targetTypes[$log->target_type] ?? class_basename($log->target_type) }}
                                    </div>
                                    <div class="max-w-52 truncate font-mono text-xs text-slate-500" title="{{ $log->target_id }}">
                                        {{ $log->target_id }}
                                    </div>
                                    @if (isset($targetLinks[$log->log_id]))
                                        <a href="{{ $targetLinks[$log->log_id] }}" class="mt-1 inline-flex text-xs font-bold text-primary hover:underline">
                                            Open record
                                        </a>
                                    @endif
                                </td>
                                <td class="min-w-72 p-4">
                                    <details>
                                        <summary class="cursor-pointer font-bold text-primary">
                                            View changes
                                        </summary>
                                        <div class="mt-3 space-y-2">
                                            @forelse ($log->changes ?? [] as $field => $change)
                                                <div class="rounded-xl bg-slate-50 p-3 text-xs">
                                                    <div class="font-mono font-bold text-slate-700">
                                                        {{ $field }}
                                                    </div>
                                                    <div class="mt-1 break-all text-slate-600">
                                                        {{ is_scalar($change['old'] ?? null) ? ($change['old'] ?? '—') : json_encode($change['old'] ?? null) }}
                                                        →
                                                        {{ is_scalar($change['new'] ?? null) ? ($change['new'] ?? '—') : json_encode($change['new'] ?? null) }}
                                                    </div>
                                                </div>
                                            @empty
                                                <p class="text-xs text-slate-500">
                                                    No field-level changes recorded.
                                                </p>
                                            @endforelse
                                            @if ($log->reason)
                                                <p class="text-xs text-slate-600">
                                                    <strong>Reason:</strong> {{ $log->reason }}
                                                </p>
                                            @endif
                                        </div>
                                    </details>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-12 text-center text-slate-500">
                                    No audit records match these filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $logs->links() }}
        </div>
    </div>
</x-app-layout>
