@props([
    'title',
    'description' => null,
])

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
