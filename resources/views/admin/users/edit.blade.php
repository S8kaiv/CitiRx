<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-display text-xl sm:text-2xl lg:text-3xl font-extrabold tracking-tight text-slate-900">
                    Manage {{ $managedUser->fullName() }}
                </h2>
                <p class="hidden sm:block text-xs font-semibold uppercase tracking-wider text-muted-ink">
                    User account
                </p>
            </div>

            <a href="{{ route('admin.users.index') }}" style="--lip: #CBD5E1;"
                class="btn-press rounded-xl border-2 border-b-4 border-slate-300 bg-white px-4 py-2 font-display text-sm font-bold text-slate-700 shadow-2xs transition hover:bg-slate-50">
                All Users
            </a>
        </div>
    </x-slot>

    <div class="py-6 font-sans text-slate-900 antialiased">
        <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div role="status" class="rounded-2xl border-2 border-b-4 border-emerald-300 bg-emerald-50 p-4 font-display text-sm font-bold text-emerald-800 shadow-2xs">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div role="alert" class="rounded-2xl border-2 border-b-4 border-rose-300 bg-rose-50 p-4 font-display text-sm font-bold text-rose-800 shadow-2xs">
                    {{ session('error') }}
                </div>
            @endif

            <section class="rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-5 sm:p-6 shadow-sm">
                <dl class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-2xl border-2 border-slate-100 bg-slate-50 p-3.5">
                        <dt class="text-[10px] font-extrabold uppercase tracking-wider text-muted-ink">Email</dt>
                        <dd class="mt-1 break-all text-sm font-bold text-slate-900">{{ $managedUser->email }}</dd>
                    </div>
                    <div class="rounded-2xl border-2 border-slate-100 bg-slate-50 p-3.5">
                        <dt class="text-[10px] font-extrabold uppercase tracking-wider text-muted-ink">Email status</dt>
                        <dd class="mt-1 text-sm font-bold {{ $managedUser->email_verified_at ? 'text-emerald-700' : 'text-amber-700' }}">
                            {{ $managedUser->email_verified_at ? 'Verified' : 'Unverified' }}
                        </dd>
                    </div>
                    <div class="rounded-2xl border-2 border-slate-100 bg-slate-50 p-3.5">
                        <dt class="text-[10px] font-extrabold uppercase tracking-wider text-muted-ink">Assessments</dt>
                        <dd class="mt-1 font-display text-xl font-black text-slate-900">{{ $managedUser->assessment_sessions_count }}</dd>
                    </div>
                    <div class="rounded-2xl border-2 border-slate-100 bg-slate-50 p-3.5">
                        <dt class="text-[10px] font-extrabold uppercase tracking-wider text-muted-ink">Account</dt>
                        <dd class="mt-1 text-sm font-bold {{ $managedUser->trashed() ? 'text-rose-700' : 'text-emerald-700' }}">
                            {{ $managedUser->trashed() ? 'Deleted' : 'Active' }}
                        </dd>
                    </div>
                </dl>
            </section>

            @if (! $managedUser->trashed())
                <form method="POST" action="{{ route('admin.users.update', ['userId' => $managedUser->user_id]) }}"
                    class="space-y-4 rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-5 sm:p-6 shadow-sm">
                    @csrf
                    @method('PUT')

                    <h3 class="font-display text-lg font-black text-slate-900">Role & Cohort</h3>

                    <div>
                        <label for="role" class="block text-xs font-extrabold uppercase tracking-wider text-muted-ink">Role</label>
                        <select id="role" name="role"
                            class="mt-1 w-full rounded-xl border-2 border-slate-200 text-sm font-medium focus:border-primary focus:ring-primary">
                            @foreach (['student', 'faculty', 'admin'] as $role)
                                <option value="{{ $role }}" @selected(old('role', $managedUser->role) === $role)>
                                    {{ ucfirst($role) }}
                                </option>
                            @endforeach
                        </select>
                        @error('role') <p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="cohort_id" class="block text-xs font-extrabold uppercase tracking-wider text-muted-ink">Cohort</label>
                        <select id="cohort_id" name="cohort_id"
                            class="mt-1 w-full rounded-xl border-2 border-slate-200 text-sm font-medium focus:border-primary focus:ring-primary">
                            <option value="">No cohort</option>
                            @foreach ($cohorts as $cohort)
                                <option value="{{ $cohort->cohort_id }}"
                                    @selected(old('cohort_id', $managedUser->cohort_id) === $cohort->cohort_id)>
                                    {{ $cohort->cohort_name }} — {{ $cohort->academic_year }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs font-medium text-muted-ink">Administrators automatically have no cohort.</p>
                        @error('cohort_id') <p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="reason" class="block text-xs font-extrabold uppercase tracking-wider text-muted-ink">Reason for change</label>
                        <textarea id="reason" name="reason" rows="3" required maxlength="500"
                            class="mt-1 w-full rounded-xl border-2 border-slate-200 text-sm font-medium focus:border-primary focus:ring-primary">{{ old('reason') }}</textarea>
                        @error('reason') <p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <button style="--lip: #4A2FC4;"
                        class="btn-press rounded-2xl bg-primary px-6 py-3 font-display text-sm font-bold text-white shadow-md">
                        Save Assignment
                    </button>
                </form>

                <form method="POST" action="{{ route('admin.users.destroy', ['userId' => $managedUser->user_id]) }}"
                    class="space-y-3 rounded-3xl border-2 border-b-4 border-rose-300 bg-gradient-to-br from-rose-50/80 via-white to-white p-5 sm:p-6 shadow-sm"
                    onsubmit="return confirm('Soft-delete this account?');">
                    @csrf
                    @method('DELETE')

                    <h3 class="font-display text-lg font-black text-rose-900">Delete Account</h3>

                    <label for="delete_reason" class="block text-xs font-extrabold uppercase tracking-wider text-rose-700">Deletion reason</label>
                    <textarea id="delete_reason" name="reason" rows="2" required maxlength="500"
                        class="w-full rounded-xl border-2 border-rose-200 text-sm font-medium focus:border-rose-400 focus:ring-rose-400"></textarea>

                    <button style="--lip: #BE123C;"
                        class="btn-press rounded-2xl bg-rose-500 px-6 py-3 font-display text-sm font-bold text-white shadow-md hover:bg-rose-600">
                        Soft-delete Account
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.users.restore', ['userId' => $managedUser->user_id]) }}"
                    class="space-y-3 rounded-3xl border-2 border-b-4 border-emerald-300 bg-gradient-to-br from-emerald-50/80 via-white to-white p-5 sm:p-6 shadow-sm">
                    @csrf
                    @method('PATCH')

                    <h3 class="font-display text-lg font-black text-emerald-900">Restore Account</h3>

                    <label for="restore_reason" class="block text-xs font-extrabold uppercase tracking-wider text-emerald-700">Restoration reason</label>
                    <textarea id="restore_reason" name="reason" rows="2" required maxlength="500"
                        class="w-full rounded-xl border-2 border-emerald-200 text-sm font-medium focus:border-emerald-400 focus:ring-emerald-400"></textarea>

                    <button style="--lip: #047857;"
                        class="btn-press rounded-2xl bg-emerald-600 px-6 py-3 font-display text-sm font-bold text-white shadow-md hover:bg-emerald-700">
                        Restore Account
                    </button>
                </form>
            @endif

            <section class="rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-5 sm:p-6 shadow-sm">
                <h3 class="font-display text-lg font-black text-slate-900">Recent Administrative Changes</h3>

                <div class="mt-4 space-y-3">
                    @forelse ($recentAudits as $log)
                        <div class="rounded-2xl border-2 border-slate-100 bg-slate-50/50 p-4 text-sm">
                            <div class="font-display font-black text-slate-900">{{ $log->action }}</div>
                            <div class="text-xs font-semibold text-muted-ink">
                                {{ $log->created_at->format('M j, Y g:i A') }}
                                · {{ $log->admin?->fullName() ?? 'Unknown administrator' }}
                            </div>
                            @if ($log->reason)
                                <div class="mt-1.5 text-xs font-medium leading-relaxed text-slate-700">{{ $log->reason }}</div>
                            @endif
                        </div>
                    @empty
                        <div class="rounded-2xl border-2 border-slate-100 bg-slate-50 p-6 text-center text-xs font-semibold text-muted-ink">
                            No administrative changes recorded.
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
