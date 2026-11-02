@props([
    'label',
    'value',
    'completed',
    'total',
    'tone' => 'primary',
])

@php
    $safeValue = min(
        100,
        max(
            0,
            (float) $value,
        ),
    );

    $barClasses = match ($tone) {
        'success' => 'bg-success-strong',
        'warning' => 'bg-warning-strong',
        'danger' => 'bg-danger-strong',
        default => 'bg-primary',
    };
@endphp

<div>
    <div class="flex items-end justify-between gap-4">
        <div>
            <p class="text-sm font-bold text-ink">
                {{ $label }}
            </p>

            <p class="mt-0.5 text-xs text-ink-muted">
                {{ number_format($completed) }}
                of
                {{ number_format($total) }}
                students
            </p>
        </div>

        <p
            class="font-display text-lg font-extrabold
                tabular-nums text-ink"
        >
            {{ number_format($safeValue, 1) }}%
        </p>
    </div>

    <div
        class="mt-3 h-2.5 overflow-hidden rounded-full bg-surface-2"
        role="progressbar"
        aria-label="{{ $label }}"
        aria-valuemin="0"
        aria-valuemax="100"
        aria-valuenow="{{ round($safeValue, 2) }}"
    >
        <div
            class="admin-progress-fill h-full rounded-full
                {{ $barClasses }}"
            style="--admin-progress: {{ $safeValue / 100 }}"
        ></div>
    </div>
</div>
