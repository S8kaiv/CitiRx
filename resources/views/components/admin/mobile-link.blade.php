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
        'admin-mobile-link',
        'text-primary' => $active,
        'text-slate-500' => ! $active,
    ]) }}
>
    <x-admin.icon
        :name="$icon"
        class="h-5 w-5"
    />

    <span>{{ $label }}</span>
</a>
