<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-xl font-bold">
            Confirm Cohort Assignments
        </h1>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-6 px-4">
            @if (session('error'))
                <div class="rounded-xl bg-red-50 p-4 text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            <section class="rounded-3xl border-2 border-slate-200 bg-white p-6">
                <dl class="grid gap-4 sm:grid-cols-4">
                    <div>
                        <dt class="text-xs uppercase text-slate-500">File</dt>
                        <dd class="font-bold">{{ $import->original_filename }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs uppercase text-slate-500">Rows</dt>
                        <dd class="font-bold">{{ $import->summary['total'] }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs uppercase text-slate-500">Changes</dt>
                        <dd class="font-bold">{{ $import->summary['changed'] }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs uppercase text-slate-500">Expires</dt>
                        <dd class="font-bold">{{ $import->expires_at->format('g:i A') }}</dd>
                    </div>
                </dl>
            </section>

            <div class="overflow-x-auto rounded-3xl border-2 border-slate-200 bg-white">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead>
                        <tr class="text-left">
                            <th class="p-4">User</th>
                            <th class="p-4">Role</th>
                            <th class="p-4">Current cohort</th>
                            <th class="p-4">Target cohort</th>
                            <th class="p-4">Result</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @foreach ($rows as $row)
                            <tr>
                                <td class="p-4">
                                    <div class="font-bold">
                                        {{ $row['user']?->fullName() ?? 'Unavailable user' }}
                                    </div>

                                    <div class="text-xs text-slate-500">
                                        {{ $row['user']?->email }}
                                    </div>
                                </td>

                                <td class="p-4">
                                    {{ ucfirst($row['user']?->role ?? '') }}
                                </td>

                                <td class="p-4">
                                    {{ $row['user']?->cohort?->cohort_name ?? 'Unassigned' }}
                                </td>

                                <td class="p-4">
                                    {{ $row['target_cohort']?->cohort_name ?? 'Unavailable' }}
                                </td>

                                <td class="p-4">
                                    {{ $row['changed'] ? 'Will change' : 'No change' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($import->committed_at === null)
                <div class="grid gap-5 lg:grid-cols-2">
                    <form
                        method="POST"
                        action="{{ route(
                            'admin.cohort-imports.commit',
                            ['import' => $import],
                        ) }}"
                        class="rounded-3xl border-2 border-green-200 bg-green-50 p-6"
                    >
                        @csrf

                        <input
                            type="hidden"
                            name="lock_version"
                            value="{{ $import->lock_version }}"
                        >

                        <textarea
                            name="reason"
                            required
                            placeholder="Reason for this bulk assignment"
                            class="w-full rounded-xl border-slate-300"
                        ></textarea>

                        <input
                            type="password"
                            name="current_password"
                            required
                            placeholder="Current password"
                            class="mt-3 w-full rounded-xl border-slate-300"
                        >

                        <button class="mt-4 rounded-xl bg-green-700 px-5 py-3 font-bold text-white">
                            Confirm Assignments
                        </button>
                    </form>

                    <form
                        method="POST"
                        action="{{ route(
                            'admin.cohort-imports.destroy',
                            ['import' => $import],
                        ) }}"
                        class="rounded-3xl border-2 border-red-200 bg-red-50 p-6"
                    >
                        @csrf
                        @method('DELETE')

                        <p class="text-sm text-red-800">
                            Cancelling deletes this temporary preview without changing users.
                        </p>

                        <button class="mt-4 rounded-xl bg-red-700 px-5 py-3 font-bold text-white">
                            Cancel Preview
                        </button>
                    </form>
                </div>
            @else
                <div class="rounded-xl bg-green-50 p-4 text-green-800">
                    This import was committed
                    {{ $import->committed_at->diffForHumans() }}.
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
