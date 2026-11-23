@props(['theme'])

@php
    $slug = Illuminate\Support\Str::after($theme['id'], 'theme-');
@endphp

<span aria-hidden="true" class="theme-reward-art relative block h-11 w-11 shrink-0" data-theme-reward="{{ $slug }}">
    <img src="{{ asset('brand/'.$slug.'-dark.png') }}" alt="" width="44" height="44" class="h-full w-full rounded-xl">
</span>
