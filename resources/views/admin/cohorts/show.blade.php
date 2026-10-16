<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-display text-xl font-bold text-slate-900">
                    {{ $cohort->cohort_name }}
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    {{ $cohort->academic_year }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                @if (! $cohort->archived_at)
                    <a
                        href="{{ route(
                            'admin.cohorts.edit',
                            ['cohort' => $cohort],
                        ) }}"
                        class="rounded-xl border-2 border-slate-200
                            bg-white px-4 py-2 text-sm font-bold
                            text-slate-700 transition hover:bg-slate-50"
                    >
                        Edit Cohort
                    </a>
                @endif

                <a
                    href="{{ route('admin.cohorts.index') }}"
                    class="rounded-xl border-2 border-slate-200
                        bg-white px-4 py-2 text-sm font-bold
                        text-slate-700 transition hover:bg-slate-50"
                >
                    All Cohorts
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $windowStatus = $cohort->postTestWindowStatus();

        $windowClasses = match ($windowStatus) {
            'open' => 'border-green-200 bg-green-50 text-green-800',
            'scheduled' => 'border-blue-200 bg-blue-50 text-blue-800',
            'ended' => 'border-slate-200 bg-slate-50 text-slate-700',
            'archived' => 'border-red-200 bg-red-50 text-red-800',
            default => 'border-amber-200 bg-amber-50 text-amber-800',
        };
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div
                    role="status"
                    class="rounded-2xl border border-green-200
                        bg-green-50 p-4 text-green-800"
                >
                    {{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div
                    role="alert"
                    class="rounded-2xl border border-red-200
                        bg-red-50 p-4 text-red-800"
                >
                    {{ session('error') }}
                </div>
            @endif

            {{-- Overview --}}
            <section
                class="rounded-3xl border-2 border-slate-200
                    bg-white p-6 shadow-sm"
            >
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <dl class="grid flex-1 gap-4 sm:grid-cols-3">
                        <div>
                            <dt class="text-xs font-bold uppercase text-slate-500">
                                Academic Year
                            </dt>
                            <dd class="mt-1 font-bold text-slate-900">
                                {{ $cohort->academic_year }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-xs font-bold uppercase text-slate-500">
                                Target PhLE
                            </dt>
                            <dd class="mt-1 font-bold text-slate-900">
                                {{ $cohort->target_phle_date?->format('M j, Y') ?? 'Not set' }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-xs font-bold uppercase text-slate-500">
                                Account Status
                            </dt>
                            <dd class="mt-1 font-bold {{ $cohort->archived_at ? 'text-red-700' : 'text-green-700' }}">
                                {{ $cohort->archived_at ? 'Archived' : 'Active' }}
                            </dd>
                        </div>
                    </dl>
                </div>
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
                    <div
                        class="rounded-3xl border-2 border-slate-200
                            bg-white p-5 shadow-sm"
                    >
                        <div class="text-xs font-bold uppercase text-slate-500">
                            {{ $label }}
                        </div>

                        <div class="mt-2 font-display text-3xl font-black text-slate-900">
                            {{ $value }}
                        </div>
                    </div>
                @endforeach
            </section>

            {{-- Post-test window --}}
            <section
                class="space-y-5 rounded-3xl border-2
                    border-slate-200 bg-white p-6 shadow-sm"
            >
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="font-display text-lg font-bold text-slate-900">
                            Post-test Window
                        </h3>

                        <p class="mt-1 text-sm text-slate-500">
                            Form B is only available during this period.
                        </p>
                    </div>

                    <span
                        class="rounded-full border px-3 py-1
                            text-xs font-bold capitalize {{ $windowClasses }}"
                    >
                        {{ $windowStatus }}
                    </span>
                </div>

                @if ($cohort->archived_at)
                    <div
                        class="rounded-2xl border border-red-200
                            bg-red-50 p-4 text-sm text-red-800"
                    >
                        Archived cohorts cannot open a post-test window.
                    </div>
                @else
                    <form
                        method="POST"
                        action="{{ route(
                            'admin.cohorts.post-test-window',
                            ['cohort' => $cohort],
                        ) }}"
                        class="grid gap-4 md:grid-cols-2"
                    >
                        @csrf
                        @method('PATCH')

                        <div>
                            <label
                                for="post_test_opens_at"
                                class="block text-sm font-bold text-slate-700"
                            >
                                Opens At
                            </label>

                            <input
                                id="post_test_opens_at"
                                name="post_test_opens_at"
                                type="datetime-local"
                                required
                                value="{{ old(
                                    'post_test_opens_at',
                                    $cohort->post_test_opens_at?->format('Y-m-d\TH:i'),
                                ) }}"
                                class="mt-1 w-full rounded-xl border-slate-300
                                    focus:border-primary focus:ring-primary"
                            >

                            @error('post_test_opens_at')
                                <p class="mt-1 text-sm text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label
                                for="post_test_closes_at"
                                class="block text-sm font-bold text-slate-700"
                            >
                                Closes At
                            </label>

                            <input
                                id="post_test_closes_at"
                                name="post_test_closes_at"
                                type="datetime-local"
                                required
                                value="{{ old(
                                    'post_test_closes_at',
                                    $cohort->post_test_closes_at?->format('Y-m-d\TH:i'),
                                ) }}"
                                class="mt-1 w-full rounded-xl border-slate-300
                                    focus:border-primary focus:ring-primary"
                            >

                            @error('post_test_closes_at')
                                <p class="mt-1 text-sm text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label
                                for="window_reason"
                                class="block text-sm font-bold text-slate-700"
                            >
                                Reason
                            </label>

                            <textarea
                                id="window_reason"
                                name="reason"
                                rows="2"
                                required
                                minlength="5"
                                maxlength="500"
                                class="mt-1 w-full rounded-xl border-slate-300
                                    focus:border-primary focus:ring-primary"
                            >{{ old('reason') }}</textarea>

                            @error('reason')
                                <p class="mt-1 text-sm text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <button
                                class="rounded-xl bg-primary px-4 py-2
                                    text-sm font-bold text-white"
                            >
                                Save Post-test Window
                            </button>
                        </div>
                    </form>

                    @if (
                        $cohort->post_test_opens_at
                        || $cohort->post_test_closes_at
                    )
                        <form
                            method="POST"
                            action="{{ route(
                                'admin.cohorts.post-test-window',
                                ['cohort' => $cohort],
                            ) }}"
                            class="space-y-3 border-t border-slate-100 pt-5"
                            onsubmit="return confirm('Clear this post-test schedule?');"
                        >
                            @csrf
                            @method('PATCH')

                            <input
                                type="hidden"
                                name="clear_window"
                                value="1"
                            >

                            <div>
                                <label
                                    for="clear_window_reason"
                                    class="block text-sm font-bold text-slate-700"
                                >
                                    Reason for clearing
                                </label>

                                <textarea
                                    id="clear_window_reason"
                                    name="reason"
                                    rows="2"
                                    required
                                    minlength="5"
                                    maxlength="500"
                                    class="mt-1 w-full rounded-xl border-slate-300
                                        focus:border-primary focus:ring-primary"
                                ></textarea>
                            </div>

                            <button
                                class="rounded-xl border-2 border-amber-300
                                    bg-amber-50 px-4 py-2 text-sm font-bold
                                    text-amber-800"
                            >
                                Clear Schedule
                            </button>
                        </form>
                    @endif
                @endif
            </section>

            {{-- Faculty --}}
            <section
                class="rounded-3xl border-2 border-slate-200
                    bg-white p-6 shadow-sm"
            >
                <h3 class="font-display text-lg font-bold text-slate-900">
                    Assigned Faculty
                </h3>

                <div class="mt-4 space-y-3">
                    @forelse ($faculty as $facultyMember)
                        <div
                            class="flex flex-wrap items-center
                                justify-between gap-3 rounded-2xl
                                border border-slate-200 p-4"
                        >
                            <div>
                                <div class="font-bold text-slate-900">
                                    {{ $facultyMember->fullName() }}
                                </div>
                                <div class="text-sm text-slate-500">
                                    {{ $facultyMember->email }}
                                </div>
                            </div>

                            <a
                                href="{{ route(
                                    'admin.users.edit',
                                    ['userId' => $facultyMember->user_id],
                                ) }}"
                                class="font-bold text-primary"
                            >
                                Manage
                            </a>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">
                            No faculty member is assigned to this cohort.
                        </p>
                    @endforelse
                </div>
            </section>

            {{-- Students --}}
            <section
                class="rounded-3xl border-2 border-slate-200
                    bg-white p-6 shadow-sm"
            >
                <h3 class="font-display text-lg font-bold text-slate-900">
                    Enrolled Students
                </h3>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead>
                            <tr class="text-left text-xs font-bold uppercase text-slate-500">
                                <th class="py-3 pr-4">Student</th>
                                <th class="px-4 py-3">Student ID</th>
                                <th class="px-4 py-3">Email</th>
                                <th class="py-3 pl-4"></th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            @forelse ($students as $student)
                                <tr>
                                    <td class="py-3 pr-4 font-bold text-slate-900">
                                        {{ $student->fullName() }}
                                    </td>

                                    <td class="px-4 py-3 text-slate-600">
                                        {{ $student->student_id ?? '—' }}
                                    </td>

                                    <td class="px-4 py-3 text-slate-600">
                                        {{ $student->email }}
                                    </td>

                                    <td class="py-3 pl-4 text-right">
                                        <a
                                            href="{{ route(
                                                'admin.users.edit',
                                                ['userId' => $student->user_id],
                                            ) }}"
                                            class="font-bold text-primary"
                                        >
                                            Manage
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="4"
                                        class="py-8 text-center text-slate-500"
                                    >
                                        No students are assigned to this cohort.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $students->links() }}
                </div>
            </section>

            {{-- Archive and restore --}}
            <section
                class="rounded-3xl border-2
                    {{ $cohort->archived_at
                        ? 'border-green-200 bg-green-50'
                        : 'border-red-200 bg-red-50' }}
                    p-6"
            >
                @if (! $cohort->archived_at)
                    <h3 class="font-display text-lg font-bold text-red-900">
                        Archive Cohort
                    </h3>

                    <p class="mt-1 text-sm text-red-700">
                        Existing user assignments and research history will be preserved.
                    </p>

                    <form
                        method="POST"
                        action="{{ route(
                            'admin.cohorts.archive',
                            ['cohort' => $cohort],
                        ) }}"
                        class="mt-4 space-y-3"
                        onsubmit="return confirm('Archive this cohort?');"
                    >
                        @csrf
                        @method('PATCH')

                        <textarea
                            name="reason"
                            rows="2"
                            required
                            minlength="5"
                            maxlength="500"
                            placeholder="Reason for archiving"
                            class="w-full rounded-xl border-red-300"
                        ></textarea>

                        <button
                            class="rounded-xl bg-red-600 px-4 py-2
                                text-sm font-bold text-white"
                        >
                            Archive Cohort
                        </button>
                    </form>
                @else
                    <h3 class="font-display text-lg font-bold text-green-900">
                        Restore Cohort
                    </h3>

                    <p class="mt-1 text-sm text-green-700">
                        Restoring the cohort will not reopen its post-test window.
                    </p>

                    <form
                        method="POST"
                        action="{{ route(
                            'admin.cohorts.unarchive',
                            ['cohort' => $cohort],
                        ) }}"
                        class="mt-4 space-y-3"
                    >
                        @csrf
                        @method('PATCH')

                        <textarea
                            name="reason"
                            rows="2"
                            required
                            minlength="5"
                            maxlength="500"
                            placeholder="Reason for restoring"
                            class="w-full rounded-xl border-green-300"
                        ></textarea>

                        <button
                            class="rounded-xl bg-green-700 px-4 py-2
                                text-sm font-bold text-white"
                        >
                            Unarchive Cohort
                        </button>
                    </form>
                @endif
            </section>

            {{-- Audit history --}}
            <section
                class="rounded-3xl border-2 border-slate-200
                    bg-white p-6 shadow-sm"
            >
                <div class="flex items-center justify-between gap-3">
                    <h3 class="font-display text-lg font-bold text-slate-900">
                        Recent Changes
                    </h3>

                    <a
                        href="{{ route(
                            'admin.audit-logs.index',
                            ['target_type' => \App\Models\Cohort::class],
                        ) }}"
                        class="text-sm font-bold text-primary"
                    >
                        View All Audits
                    </a>
                </div>

                <div class="mt-4 space-y-3">
                    @forelse ($recentAudits as $log)
                        <div class="border-l-4 border-primary/30 pl-4">
                            <div class="font-bold text-slate-900">
                                {{ $log->action }}
                            </div>

                            <div class="text-xs text-slate-500">
                                {{ $log->created_at->format('M j, Y g:i A') }}
                                ·
                                {{ $log->admin?->fullName() ?? 'Unknown administrator' }}
                            </div>

                            @if ($log->reason)
                                <p class="mt-1 text-sm text-slate-600">
                                    {{ $log->reason }}
                                </p>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">
                            No administrative changes recorded.
                        </p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
