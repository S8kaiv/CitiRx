@props(['title', 'description' => null])
@php($toolsId = 'faculty-tools-'.\Illuminate\Support\Str::uuid())
<div {{ $attributes->class('ui-page-header rx-faculty-header') }}
    x-data="{ expanded: false }" x-bind:data-expanded="expanded ? 'true' : 'false'"
    x-on:resize.window="if (window.innerWidth >= 1024) expanded = false"
    x-on:click.outside="expanded = false"
    x-on:keydown.escape="if (expanded) { expanded = false; if (window.innerWidth < 1024) document.querySelector('.rx-faculty-header-toggle').focus() }">
    <div class="ui-page-header-text">
        <h1 class="ui-page-title">{{ $title }}</h1>
        @if ($description)<p id="{{ $toolsId }}-description" class="ui-page-description">{{ $description }}</p>@endif
    </div>
    @if ($description || isset($actions))
        <button x-cloak x-ref="toggle" type="button" class="rx-faculty-header-toggle"
            x-bind:aria-expanded="expanded ? 'true' : 'false'" x-on:click="expanded = !expanded"
            aria-controls="{{ $description ? $toolsId.'-description' : '' }} {{ isset($actions) ? $toolsId.'-actions' : '' }}"
            x-bind:aria-label="expanded ? 'Close page actions' : 'Open page actions'" title="Page actions">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <circle cx="5" cy="12" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="19" cy="12" r="2"/>
            </svg>
        </button>
    @endif
    @isset($actions)<div id="{{ $toolsId }}-actions" class="ui-page-header-actions">{{ $actions }}</div>@endisset
</div>
