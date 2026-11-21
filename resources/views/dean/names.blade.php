<x-app-layout>
    <x-slot name="header"><x-ui.page-header title="Student name requests" description="The official name stays unchanged until a request is approved." /></x-slot>
    <div class="mx-auto max-w-5xl space-y-5 px-4 py-6 sm:px-6">
        @if(session('status'))<x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>@endif
        <form method="GET" class="flex items-end gap-3"><x-ui.field name="status" label="Request status" type="select">@foreach(['pending','approved','declined','superseded'] as $status)<option value="{{ $status }}" @selected(request('status', 'pending') === $status)>{{ ucfirst($status) }}</option>@endforeach</x-ui.field><x-ui.button type="submit">Filter</x-ui.button></form>
        @forelse($requests as $item)<x-ui.card>
            <h2 class="font-bold text-ink">{{ $item->student?->fullName() ?? 'Unavailable student' }}</h2>
            <p class="mt-2 text-sm text-ink-muted">Requested: {{ implode(' ', array_filter($item->requested_names)) }} · {{ ucfirst($item->status) }}</p>
            @if($item->status === 'pending')<form method="POST" action="{{ route('dean.names.decide', $item) }}" data-confirm="Record this name-request decision?" class="mt-4 space-y-4">@csrf @method('PATCH')
                <x-ui.field name="decision" :id="'decision-'.$item->id" label="Decision" type="select" required><option value="approve">Approve requested name</option><option value="decline">Decline request</option></x-ui.field>
                <x-ui.field name="decision_note" :id="'note-'.$item->id" label="Decision note" hint="Required when declining; visible to the student." type="textarea" />
                <x-ui.button type="submit">Record decision</x-ui.button></form>
            @else<p class="mt-3 text-sm text-ink">{{ $item->decision_note }} {{ $item->reviewer?->fullName() }}</p>@endif
        </x-ui.card>@empty<x-ui.alert>No name requests match this status.</x-ui.alert>@endforelse
        {{ $requests->links() }}
    </div>
</x-app-layout>
