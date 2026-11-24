<x-app-layout surface="admin">
    <x-slot name="header">
        <x-admin.page-header :title="$cohort->cohort_name" :description="$cohort->academic_year" />
    </x-slot>

    @php
        $window = $cohort->postTestWindowStatus();
        $isArchived = $cohort->archived_at !== null;
        $lastAction = old('_cohort_action');
        $archiveBlocked = ! $isArchived && $collectionStarted && in_array($window, ['open', 'scheduled'], true);
    @endphp

    <div class="mx-auto max-w-7xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        <x-admin.flash />

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <x-ui.badge :variant="$isArchived ? 'neutral' : 'success'">
                    {{ $isArchived ? 'Archived' : 'Active' }}
                </x-ui.badge>
                <p class="text-sm text-ink-muted">
                    Target PhLE: {{ $cohort->target_phle_date?->format('M j, Y') ?? 'Not set' }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <x-ui.button variant="secondary" :href="route('admin.cohorts.index')">All cohorts</x-ui.button>
                @unless ($isArchived)
                    <x-ui.button variant="secondary" :href="route('admin.cohorts.edit', $cohort)">Edit cohort</x-ui.button>
                @endunless
            </div>
        </div>

        <dl aria-label="Cohort assessment summary" class="grid gap-3 sm:grid-cols-3">
            @foreach ([
                ['label' => 'Enrolled students', 'count' => $counts['students'], 'suffix' => '', 'started' => null],
                ['label' => 'Form A · Diagnostic', 'count' => $counts['diagnostic_completed'], 'suffix' => 'completed', 'started' => $counts['diagnostic_started']],
                ['label' => 'Form B · Mock board', 'count' => $counts['mock_completed'], 'suffix' => 'finalized', 'started' => $counts['mock_started']],
            ] as $metric)
                <div class="min-w-0 rounded-2xl border border-line bg-surface p-4">
                    <dt class="text-sm font-bold text-ink-muted">{{ $metric['label'] }}</dt>
                    <dd class="mt-1 text-ink">
                        <span class="font-display text-2xl font-extrabold tabular-nums">{{ number_format($metric['count']) }}</span>
                        <span class="text-sm">{{ $metric['suffix'] }}</span>
                    </dd>
                    @if ($metric['started'] !== null)
                        <dd class="mt-1 text-sm text-ink-muted">{{ number_format($metric['started']) }} started</dd>
                    @endif
                </div>
            @endforeach
        </dl>

        <div class="grid items-start gap-5 xl:grid-cols-3">
            <x-admin.panel id="cohort-roster" title="Student roster" class="min-w-0 xl:col-span-2">
                <x-slot:actions>
                    @unless ($isArchived)
                        <x-ui.button variant="secondary"
                            :href="route('admin.users.index', ['assign' => 1, 'no_cohort' => 1])">
                            Find unassigned students
                        </x-ui.button>
                    @endunless
                </x-slot:actions>

                <form method="GET" action="{{ route('admin.cohorts.show', $cohort) }}"
                    class="grid items-end gap-2 sm:grid-cols-[minmax(0,1fr)_auto]">
                    <x-ui.field name="q" id="cohort-roster-search" type="search" label="Find a student"
                        :value="$rosterSearch" maxlength="100" placeholder="First name, surname, email, or ID" />
                    <x-ui.button type="submit">Search</x-ui.button>
                </form>

                @if ($rosterSearch !== '')
                    <a href="{{ route('admin.cohorts.show', $cohort) }}"
                        class="inline-flex min-h-11 items-center text-sm font-bold text-primary-ink underline underline-offset-4">
                        Clear search
                    </a>
                @endif

                <ul class="mt-3 divide-y divide-line border-t border-line">
                    @forelse ($students as $student)
                        <li class="grid items-start gap-2 py-3 sm:grid-cols-[minmax(0,1fr)_minmax(0,0.8fr)_5rem] sm:items-center sm:gap-4">
                            <div class="min-w-0">
                                <p class="break-words font-bold text-ink">{{ $student->fullName() }}</p>
                                <p class="break-all text-sm text-ink-muted">{{ $student->email }}</p>
                                @if ($student->student_id)
                                    <p class="text-xs text-ink-muted">ID {{ $student->student_id }}</p>
                                @endif
                            </div>

                            <div class="flex min-w-0 flex-wrap gap-1.5">
                                <x-ui.badge :variant="$student->form_a_complete ? 'success' : 'neutral'">
                                    {{ $student->form_a_complete ? 'Form A completed' : 'Form A pending' }}
                                </x-ui.badge>
                                <x-ui.badge :variant="$student->form_b_finalized ? 'success' : 'neutral'">
                                    {{ $student->form_b_finalized ? 'Form B finalized' : 'Form B pending' }}
                                </x-ui.badge>
                            </div>

                            <div class="sm:justify-self-end">
                                <x-ui.button variant="ghost"
                                    :href="route('admin.users.edit', ['userId' => $student->user_id])"
                                    :aria-label="'Manage '.$student->fullName()">
                                    Manage
                                </x-ui.button>
                            </div>
                        </li>
                    @empty
                        <li class="py-5 text-sm text-ink-muted">
                            {{ $rosterSearch === ''
                                ? 'No students are assigned to this cohort.'
                                : 'No students in this cohort match your search.' }}
                        </li>
                    @endforelse
                </ul>

                <x-admin.pager :paginator="$students" label="Student roster" />
            </x-admin.panel>

            <div class="min-w-0 space-y-5">
                <x-admin.panel id="form-b-window" title="Form B window">
                    <x-slot:actions>
                        <x-ui.badge :variant="match ($window) {
                            'open' => 'success',
                            'scheduled' => 'info',
                            default => 'neutral',
                        }">
                            {{ $window === 'closed' ? 'Unscheduled' : ucfirst($window) }}
                        </x-ui.badge>
                    </x-slot:actions>

                    <div class="space-y-3">
                        <dl class="grid gap-3 text-sm">
                            <div>
                                <dt class="text-ink-muted">Opens at</dt>
                                <dd class="font-bold text-ink">
                                    {{ $cohort->post_test_opens_at?->format('M j, Y g:i A') ?? 'Not set' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-ink-muted">Closes at</dt>
                                <dd class="font-bold text-ink">
                                    {{ $cohort->post_test_closes_at?->format('M j, Y g:i A') ?? 'Not set' }}
                                </dd>
                            </div>
                        </dl>

                        <p class="text-xs text-ink-muted">
                            Times in {{ config('app.timezone') }}. Form B also requires the study-wide Post Test Open phase.
                        </p>

                        @if ($isArchived)
                            <p class="text-sm text-ink-muted">Archived cohort schedules are read-only.</p>
                        @elseif ($collectionStarted)
                            <x-ui.alert variant="warning">
                                The schedule is locked because official collection has started.
                            </x-ui.alert>
                        @else
                            <x-admin.disclosure title="Edit schedule" :open="$lastAction === 'window'">
                                <form method="POST" action="{{ route('admin.cohorts.post-test-window', $cohort) }}"
                                    class="space-y-3 p-4">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="_cohort_action" value="window">

                                    <x-ui.field name="post_test_opens_at" type="datetime-local" label="Opens at"
                                        :value="$cohort->post_test_opens_at?->format('Y-m-d\TH:i')" required />
                                    <x-ui.field name="post_test_closes_at" type="datetime-local" label="Closes at"
                                        :value="$cohort->post_test_closes_at?->format('Y-m-d\TH:i')" required />

                                    <div class="space-y-1.5">
                                        <label for="window-reason" class="block text-sm font-bold text-ink">Reason</label>
                                        <textarea id="window-reason" name="reason" rows="2" required minlength="5" maxlength="500"
                                            class="ui-field-control">{{ $lastAction === 'window' ? old('reason') : '' }}</textarea>
                                        @if ($lastAction === 'window')
                                            @error('reason')
                                                <p class="text-sm text-danger-ink" role="alert">{{ $message }}</p>
                                            @enderror
                                        @endif
                                    </div>

                                    <x-ui.button type="submit">Save schedule</x-ui.button>
                                </form>
                            </x-admin.disclosure>

                            @if ($cohort->post_test_opens_at || $cohort->post_test_closes_at)
                                <x-admin.disclosure title="Clear schedule" :open="$lastAction === 'clear'">
                                    <form method="POST" action="{{ route('admin.cohorts.post-test-window', $cohort) }}"
                                        data-confirm="Clear the Form B schedule for this cohort?" class="space-y-3 p-4">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="_cohort_action" value="clear">
                                        <input type="hidden" name="clear_window" value="1">

                                        <div class="space-y-1.5">
                                            <label for="clear-reason" class="block text-sm font-bold text-ink">Reason for clearing</label>
                                            <textarea id="clear-reason" name="reason" rows="2" required minlength="5" maxlength="500"
                                                class="ui-field-control">{{ $lastAction === 'clear' ? old('reason') : '' }}</textarea>
                                            @if ($lastAction === 'clear')
                                                @error('reason')
                                                    <p class="text-sm text-danger-ink" role="alert">{{ $message }}</p>
                                                @enderror
                                            @endif
                                        </div>

                                        <x-ui.button type="submit" variant="danger">Clear schedule</x-ui.button>
                                    </form>
                                </x-admin.disclosure>
                            @endif
                        @endif
                    </div>
                </x-admin.panel>

                <x-admin.panel id="cohort-faculty" title="Assigned faculty">
                    <ul class="divide-y divide-line">
                        @forelse ($faculty as $member)
                            <li class="flex flex-wrap items-center justify-between gap-2 py-2 first:pt-0 last:pb-0">
                                <div class="min-w-0">
                                    <p class="break-words font-bold text-ink">{{ $member->fullName() }}</p>
                                    <p class="break-all text-xs text-ink-muted">{{ $member->email }}</p>
                                </div>
                                <x-ui.button variant="ghost"
                                    :href="route('admin.users.edit', ['userId' => $member->user_id])"
                                    :aria-label="'Manage '.$member->fullName()">
                                    Manage
                                </x-ui.button>
                            </li>
                        @empty
                            <li class="text-sm text-ink-muted">No faculty assigned.</li>
                        @endforelse
                    </ul>
                </x-admin.panel>
            </div>
        </div>

        <x-admin.panel id="cohort-history" title="Recent changes">
            <x-slot:actions>
                <x-ui.button variant="ghost" :href="route('admin.audit-logs.index', [
                    'target_type' => \App\Models\Cohort::class,
                    'target_id' => $cohort->cohort_id,
                ])">
                    View cohort audit history
                </x-ui.button>
            </x-slot:actions>

            <ol class="divide-y divide-line">
                @forelse ($recentAudits as $log)
                    <li class="py-2 first:pt-0 last:pb-0">
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <p class="break-words font-bold text-ink">
                                {{ str($log->action)->replace(['.', '_'], ' ')->headline() }}
                            </p>
                            <time datetime="{{ $log->created_at->toIso8601String() }}"
                                class="text-xs tabular-nums text-ink-muted">
                                {{ $log->created_at->format('M j, Y g:i A') }}
                            </time>
                        </div>
                        <p class="text-sm text-ink-muted">{{ $log->actorDisplayName() }}</p>
                    </li>
                @empty
                    <li class="text-sm text-ink-muted">No administrative changes recorded.</li>
                @endforelse
            </ol>
        </x-admin.panel>

        <x-admin.disclosure :title="$isArchived ? 'Restore cohort' : 'Archive cohort'" :open="$lastAction === 'lifecycle'">
            <div class="space-y-3 p-4">
                @if ($archiveBlocked)
                    <p class="text-sm text-ink-muted">
                        This cohort has a scheduled or open official Form B window. It can be archived after the window ends.
                    </p>
                @else
                    <p class="text-sm text-ink-muted">
                        {{ $isArchived
                            ? 'Restoring makes the cohort active again. Form B follows the saved schedule and study phase.'
                            : 'Archiving preserves existing assignments and research history.' }}
                    </p>

                    <form method="POST"
                        action="{{ route($isArchived ? 'admin.cohorts.unarchive' : 'admin.cohorts.archive', $cohort) }}"
                        data-confirm="{{ $isArchived ? 'Restore this cohort?' : 'Archive this cohort?' }}"
                        class="space-y-3">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="_cohort_action" value="lifecycle">

                        <div class="space-y-1.5">
                            <label for="lifecycle-reason" class="block text-sm font-bold text-ink">Reason</label>
                            <textarea id="lifecycle-reason" name="reason" rows="2" required minlength="5" maxlength="500"
                                class="ui-field-control">{{ $lastAction === 'lifecycle' ? old('reason') : '' }}</textarea>
                            @if ($lastAction === 'lifecycle')
                                @error('reason')
                                    <p class="text-sm text-danger-ink" role="alert">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>

                        <x-ui.button type="submit" :variant="$isArchived ? 'secondary' : 'danger'">
                            {{ $isArchived ? 'Restore cohort' : 'Archive cohort' }}
                        </x-ui.button>
                    </form>
                @endif
            </div>
        </x-admin.disclosure>
    </div>
</x-app-layout>
