<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="$cohort->cohort_name" :description="$cohort->academic_year">
            <x-slot:actions>
                <x-ui.button variant="secondary" :href="route($routePrefix.'.index')">Back to cohorts</x-ui.button>
                @if (auth()->user()->role === 'dean' && ! $cohort->archived_at)
                    <x-ui.button :href="route('dean.cohorts.assignments', ['cohort_id' => $cohort->cohort_id])">Assign students</x-ui.button>
                @endif
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        @if (auth()->user()->role === 'dean' && session('status'))
            <x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>
        @endif
        <x-ui.card>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-bold text-ink">Cohort details</h2>
                <x-ui.badge :variant="$cohort->archived_at ? 'neutral' : 'success'">
                    {{ $cohort->archived_at ? 'Archived' : 'Active' }}
                </x-ui.badge>
            </div>
            <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
                <div><dt class="text-ink-muted">Students</dt><dd class="mt-1 font-semibold text-ink">{{ $cohort->students_count }}</dd></div>
                <div><dt class="text-ink-muted">Diagnostic completed</dt><dd class="mt-1 font-semibold text-ink">{{ $cohort->diagnostic_completed_count }} of {{ $cohort->students_count }}</dd></div>
                <div><dt class="text-ink-muted">Target PhLE date</dt><dd class="mt-1 font-semibold text-ink">{{ $cohort->target_phle_date?->format('M j, Y') ?? 'Not set' }}</dd></div>
                <div><dt class="text-ink-muted">Post-test window</dt><dd class="mt-1 font-semibold text-ink">{{ ucfirst($cohort->postTestWindowStatus()) }}</dd></div>
            </dl>
            @if ($cohort->post_test_opens_at && $cohort->post_test_closes_at)
                <p class="mt-4 text-sm text-ink-muted">
                    Scheduled {{ $cohort->post_test_opens_at->copy()->timezone('Asia/Manila')->format('M j, Y g:i A') }}
                    to {{ $cohort->post_test_closes_at->copy()->timezone('Asia/Manila')->format('M j, Y g:i A') }} (Asia/Manila).
                </p>
            @endif
            @if (auth()->user()->role === 'dean')
                <p class="mt-4 text-sm text-ink-muted">Use Assign students to manage membership. Assessment-window controls remain administrator-only.</p>
            @else
                <p class="mt-4 text-sm text-ink-muted">Only administrators can change cohort details, membership, or assessment windows.</p>
            @endif
        </x-ui.card>

        <x-ui.card>
            <h2 class="text-lg font-bold text-ink">Assigned faculty</h2>
            <ul class="mt-3 divide-y divide-line text-sm text-ink">
                @forelse ($faculty as $member)
                    <li class="break-words py-3">{{ $member->fullName() }}</li>
                @empty
                    <li class="py-3 text-ink-muted">No faculty assigned to this cohort.</li>
                @endforelse
            </ul>
        </x-ui.card>

        <x-ui.card :padded="false">
            <div class="border-b border-line p-4 sm:p-6">
                <h2 class="text-lg font-bold text-ink">Students</h2>
                <p class="mt-1 text-sm text-ink-muted">Only students currently assigned to this cohort are listed.</p>
            </div>
            <ul class="divide-y divide-line">
                @forelse ($students as $student)
                    <li class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        <div class="min-w-0">
                            <p class="break-words font-semibold text-ink">{{ $student->fullName() }}</p>
                            <p class="mt-1 break-words text-sm text-ink-muted">Student ID: {{ $student->student_id ?? 'Not assigned' }}</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <x-ui.badge :variant="$student->is_diagnostic_completed ? 'success' : 'neutral'">
                                {{ $student->is_diagnostic_completed ? 'Diagnostic completed' : 'Diagnostic not completed' }}
                            </x-ui.badge>
                            @if (auth()->user()->role === 'dean')
                                <x-ui.button variant="secondary" :href="route('dean.students.show', $student)"
                                    :aria-label="'View student '.$student->fullName()">
                                    View student
                                </x-ui.button>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="p-6 text-sm text-ink-muted">No students assigned to this cohort.</li>
                @endforelse
            </ul>
        </x-ui.card>

        {{ $students->links() }}
    </div>
</x-app-layout>
