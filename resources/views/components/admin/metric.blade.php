@props([
    'label',
    'value',
    'hint' => null,
    'tone' => 'neutral',
    'href' => null,
])

@php
    $valueClasses = match ($tone) {
        'primary' => 'text-primary',
        'success' => 'text-success-ink',
        'warning' => 'text-warning-ink',
        'danger' => 'text-danger-ink',
        default => 'text-ink',
    };
@endphp

<div class="min-w-0 px-5 py-4">
    <dt
        class="text-xs font-bold uppercase tracking-[0.08em]
            text-ink-muted"
    >
        {{ $label }}
    </dt>

    <dd
        class="mt-1 font-display text-2xl font-extrabold
            tabular-nums {{ $valueClasses }}"
    >
        {{ $value }}
    </dd>

    @if ($hint)
        <p class="mt-1 text-xs leading-relaxed text-ink-muted">
            {{ $hint }}
        </p>
    @endif

    @if ($href)
        <a
            href="{{ $href }}"
            class="mt-2 inline-flex min-h-11 items-center text-xs
                font-bold text-primary underline-offset-4
                hover:underline focus:outline-none
                focus-visible:ring-4 focus-visible:ring-primary/25"
        >
            View details
        </a>
    @endif
</div>
