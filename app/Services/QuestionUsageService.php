<?php

namespace App\Services;

use App\Models\Question;
use Carbon\CarbonInterface;

class QuestionUsageService
{
    /**
     * Mark questions as served without replacing an existing
     * first-served timestamp.
     *
     * @param  iterable<int, string>  $questionIds
     */
    public function markServed(
        iterable $questionIds,
        ?CarbonInterface $servedAt = null,
    ): void {
        $ids = collect($questionIds)
            ->map(
                fn (mixed $id): string => trim(
                    (string) $id,
                ),
            )
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return;
        }

        Question::query()
            ->whereKey(
                $ids->all(),
            )
            ->whereNull(
                'first_served_at',
            )
            ->update([
                'first_served_at' => $servedAt
                    ?? now(),
            ]);
    }
}
