<?php

namespace App\Services;

use App\Models\RxVault;
use App\Models\User;

class RxVaultService
{
    /**
     * Number of trusted consecutive correct answers
     * required to clear a Vault question.
     */
    public const CLEAR_THRESHOLD = 3;

    /**
     * Must be called exactly once for each successfully
     * recorded Practice response.
     *
     * PracticeService provides the surrounding database
     * transaction and serializes responses.
     */
    public function handleResponse(
        User $user,
        string $questionId,
        bool $isCorrect,
        bool $isSpeedFlagged,
    ): void {
        $entry = RxVault::query()
            ->where(
                'user_id',
                $user->user_id
            )
            ->where(
                'question_id',
                $questionId
            )
            ->first();

        /*
         * =============================================
         * CORRECT ANSWER
         * =============================================
         */
        if ($isCorrect) {

            /*
             * A speed-flagged correct answer is not
             * trusted for Vault-clearance progress.
             */
            if ($isSpeedFlagged) {
                return;
            }

            /*
             * Correct answers do not create new
             * Vault entries.
             */
            if (! $entry) {
                return;
            }

            /*
             * Already cleared.
             *
             * Keep it at 3 / 3 until the student
             * misses the question again.
             */
            if ($entry->is_cleared) {
                return;
            }

            $entry->consecutive_correct_count =
                min(
                    self::CLEAR_THRESHOLD,
                    $entry->consecutive_correct_count + 1
                );

            /*
             * Clear exactly when the threshold
             * is reached.
             */
            if (
                $entry->consecutive_correct_count
                === self::CLEAR_THRESHOLD
            ) {
                $entry->is_cleared = true;
                $entry->cleared_at = now();
            }

            $entry->save();

            return;
        }

        /*
         * =============================================
         * WRONG ANSWER
         * =============================================
         *
         * A wrong answer counts even if speed-flagged.
         */

        if ($entry) {

            /*
             * Break the correct streak.
             *
             * If previously cleared, reopen it.
             */
            $entry->consecutive_correct_count = 0;
            $entry->is_cleared = false;
            $entry->cleared_at = null;

            $entry->save();

            return;
        }

        /*
         * First Practice miss for this question.
         */
        RxVault::create([
            'user_id' => $user->user_id,

            'question_id' => $questionId,

            'consecutive_correct_count' => 0,

            'is_cleared' => false,

            'added_at' => now(),

            'cleared_at' => null,
        ]);
    }
}
