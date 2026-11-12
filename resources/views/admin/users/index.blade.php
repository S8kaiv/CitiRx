<x-app-layout surface="admin">
    <x-slot name="header">
        <x-admin.page-header
            title="Users"
            description="Manage roles, email verification, cohort assignments, and account access."
        >
            <x-slot:actions>
                <a
                    href="{{ route(
                        'admin.cohort-imports.create',
                    ) }}"
                    class="btn-press inline-flex min-h-11 items-center justify-center rounded-xl bg-primary px-4 text-sm font-bold text-white shadow-md focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/25"
                    style="--lip: rgb(var(--color-primary-lip));"
                >
                    Bulk assign cohorts
                </a>
            </x-slot:actions>
        </x-admin.page-header>
    </x-slot>

    <div class="pt-6 pb-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <form
                method="GET"
                class="grid gap-3 rounded-3xl border-2 border-slate-200 bg-white p-5 md:grid-cols-3"
            >
                <div class="min-w-0 space-y-1.5">
                    <label
                        for="users-search"
                        class="block text-sm font-bold text-ink"
                    >
                        Search users
                    </label>

                    <input
                        id="users-search"
                        type="text"
                        name="q"
                        value="{{ $filters['q'] }}"
                        placeholder="Name, email, or student ID"
                        class="min-h-[var(--control-h)] w-full rounded-xl border-slate-300"
                    >
                </div>

                <div class="min-w-0 space-y-1.5">
                    <label
                        for="users-role"
                        class="block text-sm font-bold text-ink"
                    >
                        Role
                    </label>

                    <select
                        id="users-role"
                        name="role"
                        class="min-h-[var(--control-h)] w-full rounded-xl border-slate-300"
                    >
                        <option value="">All roles</option>

                        @foreach (['student', 'faculty', 'admin'] as $role)
                            <option
                                value="{{ $role }}"
                                @selected($filters['role'] === $role)
                            >
                                {{ ucfirst($role) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="min-w-0 space-y-1.5">
                    <label
                        for="users-cohort"
                        class="block text-sm font-bold text-ink"
                    >
                        Cohort
                    </label>

                    <select
                        id="users-cohort"
                        name="cohort_id"
                        class="min-h-[var(--control-h)] w-full rounded-xl border-slate-300"
                    >
                        <option value="">All cohorts</option>

                        @foreach ($cohorts as $cohort)
                            <option
                                value="{{ $cohort->cohort_id }}"
                                @selected(
                                    $filters['cohort_id'] === $cohort->cohort_id
                                )
                            >
                                {{ $cohort->cohort_name }} — {{ $cohort->academic_year }}
                                {{ $cohort->archived_at ? '(Archived)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="min-w-0 space-y-1.5">
                    <label
                        for="users-verification"
                        class="block text-sm font-bold text-ink"
                    >
                        Email status
                    </label>

                    <select
                        id="users-verification"
                        name="verification"
                        class="min-h-[var(--control-h)] w-full rounded-xl border-slate-300"
                    >
                        <option value="">Any email status</option>

                        <option
                            value="verified"
                            @selected($filters['verification'] === 'verified')
                        >
                            Verified
                        </option>

                        <option
                            value="unverified"
                            @selected($filters['verification'] === 'unverified')
                        >
                            Unverified
                        </option>
                    </select>
                </div>

                <div class="min-w-0 space-y-1.5">
                    <label
                        for="users-status"
                        class="block text-sm font-bold text-ink"
                    >
                        Account status
                    </label>

                    <select
                        id="users-status"
                        name="status"
                        class="min-h-[var(--control-h)] w-full rounded-xl border-slate-300"
                    >
                        <option
                            value="active"
                            @selected($filters['status'] === 'active')
                        >
                            Active
                        </option>

                        <option
                            value="deleted"
                            @selected($filters['status'] === 'deleted')
                        >
                            Deleted
                        </option>

                        <option
                            value="all"
                            @selected($filters['status'] === 'all')
                        >
                            All
                        </option>
                    </select>
                </div>

                <div class="flex items-end">
                    <label
                        class="flex min-h-[var(--control-h)] cursor-pointer items-center gap-2 text-sm text-slate-700"
                    >
                        <input
                            type="checkbox"
                            name="no_cohort"
                            value="1"
                            @checked($filters['no_cohort'])
                        >

                        <span>Without cohort</span>
                    </label>
                </div>

                <div class="rx-admin-actions flex flex-wrap gap-2 md:col-span-3">
                    <button
                        type="submit"
                        class="inline-flex min-h-[var(--control-h)] items-center justify-center rounded-xl bg-primary px-4 py-2 font-bold text-white"
                    >
                        Apply Filters
                    </button>

                    <a
                        href="{{ route('admin.users.index') }}"
                        class="inline-flex min-h-[var(--control-h)] items-center justify-center rounded-xl border-2 border-slate-200 px-4 py-2 font-bold text-slate-700"
                    >
                        Reset
                    </a>
                </div>
            </form>

            <div class="overflow-x-auto rounded-3xl border-2 border-slate-200 bg-white">
                <div class="rx-admin-table-scroll" role="region" aria-label="Scrollable data table" tabindex="0">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left">
                        <tr>
                            <th class="p-4">User</th>
                            <th class="p-4">Role</th>
                            <th class="p-4">Cohort</th>
                            <th class="p-4">Email</th>
                            <th class="p-4">Status</th>
                            <th class="p-4"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($users as $managedUser)
                            <tr>
                                <td class="p-4">
                                    <div class="font-bold">
                                        {{ $managedUser->fullName() }}
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        {{ $managedUser->student_id ?? 'No student ID' }}
                                    </div>
                                </td>
                                <td class="p-4">
                                    {{ ucfirst($managedUser->role) }}
                                </td>
                                <td class="p-4">
                                    {{ $managedUser->cohort?->cohort_name ?? 'Unassigned' }}
                                </td>
                                <td class="p-4">
                                    <div>{{ $managedUser->email }}</div>
                                    <div class="text-xs {{ $managedUser->email_verified_at ? 'text-green-700' : 'text-amber-700' }}">
                                        {{ $managedUser->email_verified_at ? 'Verified' : 'Unverified' }}
                                    </div>
                                </td>
                                <td class="p-4">
                                    {{ $managedUser->trashed() ? 'Deleted' : 'Active' }}
                                </td>
                                <td class="p-4 text-right">
                                    <a
                                        href="{{ route('admin.users.edit', ['userId' => $managedUser->user_id]) }}"
                                        class="inline-flex min-h-11 items-center justify-center rounded-lg px-2 font-bold text-primary"
                                    >
                                        Manage
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-8 text-center text-slate-500">
                                    No users match these filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                    </div>
            </div>

            {{ $users->links() }}
        </div>
    </div>
</x-app-layout>
