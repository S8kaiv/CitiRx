<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-display text-xl sm:text-2xl lg:text-3xl font-extrabold tracking-tight text-slate-900">
                    User Management
                </h2>
                <p class="hidden sm:block text-xs font-semibold uppercase tracking-wider text-muted-ink">
                    Administration
                </p>
            </div>

            <a href="{{ route('dashboard') }}" style="--lip: #CBD5E1;"
                class="btn-press rounded-xl border-2 border-b-4 border-slate-300 bg-white px-4 py-2 font-display text-sm font-bold text-slate-700 shadow-2xs transition hover:bg-slate-50">
                Back to Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-6 font-sans text-slate-900 antialiased">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div role="status" class="rounded-2xl border-2 border-b-4 border-emerald-300 bg-emerald-50 p-4 font-display text-sm font-bold text-emerald-800 shadow-2xs">
                    {{ session('status') }}
                </div>
            @endif

            <form method="GET" class="grid gap-3 rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-5 sm:p-6 shadow-sm md:grid-cols-3">
                <input name="q" value="{{ $filters['q'] }}" placeholder="Name, email, or student ID"
                    class="rounded-xl border-2 border-slate-200 text-sm font-medium focus:border-primary focus:ring-primary">

                <select name="role" class="rounded-xl border-2 border-slate-200 text-sm font-medium focus:border-primary focus:ring-primary">
                    <option value="">All roles</option>
                    @foreach (['student', 'faculty', 'admin'] as $role)
                        <option value="{{ $role }}" @selected($filters['role'] === $role)>
                            {{ ucfirst($role) }}
                        </option>
                    @endforeach
                </select>

                <select name="cohort_id" class="rounded-xl border-2 border-slate-200 text-sm font-medium focus:border-primary focus:ring-primary">
                    <option value="">All cohorts</option>
                    @foreach ($cohorts as $cohort)
                        <option value="{{ $cohort->cohort_id }}" @selected($filters['cohort_id'] === $cohort->cohort_id)>
                            {{ $cohort->cohort_name }} — {{ $cohort->academic_year }}
                            {{ $cohort->archived_at ? '(Archived)' : '' }}
                        </option>
                    @endforeach
                </select>

                <select name="verification" class="rounded-xl border-2 border-slate-200 text-sm font-medium focus:border-primary focus:ring-primary">
                    <option value="">Any email status</option>
                    <option value="verified" @selected($filters['verification'] === 'verified')>Verified</option>
                    <option value="unverified" @selected($filters['verification'] === 'unverified')>Unverified</option>
                </select>

                <select name="status" class="rounded-xl border-2 border-slate-200 text-sm font-medium focus:border-primary focus:ring-primary">
                    <option value="active" @selected($filters['status'] === 'active')>Active</option>
                    <option value="deleted" @selected($filters['status'] === 'deleted')>Deleted</option>
                    <option value="all" @selected($filters['status'] === 'all')>All</option>
                </select>

                <label class="flex items-center gap-2 rounded-xl border-2 border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700">
                    <input type="checkbox" name="no_cohort" value="1" @checked($filters['no_cohort'])
                        class="rounded border-slate-300 text-primary focus:ring-primary">
                    Without cohort
                </label>

                <div class="flex gap-2 md:col-span-3">
                    <button style="--lip: #4A2FC4;"
                        class="btn-press rounded-xl bg-primary px-5 py-2.5 font-display text-sm font-bold text-white shadow-md">
                        Apply Filters
                    </button>

                    <a href="{{ route('admin.users.index') }}" style="--lip: #CBD5E1;"
                        class="btn-press rounded-xl border-2 border-b-4 border-slate-300 bg-white px-4 py-2 font-display text-sm font-bold text-slate-700 shadow-2xs transition hover:bg-slate-50">
                        Reset
                    </a>
                </div>
            </form>

            <div class="overflow-hidden rounded-3xl border-2 border-b-4 border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-extrabold uppercase tracking-wider text-muted-ink">
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
                                <tr class="hover:bg-slate-50/70">
                                    <td class="p-4">
                                        <div class="font-display font-black text-slate-900">{{ $managedUser->fullName() }}</div>
                                        <div class="text-xs text-muted-ink">{{ $managedUser->student_id ?? 'No student ID' }}</div>
                                    </td>
                                    <td class="p-4">
                                        <span class="inline-flex rounded-full bg-primary-tint px-2.5 py-1 font-display text-xs font-bold text-primary">
                                            {{ ucfirst($managedUser->role) }}
                                        </span>
                                    </td>
                                    <td class="p-4 text-slate-700">
                                        {{ $managedUser->cohort?->cohort_name ?? 'Unassigned' }}
                                    </td>
                                    <td class="p-4">
                                        <div class="text-slate-800">{{ $managedUser->email }}</div>
                                        <div class="text-xs font-bold {{ $managedUser->email_verified_at ? 'text-emerald-700' : 'text-amber-700' }}">
                                            {{ $managedUser->email_verified_at ? 'Verified' : 'Unverified' }}
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <span class="inline-flex rounded-full px-2.5 py-1 font-display text-xs font-bold {{ $managedUser->trashed() ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700' }}">
                                            {{ $managedUser->trashed() ? 'Deleted' : 'Active' }}
                                        </span>
                                    </td>
                                    <td class="p-4 text-right">
                                        <a href="{{ route('admin.users.edit', ['userId' => $managedUser->user_id]) }}"
                                            style="--lip: #CBD5E1;"
                                            class="btn-press inline-flex rounded-xl border-2 border-b-4 border-slate-300 bg-white px-3 py-1.5 font-display text-xs font-bold text-primary shadow-2xs transition hover:bg-primary-tint">
                                            Manage
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-10 text-center text-xs font-semibold text-muted-ink">
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
