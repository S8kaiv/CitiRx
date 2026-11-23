@props(['levelUp' => session('level_up')])

@if (is_array($levelUp) && isset($levelUp['level_number'], $levelUp['tier_name']))
    @php
        $newLevel = (int) $levelUp['level_number'];
        $oldLevel = (int) ($levelUp['previous_level'] ?? $newLevel - 1);
        $unlockedTheme = collect(config('student-themes'))->last(
            fn (array $theme) => $theme['requiredLevel'] > max(1, $oldLevel) && $theme['requiredLevel'] <= $newLevel,
        );
    @endphp

    <section class="theme-level-up mb-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-line bg-surface p-4">
        <div class="flex items-center gap-3" role="status">
            @if ($unlockedTheme)
                <x-theme-reward-art :theme="$unlockedTheme" />
            @endif

            <div>
                <p class="text-xs font-bold text-gold-ink">Level {{ $newLevel }} &middot; {{ $levelUp['tier_name'] }}</p>
                <p class="font-display text-base font-extrabold text-ink">
                    {{ $unlockedTheme ? $unlockedTheme['name'].' unlocked' : 'Level up!' }}
                </p>
                @if ($unlockedTheme && request()->routeIs('practice.show'))
                    <p class="text-sm text-ink-muted">Find it in your Theme Locker after this session.</p>
                @endif
            </div>
        </div>

        @if ($unlockedTheme && ! request()->routeIs('practice.show'))
            <x-ui.button variant="secondary" :href="route('dashboard', ['preview-theme' => $unlockedTheme['id']])"
                x-on:click="$el.hash = $event.detail > 0 ? 'theme-preview-motion' : ''">
                Preview theme
            </x-ui.button>
        @endif
    </section>
@endif
