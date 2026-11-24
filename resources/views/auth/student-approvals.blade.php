<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            title="Student account approvals"
            description="Check the student against the school roster before approving access."
        />
    </x-slot>

    <div class="mx-auto max-w-4xl space-y-4 px-4 py-6">
        @if (session('status'))
            <x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>
        @endif

        @if (session('error'))
            <x-ui.alert variant="danger">{{ session('error') }}</x-ui.alert>
        @endif

        <x-ui.card class="divide-y divide-line">
            @forelse ($students as $student)
                <div class="flex flex-wrap items-center justify-between gap-3 py-4">
                    <div class="min-w-0">
                        <p class="font-bold text-ink">{{ $student->fullName() }}</p>
                        <p class="break-all text-sm text-ink-muted">{{ $student->email }}</p>
                    </div>

                    <form method="POST" action="{{ route('student-approvals.store', $student) }}">
                        @csrf
                        <input type="hidden" name="reviewed_email" value="{{ $student->email }}">
                        <x-ui.button type="submit" aria-label="Approve {{ $student->fullName() }}">
                            Approve
                        </x-ui.button>
                    </form>
                </div>
            @empty
                <p class="text-sm text-ink-muted">No student accounts are ready for approval.</p>
            @endforelse
        </x-ui.card>

        {{ $students->links() }}
    </div>
</x-app-layout>
