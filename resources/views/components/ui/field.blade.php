@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'hint' => null,
    'error' => null,
    'required' => false,
    'disabled' => false,
    'rows' => 4,
])

@php
    $id = $attributes->get('id', $name);
    $errorMessage = $error ?: $errors->first($name);

    $hintId = $hint ? "{$id}-hint" : null;
    $errorId = $errorMessage ? "{$id}-error" : null;

    $describedBy = collect([
        $hintId,
        $errorId,
    ])->filter()->implode(' ');

    $controlAttributes = $attributes
        ->except(['id'])
        ->class([
            'ui-field-control',
            'pr-20' => $type === 'password',
        ]);
@endphp

<div
    class="space-y-1.5"
    @if ($type === 'password')
        x-data="{ showPassword: false }"
    @endif
>
    <label
        for="{{ $id }}"
        class="block text-sm font-bold text-ink"
    >
        {{ $label }}

        @if ($required)
            <span
                class="text-danger-ink"
                aria-hidden="true"
            >
                *
            </span>
        @endif
    </label>

    @if ($type === 'textarea')
        <textarea
            id="{{ $id }}"
            name="{{ $name }}"
            rows="{{ $rows }}"
            @required($required)
            @disabled($disabled)
            aria-invalid="{{ $errorMessage ? 'true' : 'false' }}"
            @if ($describedBy)
                aria-describedby="{{ $describedBy }}"
            @endif
            {{ $controlAttributes }}
        >{{ old($name, $value) }}</textarea>
    @elseif ($type === 'select')
        <select
            id="{{ $id }}"
            name="{{ $name }}"
            @required($required)
            @disabled($disabled)
            aria-invalid="{{ $errorMessage ? 'true' : 'false' }}"
            @if ($describedBy)
                aria-describedby="{{ $describedBy }}"
            @endif
            {{ $controlAttributes }}
        >
            {{ $slot }}
        </select>
    @else
        <div class="relative">
            <input
                id="{{ $id }}"
                name="{{ $name }}"
                type="{{ $type }}"
                value="{{ $type === 'password'
                    ? ''
                    : old($name, $value) }}"
                @required($required)
                @disabled($disabled)
                aria-invalid="{{ $errorMessage ? 'true' : 'false' }}"
                @if ($describedBy)
                    aria-describedby="{{ $describedBy }}"
                @endif
                @if ($type === 'password')
                    x-bind:type="showPassword ? 'text' : 'password'"
                @endif
                {{ $controlAttributes }}
            >

            @if ($type === 'password')
                <button
                    type="button"
                    x-on:mousedown.prevent
                    x-on:click="showPassword = ! showPassword"
                    class="absolute inset-y-0 right-0 min-h-11 px-4
                        text-sm font-bold text-primary-ink"
                    x-bind:aria-label="
                        showPassword
                            ? 'Hide password'
                            : 'Show password'
                    "
                >
                    <span x-text="showPassword ? 'Hide' : 'Show'"></span>
                </button>
            @endif
        </div>
    @endif

    @if ($hint)
        <p
            id="{{ $hintId }}"
            class="text-sm leading-relaxed text-ink-muted"
        >
            {{ $hint }}
        </p>
    @endif

    @if ($errorMessage)
        <p
            id="{{ $errorId }}"
            class="text-sm font-semibold text-danger-ink"
            role="alert"
        >
            {{ $errorMessage }}
        </p>
    @endif
</div>
