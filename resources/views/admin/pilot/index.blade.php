<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-black uppercase tracking-[0.2em] text-primary">
                Official research operations
            </p>

            <h1 class="text-2xl font-black text-slate-900">
                Pilot Controls
            </h1>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if (session('status'))
            <div
                role="status"
                class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800"
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

        @if ($errors->any())
            <div
                role="alert"
                class="rounded-2xl border border-red-200 bg-red-50 p-4 text-red-800"
            >
                <p class="font-black">
                    The pilot phase was not changed.
                </p>

                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (config('citirx.research.allow_unvalidated_forms'))
            <div class="rounded-2xl border-2 border-red-400 bg-red-50 p-5 text-red-900">
                <strong>Unsafe research override detected.</strong>
                CITIRX_ALLOW_UNVALIDATED_RESEARCH_FORMS must be false
                before official collection can begin.
            </div>
        @endif

        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-black text-slate-900">
                        Current pilot phase
                    </h2>

                    <p class="mt-2 text-2xl font-black text-primary">
                        {{ str($control->phase)->replace('_', ' ')->title() }}
                    </p>
                </div>

                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-700">
                    Lock version {{ $control->lock_version }}
                </span>
            </div>

            <div class="mt-4 grid gap-3 text-sm text-slate-600 md:grid-cols-2">
                <p>
                    <span class="font-bold text-slate-800">
                        Official collection:
                    </span>

                    @if ($control->official_collection_started_at)
                        started
                        {{ $control->official_collection_started_at->format('M j, Y g:i A') }}
                    @else
                        not started
                    @endif
                </p>

                <p>
                    <span class="font-bold text-slate-800">
                        Started by:
                    </span>

                    @if ($control->starter)
                        {{ $control->starter->first_name }}
                        {{ $control->starter->last_name }}
                    @else
                        —
                    @endif
                </p>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2">
            @foreach ($formAudits as $form => $audit)
                <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-xs font-black uppercase tracking-widest text-slate-500">
                        {{ $form }}
                    </p>

                    <h2 class="mt-1 text-lg font-black text-slate-900">
                        {{ $audit['expected_version'] }}
                    </h2>

                    <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <dt class="text-slate-500">Questions</dt>
                            <dd class="font-bold">
                                {{ $audit['active_questions'] }}/60
                            </dd>
                        </div>

                        <div>
                            <dt class="text-slate-500">Validated</dt>
                            <dd class="font-bold">
                                {{ $audit['fully_validated'] }}/60
                            </dd>
                        </div>

                        <div>
                            <dt class="text-slate-500">Placeholders</dt>
                            <dd class="font-bold">
                                {{ $audit['placeholders'] }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-slate-500">Gate</dt>
                            <dd class="font-bold {{ $audit['content_gate_ready'] ? 'text-emerald-700' : 'text-red-700' }}">
                                {{ $audit['content_gate_ready'] ? 'Ready' : 'Blocked' }}
                            </dd>
                        </div>
                    </dl>

                    @if ($audit['errors'] !== [])
                        <details class="mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-800">
                            <summary class="cursor-pointer font-black">
                                Readiness issues
                            </summary>

                            <ul class="mt-2 list-disc space-y-1 pl-5">
                                @foreach ($audit['errors'] as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </details>
                    @endif
                </article>
            @endforeach
        </section>

        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 p-6">
                <h2 class="text-lg font-black text-slate-900">
                    Cohort readiness
                </h2>

                <p class="mt-1 text-sm text-slate-600">
                    Students without cohorts: {{ $studentsWithoutCohorts }} ·
                    Unverified students: {{ $unverifiedStudents }}
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left">Cohort</th>
                            <th class="px-4 py-3 text-left">Eligible</th>
                            <th class="px-4 py-3 text-left">Diagnostic</th>
                            <th class="px-4 py-3 text-left">Post-test</th>
                            <th class="px-4 py-3 text-left">Submissions</th>
                            <th class="px-4 py-3 text-left">Window</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse ($cohorts as $cohort)
                            @php
                                $cohortReasons = $submissionReasons
                                    ->get($cohort->cohort_id, collect());
                            @endphp

                            <tr>
                                <td class="px-4 py-3 font-bold">
                                    {{ $cohort->cohort_name }}
                                </td>

                                <td class="px-4 py-3">
                                    {{ $cohort->eligible_students_count }}
                                </td>

                                <td class="px-4 py-3">
                                    {{ $cohort->diagnostic_completed_count }}
                                    complete /
                                    {{ $cohort->diagnostic_incomplete_count }}
                                    incomplete
                                </td>

                                <td class="px-4 py-3">
                                    {{ $cohort->post_test_completed_count }}
                                    complete /
                                    {{ $cohort->post_test_incomplete_count }}
                                    incomplete
                                </td>

                                <td class="px-4 py-3">
                                    @forelse ($cohortReasons as $reason)
                                        <div>
                                            {{ str($reason->submission_reason)->replace('_', ' ')->title() }}:
                                            <span class="font-bold">
                                                {{ $reason->total }}
                                            </span>
                                        </div>
                                    @empty
                                        <span class="text-slate-400">None</span>
                                    @endforelse
                                </td>

                                <td class="px-4 py-3">
                                    {{ str($cohort->postTestWindowStatus())->title() }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-slate-500">
                                    No active cohorts exist.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-black text-slate-900">
                Submission reasons
            </h2>

            <p class="mt-1 text-sm text-slate-600">
                Completed attempts keep their recorded submission reason.
                Missing response rows remain a data-integrity issue and are not
                treated as unanswered items.
            </p>

            <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @php
                    $reasonTotals = $submissionReasons
                        ->flatten(1)
                        ->groupBy('submission_reason')
                        ->map(fn ($rows) => $rows->sum('total'));
                @endphp

                @forelse ($reasonTotals as $reason => $total)
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-xs font-black uppercase tracking-wider text-slate-500">
                            {{ str($reason)->replace('_', ' ')->title() }}
                        </p>

                        <p class="mt-1 text-2xl font-black text-slate-900">
                            {{ $total }}
                        </p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">
                        No completed submissions have a recorded reason yet.
                    </p>
                @endforelse
            </div>
        </section>

        <section class="rounded-3xl border border-amber-200 bg-amber-50 p-6">
            <h2 class="text-lg font-black text-amber-950">
                Change pilot phase
            </h2>

            <p class="mt-1 text-sm text-amber-900">
                Follow the normal sequence. Once official collection starts,
                research-form publishing and cohort post-test windows are locked.
            </p>

            <form
                method="POST"
                action="{{ route('admin.pilot.update') }}"
                class="mt-5 grid gap-4 md:grid-cols-2"
            >
                @csrf
                @method('PATCH')

                <input
                    type="hidden"
                    name="lock_version"
                    value="{{ $control->lock_version }}"
                >

                <label class="block">
                    <span class="text-sm font-bold text-slate-700">
                        Next phase
                    </span>

                    <select
                        name="phase"
                        required
                        class="mt-1 w-full rounded-xl border-slate-300"
                    >
                        @foreach (\App\Models\PilotControl::PHASES as $phase)
                            <option
                                value="{{ $phase }}"
                                @selected(old('phase', $control->phase) === $phase)
                            >
                                {{ str($phase)->replace('_', ' ')->title() }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="text-sm font-bold text-slate-700">
                        Current password
                    </span>

                    <input
                        type="password"
                        name="current_password"
                        required
                        autocomplete="current-password"
                        class="mt-1 w-full rounded-xl border-slate-300"
                    >
                </label>

                <label class="block md:col-span-2">
                    <span class="text-sm font-bold text-slate-700">
                        Reason
                    </span>

                    <textarea
                        name="reason"
                        required
                        minlength="10"
                        maxlength="500"
                        rows="3"
                        class="mt-1 w-full rounded-xl border-slate-300"
                    >{{ old('reason') }}</textarea>
                </label>

                <label class="flex items-start gap-3 md:col-span-2">
                    <input
                        type="checkbox"
                        name="emergency_override"
                        value="1"
                        @checked(old('emergency_override'))
                        class="mt-1 rounded border-slate-300"
                    >

                    <span class="text-sm text-amber-950">
                        Emergency transition. Use this only after official
                        collection has started and the normal sequence cannot
                        safely represent the required shutdown. The action is
                        recorded separately in the administrative audit history.
                    </span>
                </label>

                <div class="md:col-span-2">
                    <button
                        type="submit"
                        class="rounded-xl bg-primary px-5 py-3 font-black text-white focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/25"
                    >
                        Update Pilot Phase
                    </button>
                </div>
            </form>
        </section>
    </div>
</x-app-layout>
