@props([
    'title',
    'description' => null,
])

@if (auth()->user()?->role === 'faculty')
    <x-faculty.page-header :title="$title" :description="$description" {{ $attributes }}>
        @isset($actions)
            <x-slot:actions>{{ $actions }}</x-slot:actions>
        @endisset
    </x-faculty.page-header>
@elseif (auth()->user()?->role === 'student')
    <x-student.page-header :title="$title" :description="$description" {{ $attributes }}>
        @isset($actions)
            <x-slot:actions>{{ $actions }}</x-slot:actions>
        @endisset
    </x-student.page-header>
@else
<div {{ $attributes->class('ui-page-header') }}>
    <div class="ui-page-header-text">
        <h1 class="ui-page-title">
            {{ $title }}
        </h1>

        @if ($description)
            <p class="ui-page-description">
                {{ $description }}
            </p>
        @endif
    </div>

    @isset($actions)
        <div class="ui-page-header-actions">
            {{ $actions }}
        </div>
    @endisset
</div>

@endif
