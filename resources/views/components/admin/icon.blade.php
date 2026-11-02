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

        @case('practice')
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M15.59 14.37a6 6 0 0 1-5.84 7.38v-4.8
                    m5.84-2.58a14.98 14.98 0 0 0
                    6.16-12.12A14.98 14.98 0 0 0 9.631 8.41
                    m5.96 5.96a14.926 14.926 0 0 1-5.841 2.58
                    m-.119-8.54a6 6 0 0 0-7.381 5.84h4.8
                    m2.581-5.84a14.927 14.927 0 0 0-2.58 5.84
                    m2.699 2.7c-.103.021-.207.041-.311.06
                    a15.09 15.09 0 0 1-2.448-2.448
                    14.9 14.9 0 0 1 .06-.312
                    m-2.24 2.39a4.493 4.493 0 0 0-1.757 4.306
                    4.493 4.493 0 0 0 4.306-1.758
                    M16.5 9a1.5 1.5 0 1 1-3 0
                    1.5 1.5 0 0 1 3 0Z"
            />
            @break

        @case('leaderboard')
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M7.5 14.25v2.25m4.5-6v6m4.5-9v9
                    M6 20.25h12A2.25 2.25 0 0 0 20.25 18V6
                    A2.25 2.25 0 0 0 18 3.75H6
                    A2.25 2.25 0 0 0 3.75 6v12
                    A2.25 2.25 0 0 0 6 20.25Z"
            />
            @break

        @case('mock-board')
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M9 12.75 11.25 15 15 9.75
                    M9 3.75h6m-6 0A2.25 2.25 0 0 0 6.75 6v12
                    A2.25 2.25 0 0 0 9 20.25h6
                    A2.25 2.25 0 0 0 17.25 18V6
                    A2.25 2.25 0 0 0 15 3.75m-6 0v1.5h6v-1.5"
            />
            @break

        @case('progress')
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M2.25 18 9 11.25l4.306 4.306
                    a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22
                    m0 0-5.94-2.281m5.94 2.28-2.28 5.941"
            />
            @break

        @case('vault')
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75
                    m-.75 11.25h10.5a2.25 2.25 0 0 0
                    2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25
                    H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75
                    a2.25 2.25 0 0 0 2.25 2.25Z"
            />
            @break

        @case('readiness')
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25
                    c.621 0 1.125.504 1.125 1.125v6.75
                    C7.5 20.496 6.996 21 6.375 21h-2.25
                    A1.125 1.125 0 0 1 3 19.875v-6.75Z
                    M9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25
                    c.621 0 1.125.504 1.125 1.125v11.25
                    c0 .621-.504 1.125-1.125 1.125h-2.25
                    a1.125 1.125 0 0 1-1.125-1.125V8.625Z
                    M16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25
                    C20.496 3 21 3.504 21 4.125v15.75
                    c0 .621-.504 1.125-1.125 1.125h-2.25
                    a1.125 1.125 0 0 1-1.125-1.125V4.125Z"
            />
            @break

        @case('badges')
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15
                    a3 3 0 0 1 3-3m9 0v-3.375
                    c0-.621-.503-1.125-1.125-1.125h-.871
                    M7.5 18.75v-3.375c0-.621.504-1.125
                    1.125-1.125h.872m5.003 0H9.497
                    m5.003 0a7.476 7.476 0 0 0 1.996-3.874
                    l.443-2.124a1.875 1.875 0 0 0-1.834-2.252h-1.07
                    a4.875 4.875 0 0 0-4.068 2.193l-.467.7
                    m0 0a4.875 4.875 0 0 0-4.068-2.193H4.427
                    a1.875 1.875 0 0 0-1.834 2.252l.443 2.124
                    c.338 1.62 1.096 3.09 2.196 4.174"
            />
            @break

        @case('profile')
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M15.75 6a3.75 3.75 0 1 1-7.5 0
                    3.75 3.75 0 0 1 7.5 0Z
                    M4.501 20.118a7.5 7.5 0 0 1 14.998 0
                    A17.933 17.933 0 0 1 12 21.75
                    c-2.676 0-5.216-.584-7.499-1.632Z"
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
