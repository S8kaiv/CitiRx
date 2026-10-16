<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-xl font-bold text-slate-900">
            User Management
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-2xl border border-green-200 bg-green-50 p-4 text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            <form method="GET" class="grid gap-3 rounded-3xl border-2 border-slate-200 bg-white p-5 md:grid-cols-3">
                <input name="q" value="{{ $filters['q'] }}"
                    placeholder="Name, email, or student ID"
                    class="rounded-xl border-slate-300">

                <select name="role" class="rounded-xl border-slate-300">
                    <option value="">All roles</option>
                    @foreach (['student', 'faculty', 'admin'] as $role)
                        <option value="{{ $role }}" @selected($filters['role'] === $role)>
                            {{ ucfirst($role) }}
                        </option>
                    @endforeach
                </select>

                <select name="cohort_id" class="rounded-xl border-slate-300">
                    <option value="">All cohorts</option>
                    @foreach ($cohorts as $cohort)
                        <option value="{{ $cohort->cohort_id }}" @selected($filters['cohort_id'] === $cohort->cohort_id)>
                            {{ $cohort->cohort_name }} — {{ $cohort->academic_year }}
                            {{ $cohort->archived_at ? '(Archived)' : '' }}
                        </option>
                    @endforeach
                </select>

                <select name="verification" class="rounded-xl border-slate-300">
                    <option value="">Any email status</option>
                    <option value="verified" @selected($filters['verification'] === 'verified')>Verified</option>
                    <option value="unverified" @selected($filters['verification'] === 'unverified')>Unverified</option>
                </select>

                <select name="status" class="rounded-xl border-slate-300">
                    <option value="active" @selected($filters['status'] === 'active')>Active</option>
                    <option value="deleted" @selected($filters['status'] === 'deleted')>Deleted</option>
                    <option value="all" @selected($filters['status'] === 'all')>All</option>
                </select>

                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="no_cohort" value="1"
                        @checked($filters['no_cohort'])>
                    Without cohort
                </label>

                <div class="flex gap-2 md:col-span-3">
                    <button class="rounded-xl bg-primary px-4 py-2 font-bold text-white">
                        Apply Filters
                    </button>

                    <a href="{{ route('admin.users.index') }}"
                        class="rounded-xl border-2 border-slate-200 px-4 py-2 font-bold text-slate-700">
                        Reset
                    </a>
                </div>
            </form>

            <div class="overflow-x-auto rounded-3xl border-2 border-slate-200 bg-white">
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
                                    <div class="font-bold">{{ $managedUser->fullName() }}</div>
                                    <div class="text-xs text-slate-500">
                                        {{ $managedUser->student_id ?? 'No student ID' }}
                                    </div>
                                </td>
                                <td class="p-4">{{ ucfirst($managedUser->role) }}</td>
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
                                    <a href="{{ route('admin.users.edit', ['userId' => $managedUser->user_id]) }}"
                                        class="font-bold text-primary">
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

            {{ $users->links() }}
        </div>
    </div>
</x-app-layout>
