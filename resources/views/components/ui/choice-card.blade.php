@props([
    'compact' => false,
])

<label
    {{ $attributes->class([
        'ui-choice group',
    ]) }}
>
    {{ $control }}

    <span
        @class([
            'ui-choice-card',
            'ui-choice-card-compact' => $compact,
        ])
    >
        <span class="ui-choice-letter">
            {{ $letter }}
        </span>

        <span class="ui-choice-copy">
            {{ $slot }}
        </span>
    </span>
</label>
