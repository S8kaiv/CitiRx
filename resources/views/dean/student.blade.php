<x-app-layout>
    <x-slot name="header"><x-ui.page-header :title="$student->fullName()" description="Official student identity and current competency readiness." /></x-slot>
    <div class="mx-auto max-w-5xl space-y-6 px-4 py-6 sm:px-6">
        <x-ui.button variant="secondary" :href="route('dean.dashboard')">Back to workspace</x-ui.button>
        @if(session('status'))<x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>@endif
        <x-ui.card>
            @if(!$breakdown || empty($breakdown['domains']))<p class="text-ink">Diagnostic not completed</p>@else
                <h2 class="text-xl font-bold text-ink">Estimated readiness: {{ number_format($breakdown['total'], 2) }}%</h2><p class="mt-2 text-sm text-ink-muted">An estimated learning indicator, not a probability of passing the PhLE.</p>
                <ul class="mt-4 divide-y divide-line">@foreach($breakdown['domains'] as $domain)<li class="flex justify-between gap-4 py-3 text-ink"><span>{{ $domain['domain_name'] }}</span><span>{{ number_format($domain['mastery'] * 100, 1) }}%</span></li>@endforeach</ul>
            @endif
        </x-ui.card>
        <x-ui.card><h2 class="mb-4 text-lg font-bold text-ink">Edit official name</h2>
            <form method="POST" action="{{ route('dean.students.name', $student) }}" data-confirm="Update this student's official name? Any pending name request will be superseded." class="space-y-4">
                @csrf @method('PATCH')
                <x-ui.field name="first_name" label="First name" :value="$student->first_name" required />
                <x-ui.field name="middle_name" label="Middle name" :value="$student->middle_name" />
                <x-ui.field name="last_name" label="Last name" :value="$student->last_name" required />
                <x-ui.button type="submit">Update official name</x-ui.button>
            </form>
        </x-ui.card>
    </div>
</x-app-layout>
