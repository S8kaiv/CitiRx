@props(['title', 'description' => null])

<div
    {{ $attributes->class('ui-page-header rx-admin-header') }}
    x-data="{ expanded: false }"
    x-bind:data-expanded="expanded ? 'true' : 'false'"
    x-on:keydown.escape="if (expanded) { expanded = false; $refs.headerToggle?.focus() }"
>
    <div class="ui-page-header-text">
        <h1 class="ui-page-title">{{ $title }}</h1>
        @if ($description)
            <p class="ui-page-description">{{ $description }}</p>
        @endif
    </div>

    @if ($description || isset($actions))
        <button
            x-cloak
            x-ref="headerToggle"
            type="button"
            class="rx-admin-header-toggle"
            x-bind:aria-expanded="expanded ? 'true' : 'false'"
            x-on:click="expanded = ! expanded"
            aria-label="Toggle page information and actions"
            title="Page actions"
        >
            <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true">
                <g class="rx-header-dots" fill="currentColor">
                    <circle cx="5" cy="12" r="2" />
                    <circle cx="12" cy="12" r="2" />
                    <circle cx="19" cy="12" r="2" />
                </g>
                <path class="rx-header-close" d="m7 7 10 10M17 7 7 17"
                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
            </svg>
        </button>
    @endif

    @isset($actions)
        <div class="ui-page-header-actions">{{ $actions }}</div>
    @endisset
</div>
