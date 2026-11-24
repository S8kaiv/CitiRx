<x-app-layout surface="admin">
    <x-slot name="header">
        <x-admin.page-header
            title="Research Participants">
            <x-slot:actions>
                <a
                    href="{{ route(
                        'admin.research-analytics.index',
                    ) }}"
                    class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-bold text-slate-700 transition-colors hover:bg-slate-50 focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/25"
                >
                    Open analytics
                </a>
            </x-slot:actions>
        </x-admin.page-header>
    </x-slot>

    <div class="pt-6 pb-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-admin.disclosure title="Participant scope at a glance">
                <div class="min-w-0 space-y-3 p-4">
                    <p class="text-sm text-ink-muted">Official analytics includes students marked Included. Account deletion and research status are managed independently.</p>
                    <dl class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        @foreach ($statusCounts as $status => $count)
                            <div class="rounded-xl bg-surface-2 p-3">
                                <dt class="text-sm text-ink-muted">{{ \App\Models\ResearchParticipant::label($status) }}</dt>
                                <dd class="font-bold text-ink">{{ $count }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </x-admin.disclosure>

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

                <div class="rx-admin-actions flex flex-wrap gap-2 md:col-span-4">
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
                <div class="rx-admin-table-scroll" role="region" aria-label="Scrollable data table" tabindex="0">
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
            </div>

            <x-admin.pager :paginator="$participants" label="Participants" />
        </div>
    </div>
</x-app-layout>
