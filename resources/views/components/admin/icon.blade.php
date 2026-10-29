@props([
    'name',
    'class' => 'h-5 w-5',
])

<svg
    {{ $attributes->merge([
        'class' => $class,
        'fill' => 'none',
        'viewBox' => '0 0 24 24',
        'stroke' => 'currentColor',
        'stroke-width' => '2.25',
        'aria-hidden' => 'true',
    ]) }}
>
    @switch($name)
        @case('dashboard')
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="m2.25 12 8.954-8.955c.44-.439 1.152-.439
                    1.591 0L21.75 12M4.5 9.75v10.125c0
                    .621.504 1.125 1.125 1.125H9.75v-4.875c0
                    -.621.504-1.125 1.125-1.125h2.25c.621 0
                    1.125.504 1.125 1.125V21h4.125c.621 0
                    1.125-.504 1.125-1.125V9.75"
            />
            @break

        @case('users')
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M18 18.72a9.094 9.094 0 0 0
                    3.741-.479 3 3 0 0 0-4.682-2.72M15
                    6.75a3 3 0 1 1-6 0 3 3 0 0 1 6
                    0ZM6 20.25a6 6 0 0 1 12 0"
            />
            @break

        @case('cohorts')
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9
                    6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5
                    3H15m-1.5 3H15M9 21v-4.5h6V21"
            />
            @break

        @case('questions')
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M19.5 14.25v-2.625a3.375 3.375 0 0
                    0-3.375-3.375h-1.5A1.125 1.125 0 0 1
                    13.5 7.125v-1.5a3.375 3.375 0 0
                    0-3.375-3.375H8.25m0 12.75h7.5m-7.5
                    3h4.5M10.5 2.25H5.625c-.621 0-1.125.504-1.125
                    1.125v17.25c0 .621.504 1.125 1.125
                    1.125h12.75c.621 0 1.125-.504
                    1.125-1.125V11.625a9 9 0 0 0-9-9Z"
            />
            @break

        @case('pearls')
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M12 6.253v13m0-13C10.832 5.477 9.246 5
                    7.5 5S4.168 5.477 3 6.253v13C4.168
                    18.477 5.754 18 7.5 18s3.332.477
                    4.5 1.253m0-13C13.168 5.477 14.754 5
                    16.5 5s3.332.477 4.5 1.253v13C19.832
                    18.477 18.246 18 16.5 18s-3.332.477-4.5
                    1.253"
            />
            @break

        @case('forms')
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M9 12h6m-6 4h6m2.25 5H6.75A2.25
                    2.25 0 0 1 4.5 18.75V5.25A2.25
                    2.25 0 0 1 6.75 3h6.879c.597 0
                    1.17.237 1.591.659l2.121 2.121c.422.422.659.994.659
                    1.591V18.75A2.25 2.25 0 0 1 17.25 21Z"
            />
            @break

        @case('imports')
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M12 16.5V3m0 13.5-4.5-4.5m4.5
                    4.5 4.5-4.5M4.5 15.75v3A2.25 2.25
                    0 0 0 6.75 21h10.5a2.25 2.25
                    0 0 0 2.25-2.25v-3"
            />
            @break

        @case('participants')
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M15 6.75a3 3 0 1 1-6 0 3 3
                    0 0 1 6 0ZM6 20.25a6 6 0 0
                    1 12 0"
            />
            @break

        @case('analytics')
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M4.5 20.25V10.5m5 9.75V4.5m5
                    15.75v-6m5 6V7.5"
            />
            @break

        @case('pilot')
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M6.75 3v2.25M17.25 3v2.25M3.75
                    8.25h16.5M5.25 4.5h13.5A1.5 1.5
                    0 0 1 20.25 6v13.5A1.5 1.5
                    0 0 1 18.75 21H5.25a1.5 1.5
                    0 0 1-1.5-1.5V6a1.5 1.5
                    0 0 1 1.5-1.5ZM9.75 12.75l1.5
                    1.5 3-3"
            />
            @break

        @case('audit')
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M12 6v6h4.5m4.5 0a9 9
                    0 1 1-9-9 9 9 0 0 1 9 9Z"
            />
            @break

        @case('health')
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M3.75 4.5h16.5v12H3.75v-12ZM8.25
                    19.5h7.5M12 16.5v3M6.75
                    10.5H9l1.5-3 2.25 6 1.5-3h3"
            />
            @break

        @case('menu')
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M4.5 6.75h15m-15 5.25h15m-15
                    5.25h15"
            />
            @break

        @case('close')
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M6 18 18 6M6 6l12 12"
            />
            @break

        @case('arrow')
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M8.25 4.5 15.75 12l-7.5 7.5"
            />
            @break

        @default
            <rect
                x="4"
                y="4"
                width="6"
                height="6"
                rx="1"
            />
            <rect
                x="14"
                y="4"
                width="6"
                height="6"
                rx="1"
            />
            <rect
                x="4"
                y="14"
                width="6"
                height="6"
                rx="1"
            />
            <rect
                x="14"
                y="14"
                width="6"
                height="6"
                rx="1"
            />
    @endswitch
</svg>
