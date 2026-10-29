<div
    role="group"
    aria-label="Reading comfort"
    class="inline-flex rounded-xl border-2 border-line
        bg-surface p-1"
>
    @foreach ([
        'light' => 'Light',
        'dark' => 'Dark',
        'auto' => 'Auto',
    ] as $mode => $label)
        <button
            type="button"
            x-on:click="setComfort('{{ $mode }}')"
            x-bind:aria-pressed="
                comfortMode === '{{ $mode }}'
                    ? 'true'
                    : 'false'
            "
            x-bind:class="
                comfortMode === '{{ $mode }}'
                    ? 'bg-primary-tint text-primary-ink'
                    : 'text-ink-muted hover:bg-surface-2'
            "
            class="min-h-11 rounded-lg px-3 text-sm
                font-bold transition-colors"
        >
            {{ $label }}
        </button>
    @endforeach
</div>
