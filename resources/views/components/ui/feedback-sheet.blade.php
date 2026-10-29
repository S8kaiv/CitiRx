@props([
    'status' => 'correct',
    'title' => null,
])

@php
    $safeStatus = in_array(
        $status,
        [
            'correct',
            'incorrect',
            'information',
        ],
        true,
    )
        ? $status
        : 'information';

    $resolvedTitle = $title ?? match ($safeStatus) {
        'correct' => 'Correct answer',
        'incorrect' => 'Review this answer',
        default => 'Answer feedback',
    };
@endphp

<section
    role="status"
    aria-live="polite"
    {{ $attributes->class([
        'ui-feedback-sheet',
        "ui-feedback-sheet-{$safeStatus}",
    ]) }}
>
    <div class="ui-feedback-heading">
        <span class="ui-feedback-icon" aria-hidden="true">
            @if ($safeStatus === 'correct')
                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2.5"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="m5 12.5 4.25 4.25L19 7"
                    />
                </svg>
            @elseif ($safeStatus === 'incorrect')
                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2.5"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 8v5"
                    />

                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 17.25h.01"
                    />

                    <circle cx="12" cy="12" r="9" />
                </svg>
            @else
                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2.5"
                >
                    <circle cx="12" cy="12" r="9" />

                    <path
                        stroke-linecap="round"
                        d="M12 11v5"
                    />

                    <path
                        stroke-linecap="round"
                        d="M12 8h.01"
                    />
                </svg>
            @endif
        </span>

        <h3 class="ui-feedback-title">
            {{ $resolvedTitle }}
        </h3>
    </div>

    @isset($answer)
        <div class="ui-feedback-answer">
            {{ $answer }}
        </div>
    @endisset

    @if (trim((string) $slot) !== '')
        <div class="ui-feedback-body">
            {{ $slot }}
        </div>
    @endif

    @isset($meta)
        <div class="ui-feedback-meta">
            {{ $meta }}
        </div>
    @endisset
</section>
