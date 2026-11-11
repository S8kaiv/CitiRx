<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            title="Intervention History"
            description="Review and complete the interventions you assigned to your cohort."
        >
            <x-slot:actions>
                <x-ui.button
                    variant="secondary"
                    :href="route('faculty.dashboard')"
                >
                    Back to Faculty Dashboard
                </x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <div class="pt-6 pb-6 sm:pb-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <x-ui.alert variant="success">
                    {{ session('status') }}
                </x-ui.alert>
            @endif

            @forelse ($interventions as $intervention)
                <x-ui.card>
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-ui.badge variant="primary">
                                    {{ \App\Models\FacultyIntervention::TYPE_LABELS[
                                        $intervention->intervention_type
                                    ] ?? ucfirst(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $intervention->intervention_type,
                                        ),
                                    ) }}
                                </x-ui.badge>

                                <x-ui.badge
                                    :variant="$intervention->is_completed
                                        ? 'success'
                                        : 'warning'"
                                >
                                    {{ $intervention->is_completed
                                        ? 'Completed'
                                        : 'Open' }}
                                </x-ui.badge>
                            </div>

                            <h2 class="mt-3 font-display text-lg font-extrabold text-ink">
                                @if ($intervention->targetUser)
                                    {{ $intervention->targetUser->fullName() }}
                                @else
                                    Unavailable student
                                @endif
                            </h2>

                            <p class="mt-1 text-sm text-ink-muted">
                                {{ $intervention->domain?->domain_name ?? 'Unknown domain' }}

                                @if ($intervention->competency)
                                    <span aria-hidden="true">·</span>
                                    {{ $intervention->competency->competency_code }}
                                    — {{ $intervention->competency->title }}
                                @endif
                            </p>

                            @if ($intervention->custom_message)
                                <div class="mt-4 rounded-2xl bg-surface-2 p-4">
                                    <p class="whitespace-pre-line text-sm leading-relaxed text-ink">
                                        {{ $intervention->custom_message }}
                                    </p>
                                </div>
                            @endif

                            <p class="mt-4 text-xs leading-relaxed text-ink-muted">
                                Assigned
                                {{ $intervention->assigned_at->format(
                                    'M j, Y g:i A',
                                ) }}

                                @if ($intervention->completed_at)
                                    <span aria-hidden="true">·</span>
                                    Completed
                                    {{ $intervention->completed_at->format(
                                        'M j, Y g:i A',
                                    ) }}
                                @endif
                            </p>
                        </div>

                        @if (! $intervention->is_completed)
                            <form
                                method="POST"
                                action="{{ route(
                                    'faculty.interventions.complete',
                                    [
                                        'intervention' =>
                                            $intervention->intervention_id,
                                    ],
                                ) }}"
                                class="shrink-0"
                            >
                                @csrf
                                @method('PATCH')

                                <x-ui.button type="submit" class="w-full sm:w-auto">
                                    Mark Complete
                                </x-ui.button>
                            </form>
                        @endif
                    </div>
                </x-ui.card>
            @empty
                <x-ui.card class="py-12 text-center" raised>
                    <h2 class="font-display text-lg font-extrabold text-ink">
                        No interventions yet
                    </h2>

                    <p class="mt-2 text-sm text-ink-muted">
                        Assigned interventions will appear here.
                    </p>

                    <x-ui.button
                        class="mt-5"
                        :href="route('faculty.dashboard')"
                    >
                        Return to Faculty Dashboard
                    </x-ui.button>
                </x-ui.card>
            @endforelse

            @if ($interventions->hasPages())
                <div>
                    {{ $interventions->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
