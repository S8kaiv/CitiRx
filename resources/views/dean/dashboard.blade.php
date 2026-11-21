<x-app-layout>
    <x-slot name="header"><x-ui.page-header title="Dean workspace" description="Review student support, official names, and research forms." /></x-slot>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex flex-wrap gap-3">
            <x-ui.button :href="route('dean.research-forms.index')">Research reviews ({{ $pendingReviews->count() }})</x-ui.button>
            <x-ui.button variant="secondary" :href="route('dean.names.index')">Name requests ({{ $pendingNames }})</x-ui.button>
            <x-ui.button variant="secondary" :href="route('research-editor.index')">Edit research forms</x-ui.button>
            <x-ui.button variant="secondary" :href="route('content.pearls.index')">Micro Pearls</x-ui.button>
        </div>
        @if($pendingReviews->isNotEmpty())<x-ui.card><h2 class="font-bold text-ink">Pending research form reviews</h2><ul class="mt-3 divide-y divide-line">@foreach($pendingReviews as $version)<li class="py-3"><a class="inline-flex min-h-11 items-center font-semibold text-primary-ink underline underline-offset-4" href="{{ route('dean.research-forms.show', $version) }}">Review {{ $version->version_code }}</a></li>@endforeach</ul></x-ui.card>@endif
        <form method="GET" class="grid gap-4 rounded-2xl border border-line bg-surface p-4 sm:grid-cols-4">
            <x-ui.field name="cohort_id" label="Cohort" type="select"><option value="">All cohorts</option>@foreach($cohorts as $cohort)<option value="{{ $cohort->cohort_id }}" @selected(request('cohort_id') === $cohort->cohort_id)>{{ $cohort->cohort_name }}</option>@endforeach</x-ui.field>
            <x-ui.field name="status" label="Intervention status" type="select">@foreach(['open', 'completed', 'all'] as $status)<option value="{{ $status }}" @selected(request('status', 'open') === $status)>{{ ucfirst($status) }}</option>@endforeach</x-ui.field>
            <x-ui.field name="q" label="Student name" :value="request('q')" />
            <div class="self-end"><x-ui.button type="submit">Filter</x-ui.button></div>
        </form>
        <x-ui.card><h2 class="text-lg font-bold text-ink">Interventions</h2>
            <div class="mt-4 overflow-x-auto"><table class="w-full min-w-[600px] text-left text-sm text-ink"><thead><tr class="border-b border-line"><th class="p-3">Student / cohort</th><th class="p-3">Faculty</th><th class="p-3">Support</th><th class="p-3">Status</th></tr></thead><tbody>
                @forelse($interventions as $item)<tr class="border-b border-line"><td class="p-3">{{ $item->targetUser?->fullName() }}<div class="text-ink-muted">{{ $item->targetUser?->cohort?->cohort_name ?? 'No cohort' }}</div></td><td class="p-3">{{ $item->faculty?->fullName() }}</td><td class="max-w-sm whitespace-pre-line break-words p-3">{{ $item->custom_message }}</td><td class="p-3">{{ $item->is_completed ? 'Completed' : 'Open' }}</td></tr>@empty<tr><td colspan="4" class="p-6">No interventions match these filters.</td></tr>@endforelse
            </tbody></table></div>{{ $interventions->links() }}
        </x-ui.card>
        <x-ui.card><h2 class="text-lg font-bold text-ink">Student readiness</h2><ul class="mt-3 divide-y divide-line">
            @forelse($students as $student)<li class="flex flex-wrap items-center justify-between gap-3 py-3 text-ink"><div>{{ $student->fullName() }}<p class="text-sm text-ink-muted">{{ $student->cohort?->cohort_name ?? 'No cohort' }}</p></div><x-ui.button variant="secondary" :href="route('dean.students.show', $student)">{{ $student->is_diagnostic_completed && $student->knowledge_states_count > 0 ? 'View readiness' : 'Diagnostic not completed' }}</x-ui.button></li>@empty<li class="py-6 text-ink-muted">No students match these filters.</li>@endforelse
        </ul>{{ $students->links() }}</x-ui.card>
    </div>
</x-app-layout>
