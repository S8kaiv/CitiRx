@props([
    'value' => 0,
    'label' => null,
])

@php
    $safeValue = max(
        0,
        min(100, (float) $value),
    );
@endphp

<div {{ $attributes->class('space-y-2') }}>
    @if ($label)
        <div class="flex items-center justify-between gap-4 text-sm">
            <span class="font-bold text-ink">
                {{ $label }}
            </span>

            <span class="tabular-nums text-ink-muted">
                {{ number_format($safeValue, 0) }}%
            </span>
        </div>
    @endif

    <div
        class="ui-progress-track"
        role="progressbar"
        aria-valuemin="0"
        aria-valuemax="100"
        aria-valuenow="{{ $safeValue }}"
        @if ($label)
            aria-label="{{ $label }}"
        @endif
    >
        <div
            class="ui-progress-fill"
            style="--ui-progress: {{ $safeValue / 100 }}"
        ></div>
    </div>
</div>
