@props([
    'padded' => true,
    'raised' => false,
])

<div
    {{ $attributes->class([
        'ui-card',
        'ui-card-padded' => $padded,
        'ui-card-raised' => $raised,
    ]) }}
>
    {{ $slot }}
</div>
