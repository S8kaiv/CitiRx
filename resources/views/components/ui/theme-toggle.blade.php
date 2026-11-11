<button
    type="button"
    x-on:click="toggleComfort()"
    x-bind:aria-label="
        resolvedComfort === 'dark'
            ? 'Switch to light mode'
            : 'Switch to dark mode'
    "
    x-bind:title="
        resolvedComfort === 'dark'
            ? 'Switch to light mode'
            : 'Switch to dark mode'
    "
    class="public-theme-toggle inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-ink-muted transition-colors hover:bg-surface-2 hover:text-ink focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2"
>
    {{-- Sun: switch to light mode --}}
    <svg
        x-cloak
        x-show="resolvedComfort === 'dark'"
        class="h-5 w-5"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.8"
        aria-hidden="true"
    >
        <circle cx="12" cy="12" r="4" />

        <path
            stroke-linecap="round"
            d="M12 2v2m0 16v2M2 12h2m16 0h2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"
        />
    </svg>

    {{-- Moon: switch to dark mode --}}
    <svg
        x-cloak
        x-show="resolvedComfort !== 'dark'"
        class="h-5 w-5"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.8"
        aria-hidden="true"
    >
        <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M20.9 13.1A9 9 0 0 1 10.9 3.1 9 9 0 1 0 20.9 13.1Z"
        />
    </svg>
</button>
