@props([
    'variant' => 'primary',
    'type' => 'button',
    'href' => null,
    'loading' => false,
    'loadingLabel' => 'Please wait…',
    'disabled' => false,
])

@php
    $variantClass = match ($variant) {
        'secondary' => 'ui-button-secondary',
        'danger' => 'ui-button-danger',
        'ghost' => 'ui-button-ghost',
        default => 'ui-button-primary',
    };

    $isDisabled = $disabled || $loading;
@endphp

@if ($href)
    <a
        @if (! $isDisabled)
            href="{{ $href }}"
        @else
            aria-disabled="true"
            tabindex="-1"
        @endif
        {{ $attributes->class([
            'ui-button',
            $variantClass,
        ]) }}
    >
        {{ $slot }}
    </a>
@else
    <button
        type="{{ $type }}"
        @disabled($isDisabled)
        aria-busy="{{ $loading ? 'true' : 'false' }}"
        {{ $attributes->class([
            'ui-button',
            $variantClass,
        ]) }}
    >
        @if ($loading)
            <svg
                class="h-4 w-4 animate-spin motion-reduce:animate-none"
                viewBox="0 0 24 24"
                fill="none"
                aria-hidden="true"
            >
                <circle
                    cx="12"
                    cy="12"
                    r="9"
                    stroke="currentColor"
                    stroke-width="3"
                    opacity="0.25"
                />

                <path
                    d="M21 12a9 9 0 0 0-9-9"
                    stroke="currentColor"
                    stroke-width="3"
                    stroke-linecap="round"
                />
            </svg>

            <span>{{ $loadingLabel }}</span>
        @else
            {{ $slot }}
        @endif
    </button>
@endif
