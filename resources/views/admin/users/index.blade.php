<x-app-layout surface="admin">
    <x-slot name="header">
        <x-admin.page-header title="Users" />
    </x-slot>

    @php
        $assigning = (bool) $filters['assign'];
        $studentsOnly = $filters['role'] === 'student';
        $unassigned = (bool) $filters['no_cohort'];
        $activeCohorts = $cohorts->whereNull('archived_at');
        $canSelect = $assigning && $activeCohorts->isNotEmpty();
        $canAssign = $canSelect && $users->getCollection()->contains(fn ($student) => ! $student->research_locked);

        $roleFilterActive = filled($filters['role']) && ! $studentsOnly;
        $moreFiltersOpen = filled($filters['verification']) || $filters['status'] !== 'active' || $roleFilterActive;
        $hasFilters = $moreFiltersOpen || filled($filters['q']) || filled($filters['cohort_id']) || $unassigned;
        $resetFilters = $assigning ? ['assign' => 1] : ['role' => $studentsOnly ? 'student' : null];

        $userGroups = $studentsOnly
            ? $users->getCollection()->groupBy(fn ($student) => $student->cohort_id ?? 'unassigned')
            : collect([$users->getCollection()]);

        $scopeLinks = [
            [
                'label' => 'All users',
                'selected' => $filters['role'] === null && ! $unassigned,
                'params' => ['role' => null, 'no_cohort' => null],
            ],
            [
                'label' => 'Students',
                'selected' => $studentsOnly && ! $unassigned,
                'params' => ['role' => 'student', 'no_cohort' => null],
            ],
            [
                'label' => 'Unassigned students',
                'selected' => $studentsOnly && $unassigned,
                'params' => ['role' => 'student', 'cohort_id' => null, 'no_cohort' => 1],
            ],
        ];
    @endphp

    <div class="mx-auto max-w-7xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        <x-admin.flash />

        <section aria-labelledby="users-heading" class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 id="users-heading" class="font-display text-xl font-extrabold text-ink">
                        {{ $assigning ? 'Assign students to a cohort' : 'Account directory' }}
                    </h2>
                    <p class="mt-1 text-sm text-ink-muted">
                        {{ number_format($users->total()) }} matching accounts
                    </p>
                </div>

                @if ($assigning)
                    <x-ui.button variant="secondary"
                        :href="route('admin.users.index', [...$filters, 'assign' => null])">
                        Back to users
                    </x-ui.button>
                @else
                    <x-ui.button variant="secondary"
                        :href="route('admin.users.index', [...($studentsOnly ? $filters : []), 'assign' => 1])">
                        Assign cohorts
                    </x-ui.button>
                @endif
            </div>

            <form method="GET" action="{{ route('admin.users.index') }}"
                class="space-y-4 rounded-2xl border border-line bg-surface p-4 sm:p-5">
                @if ($assigning)
                    <input type="hidden" name="assign" value="1">
                @else
                    <nav aria-label="User scope" class="grid grid-cols-[1fr_1fr_1.5fr] gap-1 sm:flex sm:flex-wrap sm:gap-2">
                        @foreach ($scopeLinks as $scope)
                            <a href="{{ route('admin.users.index', [...$filters, ...$scope['params']]) }}"
                                @if ($scope['selected']) aria-current="page" @endif
                                class="inline-flex min-h-11 min-w-0 items-center justify-center rounded-lg px-1.5 py-1 text-center text-sm font-bold leading-tight sm:px-3
                                    {{ $scope['selected'] ? 'bg-primary-tint text-primary-ink' : 'text-ink-muted hover:bg-surface-2' }}">
                                {{ $scope['label'] }}
                            </a>
                        @endforeach
                    </nav>
                @endif

                <div class="grid grid-cols-[minmax(0,1fr)_auto] items-end gap-3 md:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_auto]">
                    <div class="col-span-2 min-w-0 md:col-span-1">
                        <x-ui.field id="users-search" name="q" type="search" label="Search users"
                            :value="$filters['q']" maxlength="100" placeholder="Name, email, or student ID" />
                    </div>

                    <div class="min-w-0 space-y-1.5">
                        <label for="users-cohort" class="block text-sm font-bold text-ink">Cohort</label>
                        <select id="users-cohort" name="cohort_id" class="ui-field-control">
                            <option value="">All cohorts</option>
                            <option value="unassigned" @selected($unassigned)>Without a cohort</option>
                            @foreach ($cohorts as $cohort)
                                <option value="{{ $cohort->cohort_id }}"
                                    @selected(! $unassigned && $filters['cohort_id'] === $cohort->cohort_id)>
                                    {{ $cohort->cohort_name }} ({{ $cohort->academic_year }}){{ $cohort->archived_at ? ' · Archived' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <x-ui.button type="submit" class="min-h-11">Apply filters</x-ui.button>
                </div>

                <x-admin.disclosure title="More filters" :open="$moreFiltersOpen">
                    <div class="grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-3">
                        @unless ($assigning)
                            <x-ui.field id="users-role" name="role" type="select" label="Role">
                                <option value="">All roles</option>
                                @foreach (['student' => 'Student', 'faculty' => 'Faculty', 'dean' => 'Dean', 'admin' => 'Administrator'] as $role => $label)
                                    <option value="{{ $role }}" @selected($filters['role'] === $role)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </x-ui.field>
                        @endunless

                        <x-ui.field id="users-verification" name="verification" type="select" label="Email status">
                            <option value="">Any email status</option>
                            <option value="verified" @selected($filters['verification'] === 'verified')>Verified</option>
                            <option value="unverified" @selected($filters['verification'] === 'unverified')>Unverified</option>
                        </x-ui.field>

                        @unless ($assigning)
                            <x-ui.field id="users-status" name="status" type="select" label="Account status">
                                <option value="active" @selected($filters['status'] === 'active')>Active</option>
                                <option value="deleted" @selected($filters['status'] === 'deleted')>Deleted</option>
                                <option value="all" @selected($filters['status'] === 'all')>All accounts</option>
                            </x-ui.field>
                        @endunless
                    </div>
                </x-admin.disclosure>

                @if ($hasFilters)
                    <a href="{{ route('admin.users.index', $resetFilters) }}"
                        class="inline-flex min-h-11 items-center text-sm font-bold text-primary-ink underline underline-offset-4">
                        Clear filters
                    </a>
                @endif
            </form>

            @if ($assigning)
                <form id="bulk-cohort" method="POST" action="{{ route('admin.users.assign-cohort') }}"
                    data-bulk data-confirm="Move the selected students to the chosen cohort? This is recorded in Audit History."
                    class="space-y-4">
                    @csrf

                    @if ($errors->hasAny(['student_ids', 'student_ids.*', 'original_cohorts', 'original_cohorts.*']))
                        <x-ui.alert variant="danger">
                            {{ $errors->first('student_ids') ?: $errors->first('student_ids.*') ?: $errors->first('original_cohorts') ?: $errors->first('original_cohorts.*') }}
                        </x-ui.alert>
                    @endif

                    @if ($canAssign)
                        <div class="space-y-3 rounded-2xl border border-line bg-surface p-4">
                            <p class="text-sm text-ink-muted">
                                Select students on this page and choose their destination cohort. Research-locked students cannot be moved.
                            </p>

                            <x-ui.selection-controls />

                            <div class="grid items-end gap-3 sm:grid-cols-[minmax(0,1fr)_auto]">
                                <x-ui.field id="bulk-cohort-target" name="cohort_id" type="select"
                                    label="Destination cohort" data-cohort-target required>
                                    <option value="">Choose an active cohort</option>
                                    @foreach ($activeCohorts as $cohort)
                                        <option value="{{ $cohort->cohort_id }}"
                                            @selected(old('cohort_id') === $cohort->cohort_id)>
                                            {{ $cohort->cohort_name }} ({{ $cohort->academic_year }})
                                        </option>
                                    @endforeach
                                </x-ui.field>

                                <x-ui.button type="submit" data-bulk-submit disabled>
                                    Assign selected students
                                </x-ui.button>
                            </div>
                        </div>
                    @elseif ($errors->has('cohort_id'))
                        <x-ui.alert variant="danger">{{ $errors->first('cohort_id') }}</x-ui.alert>
                    @elseif ($users->isNotEmpty())
                        <x-ui.alert variant="warning">
                            {{ $activeCohorts->isEmpty()
                                ? 'Create or restore an active cohort before assigning students.'
                                : 'No students on this page can be moved.' }}
                        </x-ui.alert>
                    @endif
            @endif

            @if ($users->isEmpty())
                <p class="rounded-2xl border border-line bg-surface p-6 text-sm text-ink-muted">
                    No users match these filters.
                </p>
            @else
                @foreach ($userGroups as $group)
                    @php($cohort = $group->first()->cohort)

                    <div class="overflow-hidden rounded-2xl border border-line bg-surface">
                        @if ($studentsOnly)
                            <div class="border-b border-line bg-surface-2 px-4 py-3">
                                <h3 class="font-display font-extrabold text-ink">
                                    {{ $cohort?->cohort_name ?? 'Without a cohort' }}
                                </h3>
                                @if ($cohort)
                                    <p class="mt-0.5 text-xs text-ink-muted">
                                        {{ $cohort->academic_year }}{{ $cohort->archived_at ? ' · Archived' : '' }}
                                    </p>
                                @endif
                            </div>
                        @endif

                        <ul class="divide-y divide-line">
                            @foreach ($group as $managedUser)
                                <li class="grid grid-cols-[minmax(0,1fr)_auto] items-start gap-x-3 gap-y-2 px-4 py-3
                                    md:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)_minmax(0,1.1fr)_6rem] md:items-center md:gap-x-4 md:py-4">
                                    <div class="col-start-1 row-start-1 min-w-0">
                                        <p class="break-words text-base font-bold leading-snug text-ink">
                                            {{ $managedUser->fullName() }}
                                        </p>
                                        <p class="mt-0.5 text-sm leading-5 text-ink-muted [overflow-wrap:anywhere]">
                                            {{ $managedUser->email }}
                                        </p>
                                    </div>

                                    <div class="col-span-2 flex min-w-0 flex-wrap items-baseline gap-x-2 gap-y-0.5 text-sm md:col-span-1 md:block">
                                        @if ($studentsOnly)
                                            <span class="text-ink-muted md:block md:text-xs">Student ID</span>
                                            <span class="break-words font-medium tabular-nums text-ink md:block">
                                                {{ $managedUser->student_id ?? 'Not set' }}
                                            </span>
                                        @else
                                            <span class="font-medium text-ink md:block">{{ ucfirst($managedUser->role) }}</span>
                                            @if ($managedUser->role === 'student' || $managedUser->role === 'faculty')
                                                <span class="break-words text-ink-muted md:block md:text-xs">
                                                    {{ $managedUser->cohort?->cohort_name ?? 'Without a cohort' }}
                                                </span>
                                            @endif
                                            @if ($managedUser->student_id)
                                                <span class="tabular-nums text-ink-muted md:block md:text-xs">
                                                    {{ $managedUser->student_id }}
                                                </span>
                                            @endif
                                        @endif
                                    </div>

                                    <div class="col-span-2 flex min-w-0 flex-wrap gap-1.5 md:col-span-1">
                                        <x-ui.badge :variant="$managedUser->email_verified_at ? 'success' : 'warning'">
                                            {{ $managedUser->email_verified_at ? 'Email verified' : 'Email unverified' }}
                                        </x-ui.badge>

                                        @if ($managedUser->trashed())
                                            <x-ui.badge variant="danger">Deleted</x-ui.badge>
                                        @endif

                                        @if ($managedUser->research_locked)
                                            <x-ui.badge>Research locked</x-ui.badge>
                                        @endif
                                    </div>

                                    <div class="col-start-2 row-start-1 justify-self-end md:col-start-4"
                                        @if ($assigning && (! $canSelect || $managedUser->research_locked)) hidden @endif>
                                        @if (! $assigning)
                                            <x-ui.button variant="ghost" class="min-h-11 px-2 md:px-4"
                                                :href="route('admin.users.edit', ['userId' => $managedUser->user_id])"
                                                :aria-label="'Manage '.$managedUser->fullName()">
                                                Manage
                                            </x-ui.button>
                                        @elseif ($canSelect && ! $managedUser->research_locked)
                                            <label class="inline-flex min-h-11 items-center gap-2 text-sm font-semibold text-ink">
                                                <input type="checkbox" class="rounded border-line" name="student_ids[]"
                                                    value="{{ $managedUser->user_id }}" data-row-select
                                                    data-cohort="{{ $managedUser->cohort_id }}"
                                                    aria-label="Select {{ $managedUser->fullName() }} for cohort assignment">
                                                Select
                                            </label>
                                            <input type="hidden" name="original_cohorts[{{ $managedUser->user_id }}]"
                                                value="{{ $managedUser->cohort_id }}">
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            @endif

            @if ($assigning)
                </form>
            @endif

            <x-admin.pager :paginator="$users" label="Users" />
        </section>
    </div>
</x-app-layout>
