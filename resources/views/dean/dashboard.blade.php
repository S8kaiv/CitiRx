<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Dean workspace" description="Review student support, official names, and research forms.">
            <x-slot:actions>
                <x-ui.button variant="secondary" :href="route('dean.practice-questions.index')">Practice questions</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    @php
        $showInterventions = $view === 'interventions';
        $status = $filters['status'] ?? 'open';
        $query = array_filter($filters, 'filled');
    @endphp

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm">
            <h2 class="font-semibold text-ink">Needs review</h2>

            <a class="inline-flex min-h-11 items-center gap-2 text-primary-ink underline underline-offset-4"
                href="{{ route('dean.research-forms.index') }}">
                Research reviews <x-ui.badge>{{ $pendingReviews }}</x-ui.badge>
            </a>
            <a class="inline-flex min-h-11 items-center gap-2 text-primary-ink underline underline-offset-4"
                href="{{ route('dean.names.index') }}">
                Name requests <x-ui.badge>{{ $pendingNames }}</x-ui.badge>
            </a>
            <a class="inline-flex min-h-11 items-center text-primary-ink underline underline-offset-4 lg:hidden"
                href="{{ route('content.pearls.index') }}">
                Micro Pearls
            </a>
        </div>

        @if (session('status'))
            <x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>
        @endif

        <x-ui.card class="space-y-4">
            <nav aria-label="Dean dashboard views" class="flex flex-wrap gap-2">
                <x-ui.button
                    :variant="$showInterventions ? 'secondary' : 'primary'"
                    :href="route('dean.dashboard', [...$query, 'view' => 'students'])"
                    :aria-current="$showInterventions ? null : 'page'"
                >
                    Students
                </x-ui.button>
                <x-ui.button
                    :variant="$showInterventions ? 'primary' : 'secondary'"
                    :href="route('dean.dashboard', [...$query, 'view' => 'interventions'])"
                    :aria-current="$showInterventions ? 'page' : null"
                >
                    Interventions
                </x-ui.button>
            </nav>

            <form method="GET" action="{{ route('dean.dashboard') }}"
                class="grid items-end gap-4 border-b border-line pb-4 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto]">
                <input type="hidden" name="view" value="{{ $view }}">

                <x-ui.field name="cohort_id" label="Cohort" type="select">
                    <option value="">All cohorts</option>
                    @foreach ($cohorts as $cohort)
                        <option value="{{ $cohort->cohort_id }}" @selected(($filters['cohort_id'] ?? null) === $cohort->cohort_id)>
                            {{ $cohort->cohort_name }}
                        </option>
                    @endforeach
                </x-ui.field>

                @if ($showInterventions)
                    @if (isset($query['q']))
                        <input type="hidden" name="q" value="{{ $query['q'] }}">
                    @endif
                    <x-ui.field name="status" label="Intervention status" type="select">
                        <option value="open" @selected($status === 'open')>Open</option>
                        <option value="completed" @selected($status === 'completed')>Completed</option>
                        <option value="all" @selected($status === 'all')>All statuses</option>
                    </x-ui.field>
                @else
                    @if (isset($query['status']))
                        <input type="hidden" name="status" value="{{ $query['status'] }}">
                    @endif
                    <x-ui.field name="q" label="Search students" :value="$filters['q'] ?? null" maxlength="100" />
                @endif

                <div>
                    <x-ui.button type="submit">{{ $showInterventions ? 'Filter' : 'Search' }}</x-ui.button>
                </div>
            </form>

            @if ($showInterventions)
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-lg font-bold text-ink">Interventions</h2>
                    <x-ui.button variant="secondary" :href="route('dean.dashboard', [...$query, 'view' => 'students'])">
                        Find a student to assign
                    </x-ui.button>
                </div>

                @if ($results->isEmpty())
                    <div class="space-y-2 rounded-xl bg-surface-2 p-4 text-sm text-ink-muted">
                        @if ($status === 'open')
                            <p>{{ isset($query['cohort_id']) ? 'No open interventions for this cohort.' : 'No open interventions.' }}</p>
                            <a class="inline-flex min-h-11 items-center text-primary-ink underline underline-offset-4"
                                href="{{ route('dean.dashboard', [...$query, 'status' => 'all']) }}">
                                View all statuses
                            </a>
                        @else
                            <p>No interventions match these filters.</p>
                        @endif
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[600px] text-left text-sm text-ink">
                            <thead>
                                <tr class="border-b border-line">
                                    <th scope="col" class="p-3">Student / cohort</th>
                                    <th scope="col" class="p-3">Faculty</th>
                                    <th scope="col" class="p-3">Support</th>
                                    <th scope="col" class="p-3">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($results as $item)
                                    <tr class="border-b border-line">
                                        <td class="p-3">
                                            {{ $item->targetUser?->fullName() ?? 'Unavailable student' }}
                                            <div class="text-ink-muted">{{ $item->targetUser?->cohort?->cohort_name ?? 'No cohort' }}</div>
                                        </td>
                                        <td class="p-3">{{ $item->faculty?->fullName() ?? 'Unavailable faculty' }}</td>
                                        <td class="max-w-sm space-y-1 break-words p-3">
                                            <p class="font-semibold">
                                                {{ \App\Models\FacultyIntervention::TYPE_LABELS[$item->intervention_type]
                                                    ?? ucfirst(str_replace('_', ' ', $item->intervention_type)) }}
                                            </p>
                                            <p class="text-ink-muted">{{ $item->domain?->domain_name ?? 'Unknown domain' }}</p>
                                            @if ($item->competency)
                                                <p class="text-ink-muted">
                                                    {{ $item->competency->competency_code }} {{ $item->competency->title }}
                                                </p>
                                            @endif
                                            @if (filled($item->custom_message))
                                                <p class="whitespace-pre-line">{{ $item->custom_message }}</p>
                                            @endif
                                        </td>
                                        <td class="p-3">
                                            <x-ui.badge>{{ $item->is_completed ? 'Completed' : 'Open' }}</x-ui.badge>
                                            <div class="mt-2 space-y-1 text-xs text-ink-muted">
                                                @if ($item->assigned_at)
                                                    <p>Assigned {{ $item->assigned_at->format('M j, Y g:i A') }}</p>
                                                @endif
                                                @if ($item->completed_at)
                                                    <p>Completed {{ $item->completed_at->format('M j, Y g:i A') }}</p>
                                                @endif
                                            </div>
                                            @unless ($item->is_completed)
                                                <form method="POST" class="mt-2"
                                                    action="{{ route('dean.interventions.complete', ['intervention' => $item->intervention_id]) }}"
                                                    data-confirm="Mark this intervention complete?">
                                                    @csrf
                                                    @method('PATCH')
                                                    <x-ui.button variant="secondary" type="submit">Mark complete</x-ui.button>
                                                </form>
                                            @endunless
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            @else
                <h2 class="text-lg font-bold text-ink">Student readiness</h2>
                <p class="text-sm text-ink-muted">Open a student record to review readiness or assign an intervention.</p>

                @if ($results->isNotEmpty())
                    <div aria-hidden="true"
                        class="hidden grid-cols-4 gap-4 rounded-lg bg-surface-2 px-3 py-2 text-sm font-semibold text-ink-muted md:grid">
                        <span>Student</span>
                        <span>Cohort</span>
                        <span>Readiness status</span>
                        <span class="text-right">Action</span>
                    </div>
                @endif

                <ul class="divide-y divide-line">
                    @forelse ($results as $student)
                        <li class="grid gap-2 px-3 py-3 text-sm md:grid-cols-4 md:items-center md:gap-4">
                            <p class="min-w-0 break-words font-semibold text-ink">{{ $student->fullName() }}</p>
                            <p class="min-w-0 break-words text-ink-muted">{{ $student->cohort?->cohort_name ?? 'No cohort' }}</p>
                            <div>
                                <x-ui.badge>
                                    {{ $student->is_diagnostic_completed
                                        ? ($student->knowledge_states_count > 0 ? 'Readiness available' : 'Readiness pending')
                                        : 'Diagnostic pending' }}
                                </x-ui.badge>
                            </div>
                            <div class="mt-1 md:mt-0 md:justify-self-end">
                                <x-ui.button variant="secondary" :href="route('dean.students.show', $student)"
                                    :aria-label="'View student '.$student->fullName()">
                                    View student
                                </x-ui.button>
                            </div>
                        </li>
                    @empty
                        <li class="rounded-xl bg-surface-2 p-4 text-sm text-ink-muted">No students match these filters.</li>
                    @endforelse
                </ul>
            @endif

            {{ $results->links() }}
        </x-ui.card>
    </div>
</x-app-layout>
