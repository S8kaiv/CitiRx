@props([
    'variant' => 'info',
    'role' => null,
])

@php
    $variantClass = match ($variant) {
        'success' => 'ui-alert-success',
        'danger' => 'ui-alert-danger',
        'warning' => 'ui-alert-warning',
        default => 'ui-alert-info',
    };

    $resolvedRole = $role
        ?? ($variant === 'danger' ? 'alert' : 'status');
@endphp

<div
    role="{{ $resolvedRole }}"
    {{ $attributes->class([
        'ui-alert',
        $variantClass,
    ]) }}
>
    <svg
        class="mt-0.5 h-5 w-5 shrink-0"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        stroke-width="1.8"
        aria-hidden="true"
    >
        @if ($variant === 'success')
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="m4.5 12.75 4.5 4.5 10.5-10.5"
            />
        @elseif ($variant === 'danger')
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M6 18 18 6M6 6l12 12"
            />
        @else
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M11.25 9.75h1.5v6h-1.5v-6Zm0-3h1.5v1.5h-1.5v-1.5ZM12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z"
            />
        @endif
    </svg>

    <div class="min-w-0 flex-1">
        {{ $slot }}
    </div>
</div>
