<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-display text-xl font-bold text-slate-900">
                    Administrative Audit History
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Review administrative changes to users and cohorts.
                </p>
            </div>

            <a
                href="{{ route('dashboard') }}"
                class="rounded-xl border-2 border-slate-200 bg-white px-4 py-2
                    text-sm font-bold text-slate-700 transition hover:bg-slate-50"
            >
                Back to Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('status'))
                <div
                    role="status"
                    class="rounded-2xl border border-green-200 bg-green-50 p-4 text-green-800"
                >
                    {{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div
                    role="alert"
                    class="rounded-2xl border border-red-200 bg-red-50 p-4 text-red-800"
                >
                    {{ session('error') }}
                </div>
            @endif

            {{-- Filters --}}
            <form
                method="GET"
                action="{{ route('admin.audit-logs.index') }}"
                class="grid gap-4 rounded-3xl border-2 border-slate-200 bg-white p-5
                    shadow-sm md:grid-cols-3"
            >
                <div>
                    <label
                        for="action"
                        class="block text-sm font-bold text-slate-700"
                    >
                        Action
                    </label>

                    <select
                        id="action"
                        name="action"
                        class="mt-1 w-full rounded-xl border-slate-300
                            focus:border-primary focus:ring-primary"
                    >
                        <option value="">All actions</option>

                        @foreach ($actions as $action)
                            <option
                                value="{{ $action }}"
                                @selected($filters['action'] === $action)
                            >
                                {{ $action }}
                            </option>
                        @endforeach
                    </select>

                    @error('action')
                        <p class="mt-1 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label
                        for="admin_id"
                        class="block text-sm font-bold text-slate-700"
                    >
                        Administrator
                    </label>

                    <select
                        id="admin_id"
                        name="admin_id"
                        class="mt-1 w-full rounded-xl border-slate-300
                            focus:border-primary focus:ring-primary"
                    >
                        <option value="">All administrators</option>

                        @foreach ($administrators as $administrator)
                            <option
                                value="{{ $administrator->user_id }}"
                                @selected(
                                    $filters['admin_id']
                                        === $administrator->user_id
                                )
                            >
                                {{ $administrator->fullName() }}
                                @if ($administrator->trashed())
                                    (Deleted)
                                @endif
                            </option>
                        @endforeach
                    </select>

                    @error('admin_id')
                        <p class="mt-1 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label
                        for="target_type"
                        class="block text-sm font-bold text-slate-700"
                    >
                        Record type
                    </label>

                    <select
                        id="target_type"
                        name="target_type"
                        class="mt-1 w-full rounded-xl border-slate-300
                            focus:border-primary focus:ring-primary"
                    >
                        <option value="">All record types</option>

                        <option
                            value="{{ \App\Models\User::class }}"
                            @selected(
                                $filters['target_type']
                                    === \App\Models\User::class
                            )
                        >
                            Users
                        </option>

                        <option
                            value="{{ \App\Models\Cohort::class }}"
                            @selected(
                                $filters['target_type']
                                    === \App\Models\Cohort::class
                            )
                        >
                            Cohorts
                        </option>
                    </select>

                    @error('target_type')
                        <p class="mt-1 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="flex flex-wrap gap-2 md:col-span-3">
                    <button
                        type="submit"
                        class="rounded-xl bg-primary px-4 py-2 text-sm
                            font-bold text-white transition hover:opacity-90"
                    >
                        Apply Filters
                    </button>

                    <a
                        href="{{ route('admin.audit-logs.index') }}"
                        class="rounded-xl border-2 border-slate-200 bg-white
                            px-4 py-2 text-sm font-bold text-slate-700
                            transition hover:bg-slate-50"
                    >
                        Reset
                    </a>
                </div>
            </form>

            {{-- Result summary --}}
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-slate-600">
                    Showing
                    <span class="font-bold text-slate-900">
                        {{ $logs->firstItem() ?? 0 }}
                    </span>
                    to
                    <span class="font-bold text-slate-900">
                        {{ $logs->lastItem() ?? 0 }}
                    </span>
                    of
                    <span class="font-bold text-slate-900">
                        {{ $logs->total() }}
                    </span>
                    audit records
                </p>
            </div>

            {{-- Desktop table --}}
            <div
                class="hidden overflow-hidden rounded-3xl border-2
                    border-slate-200 bg-white shadow-sm md:block"
            >
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                                <th class="px-5 py-4">Date and Time</th>
                                <th class="px-5 py-4">Administrator</th>
                                <th class="px-5 py-4">Action</th>
                                <th class="px-5 py-4">Target</th>
                                <th class="px-5 py-4">Changes</th>
                                <th class="px-5 py-4">Reason</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            @forelse ($logs as $log)
                                @php
                                    $targetLabel = match ($log->target_type) {
                                        \App\Models\User::class => 'User',
                                        \App\Models\Cohort::class => 'Cohort',
                                        default => class_basename($log->target_type),
                                    };
                                @endphp

                                <tr class="align-top hover:bg-slate-50/70">
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <div class="font-semibold text-slate-800">
                                            {{ $log->created_at->format('M j, Y') }}
                                        </div>

                                        <div class="text-xs text-slate-500">
                                            {{ $log->created_at->format('g:i:s A') }}
                                        </div>
                                    </td>

                                    <td class="px-5 py-4">
                                        <div class="font-semibold text-slate-800">
                                            {{ $log->admin?->fullName() ?? 'Unknown administrator' }}
                                        </div>

                                        @if ($log->admin?->trashed())
                                            <span class="text-xs font-semibold text-red-600">
                                                Deleted account
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-5 py-4">
                                        <span
                                            class="inline-flex rounded-full bg-primary-tint
                                                px-2.5 py-1 font-mono text-xs
                                                font-bold text-primary"
                                        >
                                            {{ $log->action }}
                                        </span>
                                    </td>

                                    <td class="px-5 py-4">
                                        <div class="font-semibold text-slate-800">
                                            {{ $targetLabel }}
                                        </div>

                                        <div
                                            class="max-w-44 truncate font-mono text-xs
                                                text-slate-500"
                                            title="{{ $log->target_id }}"
                                        >
                                            {{ $log->target_id }}
                                        </div>
                                    </td>

                                    <td class="min-w-64 px-5 py-4">
                                        @forelse ($log->changes ?? [] as $field => $change)
                                            @php
                                                $oldValue = $change['old'] ?? null;
                                                $newValue = $change['new'] ?? null;

                                                $displayOld = is_scalar($oldValue)
                                                    ? (string) $oldValue
                                                    : json_encode($oldValue);

                                                $displayNew = is_scalar($newValue)
                                                    ? (string) $newValue
                                                    : json_encode($newValue);
                                            @endphp

                                            <div class="mb-2 last:mb-0">
                                                <div class="font-mono text-xs font-bold text-slate-700">
                                                    {{ $field }}
                                                </div>

                                                <div class="text-xs text-slate-600">
                                                    <span class="text-red-600">
                                                        {{ filled($displayOld) ? $displayOld : '—' }}
                                                    </span>

                                                    <span aria-hidden="true">→</span>

                                                    <span class="text-green-700">
                                                        {{ filled($displayNew) ? $displayNew : '—' }}
                                                    </span>
                                                </div>
                                            </div>
                                        @empty
                                            <span class="text-xs text-slate-400">
                                                No field-level changes
                                            </span>
                                        @endforelse
                                    </td>

                                    <td class="max-w-72 px-5 py-4 text-slate-600">
                                        {{ $log->reason ?? 'No reason recorded.' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="6"
                                        class="px-6 py-12 text-center text-slate-500"
                                    >
                                        No audit records match these filters.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Mobile cards --}}
            <div class="space-y-4 md:hidden">
                @forelse ($logs as $log)
                    @php
                        $targetLabel = match ($log->target_type) {
                            \App\Models\User::class => 'User',
                            \App\Models\Cohort::class => 'Cohort',
                            default => class_basename($log->target_type),
                        };
                    @endphp

                    <article
                        class="space-y-3 rounded-3xl border-2 border-slate-200
                            bg-white p-5 shadow-sm"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="font-bold text-slate-900">
                                    {{ $log->action }}
                                </div>

                                <div class="text-xs text-slate-500">
                                    {{ $log->created_at->format('M j, Y g:i A') }}
                                </div>
                            </div>

                            <span
                                class="rounded-full bg-slate-100 px-2.5 py-1
                                    text-xs font-bold text-slate-600"
                            >
                                {{ $targetLabel }}
                            </span>
                        </div>

                        <div class="text-sm text-slate-700">
                            Performed by
                            <span class="font-bold">
                                {{ $log->admin?->fullName() ?? 'Unknown administrator' }}
                            </span>
                        </div>

                        @if ($log->reason)
                            <p class="text-sm text-slate-600">
                                {{ $log->reason }}
                            </p>
                        @endif

                        @if ($log->changes)
                            <dl class="space-y-2 border-t border-slate-100 pt-3">
                                @foreach ($log->changes as $field => $change)
                                    <div>
                                        <dt class="font-mono text-xs font-bold text-slate-700">
                                            {{ $field }}
                                        </dt>

                                        <dd class="text-xs text-slate-600">
                                            {{ is_scalar($change['old'] ?? null)
                                                ? ($change['old'] ?? '—')
                                                : json_encode($change['old'] ?? null) }}

                                            <span aria-hidden="true">→</span>

                                            {{ is_scalar($change['new'] ?? null)
                                                ? ($change['new'] ?? '—')
                                                : json_encode($change['new'] ?? null) }}
                                        </dd>
                                    </div>
                                @endforeach
                            </dl>
                        @endif
                    </article>
                @empty
                    <div
                        class="rounded-3xl border-2 border-dashed border-slate-300
                            bg-white p-10 text-center text-slate-500"
                    >
                        No audit records match these filters.
                    </div>
                @endforelse
            </div>

            {{ $logs->links() }}
        </div>
    </div>
</x-app-layout>
