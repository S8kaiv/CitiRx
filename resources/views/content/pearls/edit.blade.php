<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-bold uppercase text-primary">
                {{ str($pearl->content_status)
                    ->replace('_', ' ')
                    ->title() }}
            </p>

            <h1 class="font-display text-xl font-bold">
                {{ $pearl->title }}
            </h1>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-6 px-4">
            @if (session('status'))
                <div class="rounded-xl bg-green-50 p-4 text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-xl bg-red-50 p-4 text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            @if ($pearl->content_status === 'draft')
                <form
                    method="POST"
                    action="{{ route(
                        'content.pearls.update',
                        ['pearl' => $pearl->pearl_id],
                    ) }}"
                    class="rounded-3xl border-2 border-slate-200 bg-white p-6"
                >
                    @csrf
                    @method('PUT')

                    @include('content.pearls._form')
                </form>

                <form
                    method="POST"
                    action="{{ route(
                        'content.pearls.submit-review',
                        ['pearl' => $pearl->pearl_id],
                    ) }}"
                    class="rounded-3xl border-2 border-amber-200 bg-amber-50 p-6"
                >
                    @csrf
                    @method('PATCH')

                    <input
                        type="hidden"
                        name="edit_version"
                        value="{{ $pearl->edit_version }}"
                    >

                    <textarea
                        name="reason"
                        required
                        placeholder="Reason for submitting this content"
                        class="w-full rounded-xl border-slate-300"
                    ></textarea>

                    <button class="mt-3 rounded-xl bg-amber-600 px-4 py-2 font-bold text-white">
                        Submit for Review
                    </button>
                </form>
            @elseif ($pearl->content_status === 'in_review')
                <div class="grid gap-5 lg:grid-cols-2">
                    <form
                        method="POST"
                        action="{{ route(
                            'content.pearls.return-draft',
                            ['pearl' => $pearl->pearl_id],
                        ) }}"
                        class="rounded-3xl border-2 border-slate-200 bg-white p-6"
                    >
                        @csrf
                        @method('PATCH')

                        <input type="hidden" name="edit_version" value="{{ $pearl->edit_version }}">

                        <textarea
                            name="reason"
                            required
                            placeholder="Required corrections"
                            class="w-full rounded-xl border-slate-300"
                        ></textarea>

                        <button class="mt-3 rounded-xl bg-slate-700 px-4 py-2 font-bold text-white">
                            Return to Draft
                        </button>
                    </form>

                    <form
                        method="POST"
                        action="{{ route(
                            'content.pearls.publish',
                            ['pearl' => $pearl->pearl_id],
                        ) }}"
                        class="rounded-3xl border-2 border-green-200 bg-green-50 p-6"
                    >
                        @csrf
                        @method('PATCH')

                        <input type="hidden" name="edit_version" value="{{ $pearl->edit_version }}">

                        <input
                            type="datetime-local"
                            name="published_at"
                            class="w-full rounded-xl border-slate-300"
                        >

                        <textarea
                            name="reason"
                            required
                            placeholder="Publication approval reason"
                            class="mt-3 w-full rounded-xl border-slate-300"
                        ></textarea>

                        <input
                            type="password"
                            name="current_password"
                            required
                            placeholder="Current password"
                            class="mt-3 w-full rounded-xl border-slate-300"
                        >

                        <button class="mt-3 rounded-xl bg-green-700 px-4 py-2 font-bold text-white">
                            Approve Publication
                        </button>
                    </form>
                </div>
            @elseif ($pearl->content_status === 'published')
                <form
                    method="POST"
                    action="{{ route(
                        'content.pearls.retire',
                        ['pearl' => $pearl->pearl_id],
                    ) }}"
                    class="rounded-3xl border-2 border-red-200 bg-red-50 p-6"
                >
                    @csrf
                    @method('PATCH')

                    <input type="hidden" name="edit_version" value="{{ $pearl->edit_version }}">

                    <textarea
                        name="reason"
                        required
                        placeholder="Reason for retiring this lesson"
                        class="w-full rounded-xl border-slate-300"
                    ></textarea>

                    <input
                        type="password"
                        name="current_password"
                        required
                        placeholder="Current password"
                        class="mt-3 w-full rounded-xl border-slate-300"
                    >

                    <button class="mt-3 rounded-xl bg-red-700 px-4 py-2 font-bold text-white">
                        Retire Pearl
                    </button>
                </form>
            @endif

            <section class="rounded-3xl border-2 border-slate-200 bg-white p-6">
                <h2 class="font-display text-lg font-bold">
                    Immutable Edit History
                </h2>

                <div class="mt-4 space-y-3">
                    @forelse ($pearl->edits as $edit)
                        <article class="rounded-xl bg-slate-50 p-4 text-sm">
                            <div class="font-bold">
                                {{ str($edit->action)->replace('_', ' ')->title() }}
                            </div>

                            <div class="text-slate-500">
                                {{ $edit->editor?->fullName() ?? 'Former user' }}
                                · {{ $edit->edited_at->format('M j, Y g:i A') }}
                                · version {{ $edit->version_before }}
                                → {{ $edit->version_after }}
                            </div>

                            @if ($edit->reason)
                                <p class="mt-2">{{ $edit->reason }}</p>
                            @endif
                        </article>
                    @empty
                        <p class="text-sm text-slate-500">
                            No history is available.
                        </p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
