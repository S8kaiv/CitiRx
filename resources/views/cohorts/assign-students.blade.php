<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Assign students to a cohort" description="Select students and move them to an active cohort in one action.">
            <x-slot:actions>
                <x-ui.button variant="secondary" :href="route('dean.cohorts.index')">Back to cohorts</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    @php
        $selectedStudents = old('student_ids', []);
        $selectedStudents = is_array($selectedStudents) ? $selectedStudents : [];
    @endphp

    <div class="mx-auto max-w-7xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        @if (session('status'))<x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>@endif

        <form method="GET" action="{{ route('dean.cohorts.assignments') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
            @if ($selectedCohortId)<input type="hidden" name="cohort_id" value="{{ $selectedCohortId }}">@endif
            <x-ui.field name="q" label="Find students" :value="$search" maxlength="100" placeholder="Name or student ID" class="sm:min-w-80" />
            <x-ui.button type="submit" variant="secondary">Search</x-ui.button>
            <x-ui.button variant="ghost" :href="route('dean.cohorts.assignments', array_filter(['cohort_id' => $selectedCohortId]))">Clear search</x-ui.button>
        </form>

        @if ($cohorts->isEmpty())
            <x-ui.alert variant="warning">No active cohorts are available. Create a cohort before assigning students.</x-ui.alert>
            <x-ui.button :href="route('dean.cohorts.create')">Create cohort</x-ui.button>
        @endif

        <form method="POST" action="{{ route('dean.cohorts.assign') }}" data-bulk
            data-confirm="Move the selected students to the chosen cohort? Existing assignment locks are checked and every change is recorded."
            class="space-y-4">
            @csrf
            <x-ui.field name="cohort_id" id="dean-cohort-target" label="Move selected students to" type="select" data-cohort-target required :disabled="$cohorts->isEmpty()">
                <option value="">Choose an active cohort</option>
                @foreach ($cohorts as $cohort)
                    <option value="{{ $cohort->cohort_id }}" @selected(old('cohort_id', $selectedCohortId) === $cohort->cohort_id)>{{ $cohort->cohort_name }} · {{ $cohort->academic_year }}</option>
                @endforeach
            </x-ui.field>
            <p class="text-sm text-ink-muted">Only student accounts are listed. Students who started an official research assessment cannot move. Select up to 100 students per action; selection applies to the current page.</p>
            <x-ui.selection-controls />
            @foreach (['student_ids', 'student_ids.*', 'original_cohorts', 'original_cohorts.*'] as $field)
                @foreach ($errors->get($field) as $messages)
                    @foreach ((array) $messages as $message)
                        <p class="text-sm text-danger-ink" role="alert">{{ $message }}</p>
                    @endforeach
                @endforeach
            @endforeach

            <x-ui.card :padded="false">
                <ul class="divide-y divide-line">
                    @forelse ($students as $student)
                        <li class="flex flex-wrap items-center justify-between gap-3 p-4 sm:px-6">
                            <label class="flex min-h-11 min-w-0 flex-1 items-center gap-3">
                                <input type="checkbox" name="student_ids[]" value="{{ $student->user_id }}"
                                    data-row-select data-cohort="{{ $student->cohort_id }}"
                                    @checked(in_array($student->user_id, $selectedStudents, true))
                                    @disabled($student->research_locked || $cohorts->isEmpty())
                                    class="rounded border-line">
                                <span class="min-w-0">
                                    <span class="block break-words font-semibold text-ink">{{ $student->fullName() }}</span>
                                    <span class="block break-words text-sm text-ink-muted">{{ $student->student_id ?? 'No student ID' }} · {{ $student->cohort?->cohort_name ?? 'Unassigned' }}</span>
                                </span>
                            </label>
                            <input type="hidden" name="original_cohorts[{{ $student->user_id }}]" value="{{ $student->cohort_id }}">
                            @if ($student->research_locked)<x-ui.badge variant="warning">Research locked</x-ui.badge>@endif
                        </li>
                    @empty
                        <li class="p-6 text-sm text-ink-muted">No student accounts match this search.</li>
                    @endforelse
                </ul>
            </x-ui.card>

            <x-ui.button type="submit" data-bulk-submit :disabled="true">Assign selected students</x-ui.button>
            <noscript><p class="text-sm text-danger-ink">Enable JavaScript to select students and confirm the assignment.</p></noscript>
        </form>

        {{ $students->links() }}
    </div>
</x-app-layout>
