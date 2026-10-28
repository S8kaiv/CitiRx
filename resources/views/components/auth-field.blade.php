@props([
    'id',
    'label',
    'name' => null,
    'type' => 'text',
    'icon' => null,
    'toggle' => false,
    'hint' => null,
    'autofocus' => false,
    'actionHref' => null,
    'actionLabel' => null,
])

@php
    $name ??= $id;
    $invalid = $errors->has($name);

    $padding = ($icon ? 'pl-12' : 'pl-4') . ' ' . ($toggle ? 'pr-16' : 'pr-4');

    $describedBy = collect([$invalid ? "{$id}-error" : null, $hint && !$invalid ? "{$id}-hint" : null])
        ->filter()
        ->implode(' ');

    $stateClasses = $invalid
        ? 'border-weak bg-weak-tint focus:border-weak focus:ring-weak/20'
        : 'border-slate-200 bg-slate-50 focus:border-primary focus:bg-white focus:ring-primary/20';

    $paths = [
        'mail' =>
            'M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75',
        'lock' =>
            'M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z',
        'shield' =>
            'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751A11.959 11.959 0 0 1 12 2.714Z',
    ];
@endphp

<div @if ($toggle) x-data="{ show: false }" @endif>
    <div class="mb-1.5 flex min-h-6 items-center justify-between gap-3">
        <label for="{{ $id }}" class="block text-sm font-semibold text-slate-700">
            {{ $label }}
        </label>

        @if ($actionHref && $actionLabel)
            <a href="{{ $actionHref }}"
                class="rounded-lg px-1 py-1 text-xs font-bold text-primary underline-offset-4 hover:text-primary-lip hover:underline focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/25">
                {{ $actionLabel }}
            </a>
        @endif
    </div>

    <div class="relative">
        @if ($icon && isset($paths[$icon]))
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-500"
                aria-hidden="true">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $paths[$icon] }}" />
                </svg>
            </span>
        @endif

        <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}"
            @if ($toggle) x-ref="passwordInput"
                x-bind:type="show ? 'text' : 'password'" @endif
            @if ($invalid) aria-invalid="true" @endif
            @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
            @if ($autofocus) x-init="
                    if (window.matchMedia('(pointer: fine)').matches) {
                        $nextTick(() => $el.focus());
                    }
                " @endif
            {{ $attributes->merge([
                'class' => "w-full rounded-xl border py-3 {$padding} text-base text-slate-900 transition-colors placeholder:text-slate-500 focus:outline-none focus:ring-4 sm:text-sm {$stateClasses}",
            ]) }}>

        @if ($toggle)
            <button type="button"
                x-on:click.prevent="
            show = ! show;

            $nextTick(() => {
                $refs.passwordInput.focus({
                    preventScroll: true,
                });
            });
        "
                x-bind:aria-pressed="show" x-bind:aria-label="show ? 'Hide password' : 'Show password'"
                class="absolute inset-y-0 right-0 min-w-12 px-3 text-xs font-bold text-slate-600 hover:text-primary focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/25"
                x-text="show ? 'Hide' : 'Show'">
                Show
            </button>
        @endif
    </div>

    @if ($hint && !$invalid)
        <p id="{{ $id }}-hint" class="mt-1.5 text-xs leading-relaxed text-slate-600">
            {{ $hint }}
        </p>
    @endif

    @error($name)
        <p id="{{ $id }}-error" class="mt-1.5 text-xs font-semibold text-weak-ink">
            {{ $message }}
        </p>
    @enderror
</div>
