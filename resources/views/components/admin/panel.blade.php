@props([
    'id',
    'title',
    'description' => null,
])

<section
    aria-labelledby="{{ $id }}"
    {{ $attributes->class('admin-panel') }}
>
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <h2
                id="{{ $id }}"
                class="font-display text-lg font-extrabold text-ink"
            >
                {{ $title }}
            </h2>

            @if ($description)
                <p class="mt-1 max-w-2xl text-sm leading-relaxed text-ink-muted">
                    {{ $description }}
                </p>
            @endif
        </div>

        @isset($actions)
            <div class="flex shrink-0 flex-wrap items-center gap-2">
                {{ $actions }}
            </div>
        @endisset
    </div>

    <div class="mt-5">
        {{ $slot }}
    </div>
</section>
