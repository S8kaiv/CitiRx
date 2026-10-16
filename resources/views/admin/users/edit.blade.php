<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-xl font-bold text-slate-900">
            Manage {{ $managedUser->fullName() }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-2xl border border-green-200 bg-green-50 p-4 text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            <section class="rounded-3xl border-2 border-slate-200 bg-white p-6">
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-bold uppercase text-slate-500">Email</dt>
                        <dd>{{ $managedUser->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase text-slate-500">Email status</dt>
                        <dd>{{ $managedUser->email_verified_at ? 'Verified' : 'Unverified' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase text-slate-500">Assessments</dt>
                        <dd>{{ $managedUser->assessment_sessions_count }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase text-slate-500">Account</dt>
                        <dd>{{ $managedUser->trashed() ? 'Deleted' : 'Active' }}</dd>
                    </div>
                </dl>
            </section>

            @if (! $managedUser->trashed())
                <form method="POST"
                    action="{{ route('admin.users.update', ['userId' => $managedUser->user_id]) }}"
                    class="space-y-4 rounded-3xl border-2 border-slate-200 bg-white p-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="role" class="font-bold text-slate-700">Role</label>
                        <select id="role" name="role" class="mt-1 w-full rounded-xl border-slate-300">
                            @foreach (['student', 'faculty', 'admin'] as $role)
                                <option value="{{ $role }}" @selected(old('role', $managedUser->role) === $role)>
                                    {{ ucfirst($role) }}
                                </option>
                            @endforeach
                        </select>
                        @error('role') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="cohort_id" class="font-bold text-slate-700">Cohort</label>
                        <select id="cohort_id" name="cohort_id"
                            class="mt-1 w-full rounded-xl border-slate-300">
                            <option value="">No cohort</option>
                            @foreach ($cohorts as $cohort)
                                <option value="{{ $cohort->cohort_id }}"
                                    @selected(old('cohort_id', $managedUser->cohort_id) === $cohort->cohort_id)>
                                    {{ $cohort->cohort_name }} — {{ $cohort->academic_year }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-slate-500">
                            Administrators automatically have no cohort.
                        </p>
                        @error('cohort_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="reason" class="font-bold text-slate-700">Reason for change</label>
                        <textarea id="reason" name="reason" rows="3" required maxlength="500"
                            class="mt-1 w-full rounded-xl border-slate-300">{{ old('reason') }}</textarea>
                        @error('reason') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <button class="rounded-xl bg-primary px-4 py-2 font-bold text-white">
                        Save Assignment
                    </button>
                </form>

                <form method="POST"
                    action="{{ route('admin.users.destroy', ['userId' => $managedUser->user_id]) }}"
                    class="space-y-3 rounded-3xl border-2 border-red-200 bg-red-50 p-6"
                    onsubmit="return confirm('Soft-delete this account?');">
                    @csrf
                    @method('DELETE')

                    <label for="delete_reason" class="font-bold text-red-800">Deletion reason</label>
                    <textarea id="delete_reason" name="reason" rows="2" required maxlength="500"
                        class="w-full rounded-xl border-red-300"></textarea>

                    <button class="rounded-xl bg-red-600 px-4 py-2 font-bold text-white">
                        Soft-delete Account
                    </button>
                </form>
            @else
                <form method="POST"
                    action="{{ route('admin.users.restore', ['userId' => $managedUser->user_id]) }}"
                    class="space-y-3 rounded-3xl border-2 border-green-200 bg-green-50 p-6">
                    @csrf
                    @method('PATCH')

                    <label for="restore_reason" class="font-bold text-green-900">Restoration reason</label>
                    <textarea id="restore_reason" name="reason" rows="2" required maxlength="500"
                        class="w-full rounded-xl border-green-300"></textarea>

                    <button class="rounded-xl bg-green-700 px-4 py-2 font-bold text-white">
                        Restore Account
                    </button>
                </form>
            @endif

            <section class="rounded-3xl border-2 border-slate-200 bg-white p-6">
                <h3 class="font-display text-lg font-bold">Recent Administrative Changes</h3>

                <div class="mt-4 space-y-3">
                    @forelse ($recentAudits as $log)
                        <div class="border-l-4 border-primary/30 pl-4 text-sm">
                            <div class="font-bold">{{ $log->action }}</div>
                            <div class="text-xs text-slate-500">
                                {{ $log->created_at->format('M j, Y g:i A') }}
                                · {{ $log->admin?->fullName() ?? 'Unknown administrator' }}
                            </div>
                            @if ($log->reason)
                                <div class="mt-1">{{ $log->reason }}</div>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No administrative changes recorded.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
