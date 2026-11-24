@props(['title', 'open' => false])

<details {{ $attributes->class('admin-disclosure overflow-hidden rounded-2xl border border-line bg-surface') }} @if ($open) open @endif>
    <summary class="flex min-h-11 cursor-pointer list-none items-center gap-3 px-4 py-3 text-sm font-bold text-ink">
        <span class="min-w-0 flex-1 break-words">{{ $title }}</span>
        @isset($meta)
            <span class="shrink-0 text-xs font-medium text-ink-muted">{{ $meta }}</span>
        @endisset
        <svg class="h-4 w-4 shrink-0 text-ink-muted" viewBox="0 0 20 20" fill="none" aria-hidden="true">
            <path d="m5 8 5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </summary>
    <div class="min-w-0 border-t border-line">{{ $slot }}</div>
</details>
