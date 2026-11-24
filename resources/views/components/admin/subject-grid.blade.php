@props(['domains', 'indexRoute', 'itemLabel', 'icon'])

<section aria-labelledby="subject-grid-title" class="space-y-3">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 id="subject-grid-title" class="font-display text-xl font-extrabold text-ink sm:text-2xl">
            Browse by subject
        </h2>

        <x-ui.button variant="secondary" :href="route($indexRoute, ['view' => 'all'])">
            View all {{ $itemLabel }}
        </x-ui.button>
    </div>

    <ul class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($domains as $domain)
            <li class="min-w-0">
                <a href="{{ route($indexRoute, ['domain_id' => $domain->domain_id]) }}"
                    class="flex h-full min-h-20 items-center gap-3 rounded-xl border border-line bg-surface p-4 hover:border-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                    <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-primary-tint text-primary-ink">
                        <x-admin.icon :name="$icon" class="size-5" aria-hidden="true" />
                    </span>

                    <span class="min-w-0 flex-1 break-words font-display text-base font-bold leading-snug text-ink">
                        {{ $domain->domain_name }}
                    </span>

                    <x-admin.icon name="arrow" class="size-4 shrink-0 text-ink-muted" aria-hidden="true" />
                </a>
            </li>
        @empty
            <li class="col-span-full p-4 text-sm text-ink-muted">No subjects are configured yet.</li>
        @endforelse
    </ul>
</section>
