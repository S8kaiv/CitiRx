@props([
    'href',
    'label',
    'icon',
    'active' => false,
])

<a
    href="{{ $href }}"
    @if ($active)
        aria-current="page"
    @endif
    {{ $attributes->class([
        'admin-nav-link group',
        'admin-nav-link-active' => $active,
    ]) }}
>
    <span class="admin-nav-icon-wrap">
        <x-admin.icon
            :name="$icon"
            class="admin-nav-icon h-5 w-5 shrink-0"
        />
    </span>

    <span class="min-w-0 flex-1 truncate">
        {{ $label }}
    </span>

    @if ($active)
        <span
            class="h-2 w-2 shrink-0 rounded-full bg-primary/70"
            aria-hidden="true"
        ></span>
    @endif
</a>
