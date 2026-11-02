@props([
    'title',
    'description' => null,
])

<x-ui.page-header
    :title="$title"
    :description="$description"
>
    @isset($actions)
        <x-slot:actions>
            {{ $actions }}
        </x-slot:actions>
    @endisset
</x-ui.page-header>
