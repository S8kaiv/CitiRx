@props([
    'label' => 'Assessment actions',
])

<div
    role="group"
    aria-label="{{ $label }}"
    {{ $attributes->class([
        'ui-assessment-actions',
    ]) }}
>
    <div class="ui-assessment-actions-inner">
        {{ $slot }}
    </div>
</div>
