<?php

namespace App\Services;

use App\Models\Level;
use App\Models\User;
use LogicException;

class LevelService
{
    /**
     * Synchronize users.current_level with users.total_xp.
     *
     * Returns a small notification payload only when the user
     * genuinely moves upward from an already initialized level.
     *
     * @return array{level_number: int, tier_name: string}|null
     */
    public function sync(User $user): ?array
    {
        /*
         * BadgeService updates a separately queried User model.
         * Refreshing here ensures badge XP is included.
         */
        $user->refresh();

        $level = Level::query()
            ->with('tier')
            ->where('min_xp', '<=', (int) $user->total_xp)
            ->orderByDesc('min_xp')
            ->first();

        if (! $level) {
            throw new LogicException(
                'No level threshold is configured for this XP total.'
            );
        }

        return $this->apply($user, $level);
    }

    /**
     * Build all information required by the progress page.
     *
     * @return array<string, mixed>
     */
    public function progress(User $user): array
    {
        $user->refresh();

        $levels = Level::query()
            ->with('tier')
            ->orderBy('min_xp')
            ->get();

        $currentXp = (int) $user->total_xp;

        $currentLevel = $levels->last(
            fn (Level $level) =>
                (int) $level->min_xp <= $currentXp
        );

        if (! $currentLevel) {
            throw new LogicException(
                'No level threshold is configured for this XP total.'
            );
        }

        /*
         * Repair the cached current_level if necessary.
         * No toast is shown merely for visiting the page.
         */
        $this->apply($user, $currentLevel);

        $nextLevel = $levels->first(
            fn (Level $level) =>
                (int) $level->min_xp > $currentXp
        );

        $xpToNext = $nextLevel
            ? max(
                0,
                (int) $nextLevel->min_xp - $currentXp
            )
            : 0;

        $levelSpan = $nextLevel
            ? (int) $nextLevel->min_xp
                - (int) $currentLevel->min_xp
            : 0;

        $progressPercent = $levelSpan > 0
            ? min(
                100,
                round(
                    100
                    * (
                        $currentXp
                        - (int) $currentLevel->min_xp
                    )
                    / $levelSpan,
                    1
                )
            )
            : 100.0;

        return compact(
            'user',
            'levels',
            'currentLevel',
            'nextLevel',
            'xpToNext',
            'progressPercent',
        );
    }

    /**
     * Persist the correct cached level and determine whether
     * a level-up notification should be returned.
     *
     * @return array{level_number: int, tier_name: string}|null
     */
    private function apply(
        User $user,
        Level $level
    ): ?array {
        $previousLevel = $user->current_level;
        $correctLevel = (int) $level->level_number;

        if ((int) $previousLevel === $correctLevel) {
            $user->setRelation('level', $level);

            return null;
        }

        $user->current_level = $correctLevel;
        $user->save();
        $user->setRelation('level', $level);

        /*
         * Initializing a null cache or repairing a downward mismatch
         * is not a genuine user-facing level-up.
         */
        if (
            $previousLevel === null
            || $correctLevel <= (int) $previousLevel
        ) {
            return null;
        }

        return [
            'level_number' => $correctLevel,
            'tier_name' => (string) $level->tier->tier_name,
        ];
    }
}