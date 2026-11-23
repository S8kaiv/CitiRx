@props(['currentXp', 'currentLevel', 'themeXpThresholds'])

@php
    $themes = collect(config('student-themes'));
    $nextTheme = $themes->first(fn (array $theme) => $theme['requiredLevel'] > $currentLevel);
    $lastUnlockedTheme = $themes->last(fn (array $theme) => $theme['requiredLevel'] <= $currentLevel);
    $target = $nextTheme ? $themeXpThresholds->get($nextTheme['requiredLevel']) : null;
    $start = (int) $themeXpThresholds->get($lastUnlockedTheme['requiredLevel'] ?? 1, 0);
    $remaining = $target === null ? null : max(0, (int) $target - $currentXp);
    $progress = $target !== null && $target > $start ? min(100, max(0, (int) round(100 * ($currentXp - $start) / ($target - $start)))) : null;
@endphp

<div x-ref="themeJourney" tabindex="-1" class="space-y-2 border-t border-line pt-4">
    @if ($nextTheme)
        <div class="flex items-center gap-2">
            <x-theme-reward-art :theme="$nextTheme" />
            <div class="min-w-0">
                <p class="text-xs font-semibold text-ink-muted">Next theme</p>
                <p class="text-sm font-bold text-ink">{{ $nextTheme['name'] }}</p>
            </div>
        </div>

        <p class="text-xs font-bold text-gold-ink">
            @if ($remaining !== null && $remaining > 0)
                {{ number_format($remaining) }} XP to unlock
            @else
                Unlocks at Level {{ $nextTheme['requiredLevel'] }}
            @endif
        </p>

        @if ($progress !== null)
            <div class="h-2.5 overflow-hidden rounded-full bg-surface-2"
                role="progressbar" aria-label="Progress toward {{ $nextTheme['name'] }}" aria-valuemin="0"
                aria-valuemax="100" aria-valuenow="{{ $progress }}">
                <div class="theme-journey-fill h-full rounded-full" style="width: {{ $progress }}%"></div>
            </div>
        @endif
    @else
        <p class="text-sm font-bold text-ink">All themes unlocked</p>
    @endif

    <a href="{{ route('profile.edit', ['tab' => 'settings']) }}#theme-locker"
        class="inline-flex min-h-11 items-center text-sm font-bold text-primary-ink underline underline-offset-4">
        Open Theme Locker
    </a>
</div>
