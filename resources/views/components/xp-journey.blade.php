@props([
    'currentXp',
    'targetXp',
])

@php
    $current = max(0, (int) $currentXp);
    $target = max(1, (int) $targetXp);
    $cappedCurrent = min($current, $target);
    $progressPercent = min(
        100,
        round(($cappedCurrent / $target) * 100, 1),
    );
@endphp

<div class="border-t border-slate-100 pt-4">
    <div class="mb-2 flex items-center justify-between gap-3">
        <span
            class="font-display text-[10px] font-bold uppercase tracking-wider text-muted-ink"
        >
            Level 10 Journey
        </span>

        <span class="font-display text-[10px] font-black text-slate-700">
            {{ number_format($cappedCurrent) }}
            /
            {{ number_format($target) }}
            XP
        </span>
    </div>

    <div
        class="h-3 overflow-hidden rounded-full bg-slate-200"
        role="progressbar"
        aria-label="Overall XP progress toward the maximum configured level"
        aria-valuemin="0"
        aria-valuemax="{{ $target }}"
        aria-valuenow="{{ $cappedCurrent }}"
        aria-valuetext="{{ number_format($cappedCurrent) }} of {{ number_format($target) }} XP"
    >
        <div
            class="h-full rounded-full bg-primary transition-[width] duration-500 motion-reduce:transition-none"
            style="width: {{ $progressPercent }}%"
        ></div>
    </div>
</div>
