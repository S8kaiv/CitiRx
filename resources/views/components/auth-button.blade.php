@props([
    'label',
    'busyLabel' => 'Please wait…',
])

<button
    {{ $attributes->merge([
        'type' => 'submit',
        'class' => 'btn-press flex min-h-12 w-full items-center justify-center gap-2 rounded-2xl border-2 border-primary bg-primary px-7 py-3.5 font-display text-base font-bold text-white shadow-md transition-colors hover:bg-primary/90 focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/25 disabled:cursor-wait disabled:opacity-70',
    ]) }}
    x-bind:disabled="busy"
    x-bind:aria-busy="busy"
    style="--lip: rgb(var(--color-primary-lip));"
>
    <span x-show="! busy">
        {{ $label }}
    </span>

    <span
        x-cloak
        x-show="busy"
        class="flex items-center gap-2"
    >
        <svg
            class="h-4 w-4 animate-spin motion-reduce:animate-none"
            viewBox="0 0 24 24"
            fill="none"
            aria-hidden="true"
        >
            <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
            ></circle>

            <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"
            ></path>
        </svg>

        {{ $busyLabel }}
    </span>
</button>
