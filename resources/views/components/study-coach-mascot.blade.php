@props(['instance'])

<svg
    class="block h-full w-full"
    viewBox="54 28 107 138"
    aria-hidden="true"
    focusable="false"
    x-data="{
        get mascotTint() {
            return {
                'theme-forest': '#219d74',
                'theme-sakura': '#ed5981',
                'theme-midnight': '#6878ba',
                'theme-ocean': '#229db9',
                'theme-solar': '#f5a521',
            }[globalTheme];
        },
    }"
>
    <defs>
        <!-- Target the purple areas while keeping the original shading. -->
        <filter id="coach-tint-{{ $instance }}" color-interpolation-filters="sRGB">
            <feColorMatrix in="SourceGraphic" values="
                0 0 0 0 0
                0 0 0 0 0
                0 0 0 0 0
                -7 0 7 0 -.91" result="purple"/>
            <feFlood :flood-color="mascotTint" result="color"/>
            <feBlend in="color" in2="SourceGraphic" mode="color" result="tinted"/>
            <feComposite in="tinted" in2="purple" operator="in" result="purpleTint"/>
            <feComposite in="purpleTint" in2="SourceGraphic" operator="atop"/>
        </filter>

        <path id="coach-jacket-{{ $instance }}" d="
            M79.5 105 C84 110 107 107 122 101 C128 99 131.5 96.5 132.4 94
            C135 105 137 112 137 118 C138 132 131 140 123.5 143
            C109 150 94 146 87 136 C83 130 82 124 81 117 Z"/>

        <radialGradient id="coach-fabric-{{ $instance }}" cx="71%" cy="30%" r="83%">
            <stop stop-color="#fff" stop-opacity=".14"/>
            <stop offset=".56" stop-color="#fff" stop-opacity="0"/>
            <stop offset="1" stop-color="#121224" stop-opacity=".22"/>
        </radialGradient>
    </defs>

    {{-- Solar Gold cape goes behind the mascot. --}}
    <g x-show="globalTheme === 'theme-solar'">
        <path d="M84 105Q76 110 75 126Q73 138 64 145Q79 151 94 141L110 118Z" fill="#a85432"/>
        <path d="M84 113Q81 135 70 144Q81 145 90 140L100 121Z" fill="#d37a36"/>
        <path d="M65 145Q80 150 93 141" fill="none" stroke="#edac4b" stroke-width="1.4"/>
    </g>

    <image
        href="{{ asset('image/pill-mascot.png') }}"
        width="200"
        height="200"
        :filter="mascotTint ? 'url(#coach-tint-{{ $instance }})' : null"
    />

    <g x-show="globalTheme === 'theme-forest'">
        <use href="#coach-jacket-{{ $instance }}" fill="#fbfaf2"/>
        <path d="M93 107Q107 106 124 100L112 135Z" fill="#176553"/>
        <path d="M92 107L100 107L112 135L96 122L99 117L91 113Z
                 M124 100L116 103L112 135L127 116L122 112L127 105Z" fill="#a6d6c2"/>
        <path d="M120 122L131 120L132 127Q127 133 121 130Z" fill="#e7f0e7"
              stroke="#648f83" stroke-width="1.1"/>
        <path d="M112 135L114 145" stroke="#97b1a7" stroke-width="1.2"/>
        <use href="#coach-jacket-{{ $instance }}" fill="url(#coach-fabric-{{ $instance }})"/>
    </g>

    <g x-show="globalTheme === 'theme-sakura'">
        <use href="#coach-jacket-{{ $instance }}" fill="#ab496d"/>
        <path d="M91 108L112 123L129 99M104 128L112 123" fill="none"
              stroke="#f4b7bf" stroke-width="3.5" stroke-linecap="round"/>
        <path d="M81.5 119Q109 128 136.8 112L137 119Q110 135 83 126Z" fill="#ffe4cb"/>
        <path d="M117 125L127 119L126 125L132 128L119 129Z" fill="#f6b18d"/>
        <use href="#coach-jacket-{{ $instance }}" fill="url(#coach-fabric-{{ $instance }})"/>

        <g transform="translate(119 49)" stroke="#973b58" stroke-width="1.35">
            <path d="M0-5C-3-13-9-9-7-4C-15-4-14 4-7 4C-10 11-3 13 0 7
                     C3 13 10 11 7 4C14 4 15-4 7-4C9-9 3-13 0-5Z" fill="#fff0f1"/>
            <circle r="2.4" fill="#f5b35f" stroke="none"/>
        </g>
    </g>

    <g x-show="globalTheme === 'theme-midnight'">
        <use href="#coach-jacket-{{ $instance }}" fill="#343c61"/>
        <path d="M79.5 105Q91 112 108 106Q121 103 132.4 94Q132 105 118 114
                 L108 111L99 117Q85 117 79.5 105Z" fill="#8793c0"/>
        <path d="M105 114L106 123M113 112L116 121" stroke="#eeebf7"
              stroke-width="1.8" stroke-linecap="round"/>
        <path d="M99 130L122 126L127 132Q114 138 96 135Z" fill="#515d83"/>
        <use href="#coach-jacket-{{ $instance }}" fill="url(#coach-fabric-{{ $instance }})"/>
    </g>

    <g x-show="globalTheme === 'theme-ocean'">
        <use href="#coach-jacket-{{ $instance }}" fill="#17596e"/>
        <path d="M85 107Q108 110 131 96L124 113L111 124L96 119Z" fill="#fff0d0"/>
        <path d="M91 110L98 116L110 121L122 112L127 102" fill="none"
              stroke="#45acb4" stroke-width="1.5"/>
        <path d="M111 121L116 124L114 133L110 130L112 125L108 124Z" fill="#f48d7c"/>
        <use href="#coach-jacket-{{ $instance }}" fill="url(#coach-fabric-{{ $instance }})"/>
    </g>

    <g x-show="globalTheme === 'theme-solar'">
        <path d="M79.5 105Q88 109 99 107Q95 115 82 119Z" fill="#cb6f32"/>
        <circle cx="99" cy="108" r="2.9" fill="#fff1bd" stroke="#904e29" stroke-width="1.3"/>

        <g stroke="#87451f" stroke-width="1.75" stroke-linejoin="round">
            <path d="M88 44L85 32L95 37L101 29.5L107 37L117 32L114 44Q101 48 88 44Z" fill="#edaa40"/>
            <path d="M87.5 41Q101 44 114.5 41L114 45Q101 49 88 45Z" fill="#ffda82"/>
            <path d="M101 36L104 39L101 42L98 39Z" fill="#fff2bf" stroke-width="1"/>
        </g>
    </g>
</svg>
