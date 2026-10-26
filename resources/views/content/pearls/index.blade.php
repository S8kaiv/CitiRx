<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="font-display text-xl font-bold">
                Micro Pearl Administration
            </h1>

            <a
                href="{{ route('content.pearls.create') }}"
                class="rounded-xl bg-primary px-4 py-2 font-bold text-white"
            >
                Create Draft
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-5 px-4">
            <form method="GET" class="grid gap-3 rounded-2xl bg-white p-5 md:grid-cols-3">
                <input
                    name="q"
                    value="{{ $search }}"
                    placeholder="Search title or summary"
                    class="rounded-xl border-slate-300"
                >

                <select name="status" class="rounded-xl border-slate-300">
                    <option value="">All statuses</option>

                    @foreach ([
                        'draft',
                        'in_review',
                        'published',
                        'retired',
                    ] as $option)
                        <option value="{{ $option }}" @selected($status === $option)>
                            {{ str($option)->replace('_', ' ')->title() }}
                        </option>
                    @endforeach
                </select>

                <button class="rounded-xl bg-primary px-4 py-2 font-bold text-white">
                    Filter
                </button>
            </form>

            <div class="overflow-x-auto rounded-2xl bg-white">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead>
                        <tr class="text-left">
                            <th class="p-4">Pearl</th>
                            <th class="p-4">Competency</th>
                            <th class="p-4">Status</th>
                            <th class="p-4">Author</th>
                            <th class="p-4">Version</th>
                            <th class="p-4"></th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse ($pearls as $pearl)
                            <tr>
                                <td class="p-4">
                                    <div class="font-bold">
                                        {{ $pearl->title }}
                                    </div>

                                    @if ($pearl->is_ai_generated)
                                        <div class="text-xs text-sky-700">
                                            AI-assisted
                                        </div>
                                    @endif
                                </td>

                                <td class="p-4">
                                    {{ $pearl->competency->competency_code }}
                                </td>

                                <td class="p-4">
                                    {{ str($pearl->content_status)
                                        ->replace('_', ' ')
                                        ->title() }}
                                </td>

                                <td class="p-4">
                                    {{ $pearl->creator?->fullName() ?? 'Legacy record' }}
                                </td>

                                <td class="p-4">
                                    {{ $pearl->edit_version }}
                                </td>

                                <td class="p-4 text-right">
                                    <a
                                        href="{{ route(
                                            'content.pearls.edit',
                                            ['pearl' => $pearl->pearl_id],
                                        ) }}"
                                        class="font-bold text-primary"
                                    >
                                        Manage
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-8 text-center text-slate-500">
                                    No Micro Pearls match these filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $pearls->links() }}
        </div>
    </div>
</x-app-layout>
