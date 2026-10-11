<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-display text-xl font-extrabold text-slate-900">
                Intervention History
            </h2>

            <a
                href="{{ route('faculty.dashboard') }}"
                class="text-sm font-bold text-primary hover:text-primary-lip"
            >
                Back to Faculty Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div
                    role="status"
                    class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800"
                >
                    {{ session('status') }}
                </div>
            @endif

            @forelse ($interventions as $intervention)
                <article class="rounded-3xl border-2 border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full bg-primary-tint px-3 py-1 text-xs font-bold text-primary">
                                    {{ \App\Models\FacultyIntervention::TYPE_LABELS[
                                        $intervention->intervention_type
                                    ] ?? ucfirst(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $intervention->intervention_type,
                                        ),
                                    ) }}
                                </span>

                                <span
                                    class="rounded-full px-3 py-1 text-xs font-bold
                                        {{ $intervention->is_completed
                                            ? 'bg-emerald-100 text-emerald-800'
                                            : 'bg-amber-100 text-amber-800' }}"
                                >
                                    {{ $intervention->is_completed
                                        ? 'Completed'
                                        : 'Open' }}
                                </span>
                            </div>

                            <h3 class="mt-3 font-display text-lg font-extrabold text-slate-900">
                                @if ($intervention->targetUser)
                                    {{ $intervention->targetUser->fullName() }}
                                @else
                                    Unavailable student
                                @endif
                            </h3>

                            <p class="mt-1 text-sm text-slate-500">
                                {{ $intervention->domain?->domain_name ?? 'Unknown domain' }}

                                @if ($intervention->competency)
                                    ·
                                    {{ $intervention->competency->competency_code }}
                                    —
                                    {{ $intervention->competency->title }}
                                @endif
                            </p>

                            @if ($intervention->custom_message)
                                <p class="mt-4 rounded-2xl bg-slate-50 p-4 text-sm leading-relaxed text-slate-700">
                                    {{ $intervention->custom_message }}
                                </p>
                            @endif

                            <p class="mt-3 text-xs text-slate-400">
                                Assigned
                                {{ $intervention->assigned_at->format(
                                    'M j, Y g:i A',
                                ) }}

                                @if ($intervention->completed_at)
                                    · Completed
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
                            >
                                @csrf
                                @method('PATCH')

                                <button
                                    type="submit"
                                    class="rounded-xl border-2 border-b-4 border-primary/30 bg-primary px-4 py-2 text-sm font-bold text-white transition hover:bg-primary-lip"
                                >
                                    Mark Complete
                                </button>
                            </form>
                        @endif
                    </div>
                </article>
            @empty
                <section class="rounded-3xl border-2 border-slate-200 bg-white p-12 text-center shadow-sm">
                    <h3 class="font-display text-lg font-extrabold text-slate-900">
                        No interventions yet
                    </h3>

                    <p class="mt-2 text-sm text-slate-500">
                        Assigned interventions will appear here.
                    </p>
                </section>
            @endforelse

            <div>
                {{ $interventions->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
