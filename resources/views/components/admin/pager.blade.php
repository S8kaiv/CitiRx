@props(['paginator', 'label'])

<nav aria-label="{{ $label }} pagination" class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
    <p class="text-sm tabular-nums text-ink-muted">
        @if ($paginator->firstItem() !== null)
            {{ $paginator->firstItem() }}&ndash;{{ $paginator->lastItem() }} of {{ $paginator->total() }}
        @else
            {{ $paginator->total() }} results
        @endif
        @if ($paginator->hasPages())
            <span> &middot; Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>
        @endif
    </p>

    @if ($paginator->hasPages())
        <div class="flex flex-wrap gap-2">
            @if ($paginator->previousPageUrl())
                <x-ui.button variant="secondary" :href="$paginator->previousPageUrl()">Previous</x-ui.button>
            @endif
            @if ($paginator->nextPageUrl())
                <x-ui.button variant="secondary" :href="$paginator->nextPageUrl()">Next</x-ui.button>
            @endif
        </div>
    @endif
</nav>
