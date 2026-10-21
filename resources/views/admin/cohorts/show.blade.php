<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-display text-xl sm:text-2xl lg:text-3xl font-extrabold tracking-tight text-slate-900">
                    {{ $cohort->cohort_name }}
                </h2>
                <p class="hidden sm:block text-xs font-semibold uppercase tracking-wider text-muted-ink">
                    {{ $cohort->academic_year }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                @if (! $cohort->archived_at)
                    <a href="{{ route('admin.cohorts.edit', ['cohort' => $cohort]) }}" style="--lip: #4A2FC4;"
                        class="btn-press rounded-xl bg-primary px-4 py-2 font-display text-sm font-bold text-white shadow-md">
                        Edit Cohort
                    </a>
                @endif

                <a href="{{ route('admin.cohorts.index') }}" style="--lip: #CBD5E1;"
                    class="btn-press rounded-xl border-2 border-b-4 border-slate-300 bg-white px-4 py-2 font-display text-sm font-bold text-slate-700 shadow-2xs transition hover:bg-slate-50">
                    All Cohorts
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $windowStatus = $cohort->postTestWindowStatus();

        $windowClasses = match ($windowStatus) {
            'open' => 'border-emerald-300 bg-emerald-50 text-emerald-800',
            'scheduled' => 'border-sky-300 bg-sky-50 text-sky-800',
            'ended' => 'border-slate-300 bg-slate-50 text-slate-700',
            'archived' => 'border-rose-300 bg-rose-50 text-rose-800',
            default => 'border-amber-300 bg-amber-50 text-amber-800',
        };
    @endphp

    <div class="py-6 font-sans text-slate-900 antialiased">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
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

            {{-- Overview --}}
            <section class="relative overflow-hidden rounded-[1.5rem] border-2 border-b-4 border-primary/30 bg-gradient-to-br from-primary-tint via-[#FAF8FF] to-white p-6 sm:p-8 shadow-sm">
                <dl class="grid gap-3 sm:grid-cols-3">
                    <div class="rounded-2xl border-2 border-white bg-white/80 p-4 shadow-2xs">
                        <dt class="text-[10px] font-extrabold uppercase tracking-wider text-muted-ink">Academic Year</dt>
                        <dd class="mt-1 font-display text-lg font-black text-slate-900">{{ $cohort->academic_year }}</dd>
                    </div>

                    <div class="rounded-2xl border-2 border-white bg-white/80 p-4 shadow-2xs">
                        <dt class="text-[10px] font-extrabold uppercase tracking-wider text-muted-ink">Target PhLE</dt>
                        <dd class="mt-1 font-display text-lg font-black text-slate-900">
                            {{ $cohort->target_phle_date?->format('M j, Y') ?? 'Not set' }}
                        </dd>
                    </div>

                    <div class="rounded-2xl border-2 border-white bg-white/80 p-4 shadow-2xs">
                        <dt class="text-[10px] font-extrabold uppercase tracking-wider text-muted-ink">Account Status</dt>
                        <dd class="mt-1 font-display text-lg font-black {{ $cohort->archived_at ? 'text-rose-700' : 'text-emerald-700' }}">
                            {{ $cohort->archived_at ? 'Archived' : 'Active' }}
                        </dd>
                    </div>
                </dl>
            </section>

            {{-- Assessment statistics --}}
            <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                @foreach ([
                    'Students' => $counts['students'],
                    'Diagnostic Started' => $counts['diagnostic_started'],
                    'Diagnostic Completed' => $counts['diagnostic_completed'],
                    'Mock Started' => $counts['mock_started'],
                    'Mock Completed' => $counts['mock_completed'],
                ] as $label => $value)
                    <div class="rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-5 shadow-sm">
                        <div class="text-xs font-extrabold uppercase tracking-wider text-muted-ink">{{ $label }}</div>
                        <div class="mt-2 font-display text-3xl font-black text-slate-900">{{ $value }}</div>
                    </div>
                @endforeach
            </section>

            <div class="grid gap-6 lg:grid-cols-3">
                {{-- MAIN COLUMN --}}
                <div class="space-y-6 lg:col-span-2">

                    {{-- Post-test window --}}
                    <section class="space-y-5 rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-5 sm:p-6 shadow-sm">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h3 class="font-display text-lg font-black text-slate-900">Post-test Window</h3>
                                <p class="mt-0.5 text-xs font-semibold text-muted-ink">
                                    Form B is only available during this period.
                                </p>
                            </div>

                            <span class="rounded-full border px-3 py-1 font-display text-xs font-bold capitalize {{ $windowClasses }}">
                                {{ $windowStatus }}
                            </span>
                        </div>

                        @if ($cohort->archived_at)
                            <div class="rounded-2xl border-2 border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-800">
                                Archived cohorts cannot open a post-test window.
                            </div>
                        @else
                            <form method="POST" action="{{ route('admin.cohorts.post-test-window', ['cohort' => $cohort]) }}"
                                class="grid gap-4 md:grid-cols-2">
                                @csrf
                                @method('PATCH')

                                <div>
                                    <label for="post_test_opens_at" class="block text-xs font-extrabold uppercase tracking-wider text-muted-ink">
                                        Opens At
                                    </label>

                                    <input id="post_test_opens_at" name="post_test_opens_at" type="datetime-local" required
                                        value="{{ old('post_test_opens_at', $cohort->post_test_opens_at?->format('Y-m-d\TH:i')) }}"
                                        class="mt-1 w-full rounded-xl border-2 border-slate-200 text-sm font-medium focus:border-primary focus:ring-primary">

                                    @error('post_test_opens_at')
                                        <p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="post_test_closes_at" class="block text-xs font-extrabold uppercase tracking-wider text-muted-ink">
                                        Closes At
                                    </label>

                                    <input id="post_test_closes_at" name="post_test_closes_at" type="datetime-local" required
                                        value="{{ old('post_test_closes_at', $cohort->post_test_closes_at?->format('Y-m-d\TH:i')) }}"
                                        class="mt-1 w-full rounded-xl border-2 border-slate-200 text-sm font-medium focus:border-primary focus:ring-primary">

                                    @error('post_test_closes_at')
                                        <p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="md:col-span-2">
                                    <label for="window_reason" class="block text-xs font-extrabold uppercase tracking-wider text-muted-ink">
                                        Reason
                                    </label>

                                    <textarea id="window_reason" name="reason" rows="2" required minlength="5" maxlength="500"
                                        class="mt-1 w-full rounded-xl border-2 border-slate-200 text-sm font-medium focus:border-primary focus:ring-primary">{{ old('reason') }}</textarea>

                                    @error('reason')
                                        <p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="md:col-span-2">
                                    <button style="--lip: #4A2FC4;"
                                        class="btn-press rounded-2xl bg-primary px-6 py-3 font-display text-sm font-bold text-white shadow-md">
                                        Save Post-test Window
                                    </button>
                                </div>
                            </form>

                            @if ($cohort->post_test_opens_at || $cohort->post_test_closes_at)
                                <form method="POST" action="{{ route('admin.cohorts.post-test-window', ['cohort' => $cohort]) }}"
                                    class="space-y-3 border-t border-slate-100 pt-5"
                                    onsubmit="return confirm('Clear this post-test schedule?');">
                                    @csrf
                                    @method('PATCH')

                                    <input type="hidden" name="clear_window" value="1">

                                    <div>
                                        <label for="clear_window_reason" class="block text-xs font-extrabold uppercase tracking-wider text-muted-ink">
                                            Reason for clearing
                                        </label>

                                        <textarea id="clear_window_reason" name="reason" rows="2" required minlength="5" maxlength="500"
                                            class="mt-1 w-full rounded-xl border-2 border-slate-200 text-sm font-medium focus:border-primary focus:ring-primary"></textarea>
                                    </div>

                                    <button style="--lip: #B45309;"
                                        class="btn-press rounded-2xl bg-amber-500 px-6 py-3 font-display text-sm font-bold text-white shadow-md hover:bg-amber-600">
                                        Clear Schedule
                                    </button>
                                </form>
                            @endif
                        @endif
                    </section>

                    {{-- Students --}}
                    <section class="rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-5 sm:p-6 shadow-sm">
                        <h3 class="font-display text-lg font-black text-slate-900">Enrolled Students</h3>

                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead>
                                    <tr class="text-left text-xs font-extrabold uppercase tracking-wider text-muted-ink">
                                        <th class="py-3 pr-4">Student</th>
                                        <th class="px-4 py-3">Student ID</th>
                                        <th class="px-4 py-3">Email</th>
                                        <th class="py-3 pl-4"></th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-slate-100">
                                    @forelse ($students as $student)
                                        <tr>
                                            <td class="py-3 pr-4 font-display font-black text-slate-900">{{ $student->fullName() }}</td>
                                            <td class="px-4 py-3 text-slate-600">{{ $student->student_id ?? '—' }}</td>
                                            <td class="px-4 py-3 text-slate-600">{{ $student->email }}</td>
                                            <td class="py-3 pl-4 text-right">
                                                <a href="{{ route('admin.users.edit', ['userId' => $student->user_id]) }}"
                                                    style="--lip: #CBD5E1;"
                                                    class="btn-press inline-flex rounded-xl border-2 border-b-4 border-slate-300 bg-white px-3 py-1.5 font-display text-xs font-bold text-primary shadow-2xs transition hover:bg-primary-tint">
                                                    Manage
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="py-8 text-center text-xs font-semibold text-muted-ink">
                                                No students are assigned to this cohort.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">{{ $students->links() }}</div>
                    </section>

                    {{-- Audit history --}}
                    <section class="rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-5 sm:p-6 shadow-sm">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="font-display text-lg font-black text-slate-900">Recent Changes</h3>

                            <a href="{{ route('admin.audit-logs.index', ['target_type' => \App\Models\Cohort::class]) }}"
                                class="font-display text-xs font-bold text-primary hover:underline">
                                View all audits →
                            </a>
                        </div>

                        <div class="mt-4 space-y-3">
                            @forelse ($recentAudits as $log)
                                <div class="rounded-2xl border-2 border-slate-100 bg-slate-50/50 p-4">
                                    <div class="font-display font-black text-slate-900">{{ $log->action }}</div>

                                    <div class="text-xs font-semibold text-muted-ink">
                                        {{ $log->created_at->format('M j, Y g:i A') }}
                                        ·
                                        {{ $log->admin?->fullName() ?? 'Unknown administrator' }}
                                    </div>

                                    @if ($log->reason)
                                        <p class="mt-1.5 text-xs font-medium leading-relaxed text-slate-700">{{ $log->reason }}</p>
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

                {{-- SIDEBAR --}}
                <aside class="space-y-6">

                    {{-- Faculty --}}
                    <section class="rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="font-display text-xs font-extrabold uppercase tracking-wider text-muted-ink">
                            Assigned Faculty
                        </h3>

                        <div class="mt-4 space-y-3">
                            @forelse ($faculty as $facultyMember)
                                <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border-2 border-slate-100 bg-slate-50 p-3.5">
                                    <div class="min-w-0">
                                        <div class="font-display font-black text-slate-900">{{ $facultyMember->fullName() }}</div>
                                        <div class="break-all text-xs text-muted-ink">{{ $facultyMember->email }}</div>
                                    </div>

                                    <a href="{{ route('admin.users.edit', ['userId' => $facultyMember->user_id]) }}"
                                        class="font-display text-xs font-bold text-primary hover:underline">
                                        Manage →
                                    </a>
                                </div>
                            @empty
                                <p class="text-xs font-semibold text-muted-ink">
                                    No faculty member is assigned to this cohort.
                                </p>
                            @endforelse
                        </div>
                    </section>

                    {{-- Archive and restore --}}
                    @if (! $cohort->archived_at)
                        <section class="rounded-3xl border-2 border-b-4 border-rose-300 bg-gradient-to-br from-rose-50/80 via-white to-white p-6 shadow-sm">
                            <h3 class="font-display text-lg font-black text-rose-900">Archive Cohort</h3>

                            <p class="mt-1 text-xs font-semibold text-rose-700">
                                Existing user assignments and research history will be preserved.
                            </p>

                            <form method="POST" action="{{ route('admin.cohorts.archive', ['cohort' => $cohort]) }}"
                                class="mt-4 space-y-3" onsubmit="return confirm('Archive this cohort?');">
                                @csrf
                                @method('PATCH')

                                <textarea name="reason" rows="2" required minlength="5" maxlength="500"
                                    placeholder="Reason for archiving"
                                    class="w-full rounded-xl border-2 border-rose-200 text-sm font-medium focus:border-rose-400 focus:ring-rose-400"></textarea>

                                <button style="--lip: #BE123C;"
                                    class="btn-press block w-full rounded-2xl bg-rose-500 py-3 text-center font-display text-xs font-black uppercase tracking-wider text-white shadow-md hover:bg-rose-600">
                                    Archive Cohort
                                </button>
                            </form>
                        </section>
                    @else
                        <section class="rounded-3xl border-2 border-b-4 border-emerald-300 bg-gradient-to-br from-emerald-50/80 via-white to-white p-6 shadow-sm">
                            <h3 class="font-display text-lg font-black text-emerald-900">Restore Cohort</h3>

                            <p class="mt-1 text-xs font-semibold text-emerald-700">
                                Restoring the cohort will not reopen its post-test window.
                            </p>

                            <form method="POST" action="{{ route('admin.cohorts.unarchive', ['cohort' => $cohort]) }}"
                                class="mt-4 space-y-3">
                                @csrf
                                @method('PATCH')

                                <textarea name="reason" rows="2" required minlength="5" maxlength="500"
                                    placeholder="Reason for restoring"
                                    class="w-full rounded-xl border-2 border-emerald-200 text-sm font-medium focus:border-emerald-400 focus:ring-emerald-400"></textarea>

                                <button style="--lip: #047857;"
                                    class="btn-press block w-full rounded-2xl bg-emerald-600 py-3 text-center font-display text-xs font-black uppercase tracking-wider text-white shadow-md hover:bg-emerald-700">
                                    Unarchive Cohort
                                </button>
                            </form>
                        </section>
                    @endif
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
