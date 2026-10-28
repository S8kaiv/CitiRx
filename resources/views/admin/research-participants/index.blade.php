<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-black uppercase tracking-[0.18em] text-primary">
                Official research scope
            </p>

            <h1 class="text-xl font-black text-slate-900">
                Research Participants
            </h1>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div
                    role="status"
                    class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800"
                >
                    {{ session('status') }}
                </div>
            @endif

            <section class="rounded-3xl border-2 border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-black text-slate-900">
                    Participant-scope policy
                </h2>

                <p class="mt-2 text-sm text-slate-600">
                    Only students explicitly marked <strong>Included</strong>
                    enter official analytics. Eligible, excluded, withdrawn,
                    development, and unregistered accounts remain outside the
                    primary report. Soft deletion does not change research status.
                </p>
            </section>

            <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                @foreach ([
                    \App\Models\ResearchParticipant::STATUS_INCLUDED,
                    \App\Models\ResearchParticipant::STATUS_ELIGIBLE,
                    \App\Models\ResearchParticipant::STATUS_EXCLUDED,
                    \App\Models\ResearchParticipant::STATUS_WITHDRAWN,
                    \App\Models\ResearchParticipant::STATUS_DEVELOPMENT,
                    \App\Models\ResearchParticipant::STATUS_UNREGISTERED,
                ] as $status)
                    <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="text-xs font-black uppercase tracking-wider text-slate-500">
                            {{ \App\Models\ResearchParticipant::label($status) }}
                        </p>

                        <p class="mt-2 text-2xl font-black text-slate-900">
                            {{ $statusCounts[$status] ?? 0 }}
                        </p>
                    </article>
                @endforeach
            </section>

            <form
                method="GET"
                class="grid gap-3 rounded-3xl border-2 border-slate-200 bg-white p-5 md:grid-cols-4"
            >
                <input
                    name="q"
                    value="{{ $filters['q'] }}"
                    placeholder="Name, email, or student ID"
                    class="rounded-xl border-slate-300"
                >

                <select name="status" class="rounded-xl border-slate-300">
                    <option value="">All participant statuses</option>

                    @foreach ([...$statuses, \App\Models\ResearchParticipant::STATUS_UNREGISTERED] as $status)
                        <option
                            value="{{ $status }}"
                            @selected($filters['status'] === $status)
                        >
                            {{ \App\Models\ResearchParticipant::label($status) }}
                        </option>
                    @endforeach
                </select>

                <select name="cohort_id" class="rounded-xl border-slate-300">
                    <option value="">All cohorts</option>

                    @foreach ($cohorts as $cohort)
                        <option
                            value="{{ $cohort->cohort_id }}"
                            @selected($filters['cohort_id'] === $cohort->cohort_id)
                        >
                            {{ $cohort->cohort_name }} — {{ $cohort->academic_year }}
                            {{ $cohort->archived_at ? '(Archived)' : '' }}
                        </option>
                    @endforeach
                </select>

                <select name="account_status" class="rounded-xl border-slate-300">
                    <option value="active" @selected($filters['account_status'] === 'active')>
                        Active accounts
                    </option>
                    <option value="deleted" @selected($filters['account_status'] === 'deleted')>
                        Deleted accounts
                    </option>
                    <option value="all" @selected($filters['account_status'] === 'all')>
                        All accounts
                    </option>
                </select>

                <div class="flex gap-2 md:col-span-4">
                    <button class="rounded-xl bg-primary px-4 py-2 font-bold text-white">
                        Apply Filters
                    </button>

                    <a
                        href="{{ route('admin.research-participants.index') }}"
                        class="rounded-xl border-2 border-slate-200 px-4 py-2 font-bold text-slate-700"
                    >
                        Reset
                    </a>
                </div>
            </form>

            <div class="overflow-x-auto rounded-3xl border-2 border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left">
                        <tr>
                            <th class="p-4">Student</th>
                            <th class="p-4">Cohort</th>
                            <th class="p-4">Participant status</th>
                            <th class="p-4">Last changed</th>
                            <th class="p-4">Account</th>
                            <th class="p-4"></th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse ($participants as $student)
                            @php
                                $scopeStatus = $student->researchParticipant?->status
                                    ?? \App\Models\ResearchParticipant::STATUS_UNREGISTERED;
                            @endphp

                            <tr>
                                <td class="p-4">
                                    <p class="font-bold text-slate-900">
                                        {{ $student->fullName() }}
                                    </p>
                                    <p class="text-xs text-slate-500">
                                        {{ $student->student_id ?? 'No student ID' }} ·
                                        {{ $student->email }}
                                    </p>
                                </td>

                                <td class="p-4">
                                    {{ $student->cohort?->cohort_name ?? 'Unassigned' }}
                                </td>

                                <td class="p-4 font-bold">
                                    {{ \App\Models\ResearchParticipant::label($scopeStatus) }}
                                </td>

                                <td class="p-4 text-slate-600">
                                    @if ($student->researchParticipant)
                                        {{ $student->researchParticipant->changed_at->format('M j, Y g:i A') }}
                                        <div class="text-xs">
                                            by {{ $student->researchParticipant->changedBy?->fullName() ?? 'Unavailable administrator' }}
                                        </div>
                                    @else
                                        —
                                    @endif
                                </td>

                                <td class="p-4">
                                    {{ $student->trashed() ? 'Soft-deleted' : 'Active' }}
                                </td>

                                <td class="p-4 text-right">
                                    <a
                                        href="{{ route('admin.research-participants.edit', ['userId' => $student->user_id]) }}"
                                        class="font-bold text-primary"
                                    >
                                        Manage
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-8 text-center text-slate-500">
                                    No students match these filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $participants->links() }}
        </div>
    </div>
</x-app-layout>
