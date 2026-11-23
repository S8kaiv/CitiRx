<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Practice question" :description="$question->competency?->competency_code.' · '.$question->competency?->domain?->domain_name">
            <x-slot:actions>
                <x-ui.button variant="secondary" :href="route($routePrefix.'.index')">Back to practice questions</x-ui.button>
                <x-ui.button :href="route($routePrefix.'.create')">Add another question</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <div class="mx-auto max-w-4xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        @if (session('status'))<x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>@endif
        @if ($lockReason)<x-ui.alert>{{ $lockReason }}</x-ui.alert>@endif

        <x-ui.card>
            <x-ui.badge :variant="$question->is_active ? 'success' : 'neutral'">{{ $question->is_active ? 'Active in practice pool' : 'Inactive' }}</x-ui.badge>
            <h2 class="mt-4 whitespace-pre-line break-words text-lg font-bold text-ink">{{ $question->question_text }}</h2>
            <ol class="mt-5 space-y-3">
                @foreach ($question->choices as $choice)
                    <li class="rounded-xl border border-line p-4 text-ink">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="font-bold">Choice {{ $choice->choice_letter }}</span>
                            @if ($choice->is_correct)<x-ui.badge variant="success">Correct answer</x-ui.badge>@endif
                        </div>
                        <p class="mt-2 whitespace-pre-line break-words text-sm">{{ $choice->choice_text }}</p>
                    </li>
                @endforeach
            </ol>
        </x-ui.card>

        <x-ui.card>
            <h2 class="font-bold text-ink">Answer rationale</h2>
            <p class="mt-3 whitespace-pre-line break-words text-sm text-ink">{{ $question->hypercorrection_rationale }}</p>
            <h2 class="mt-6 font-bold text-ink">Source</h2>
            <p class="mt-3 whitespace-pre-line break-words text-sm text-ink">{{ $question->source_reference }}</p>
            <p class="mt-3 text-sm text-ink-muted">Rights: {{ $question->copyright_permission ?? 'Not recorded for this older item' }}</p>
        </x-ui.card>

        @if (auth()->user()->role === 'admin')
            <x-ui.button variant="secondary" :href="route('admin.questions.show', $question)">Open Question Bank controls</x-ui.button>
        @endif
    </div>
</x-app-layout>
