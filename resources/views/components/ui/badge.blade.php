@props([
    'variant' => 'neutral',
])

@php
    $classes = match ($variant) {
        'success' => 'bg-success-tint text-success-ink',
        'danger' => 'bg-danger-tint text-danger-ink',
        'warning' => 'bg-warning-tint text-warning-ink',
        'info' => 'bg-info-tint text-info-ink',
        'primary' => 'bg-primary-tint text-primary-ink',
        default => 'bg-surface-2 text-ink-muted',
    };
@endphp

<span
    {{ $attributes->class([
        'ui-badge',
        $classes,
    ]) }}
>
    {{ $slot }}
</span>
