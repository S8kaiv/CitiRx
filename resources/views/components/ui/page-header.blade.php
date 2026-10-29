@props([
    'title',
    'description' => null,
])

<div {{ $attributes->class('ui-page-header') }}>
    <div class="min-w-0">
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
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            {{ $actions }}
        </div>
    @endisset
</div>
