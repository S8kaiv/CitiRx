<?php

namespace App\Services;

use App\Models\AssessmentSession;
use App\Models\Badge;
use App\Models\ResponseTelemetryLog;
use App\Models\User;
use App\Models\UserBadge;
use App\Models\UserKnowledgeState;
use App\Models\XpTransaction;
use Illuminate\Support\Facades\DB;

class BadgeService
{
    /**
     * Evaluate all badge criteria for one user.
     *
     * Returns only badges that were newly unlocked
     * during this call.
     */
    public function evaluateAndAward(
        User $user
    ): array {
        return DB::transaction(
            function () use ($user) {

                /*
                 * Serialize badge evaluation for this user.
                 *
                 * This prevents two simultaneous requests
                 * from awarding the same badge twice.
                 */
                $user = User::query()
                    ->where(
                        'user_id',
                        $user->user_id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                $newlyUnlocked = [];

                $badges = Badge::query()
                    ->orderBy('badge_name')
                    ->get();

                foreach ($badges as $badge) {

                    if (
                        ! $this->qualifies(
                            $user,
                            $badge
                        )
                    ) {
                        continue;
                    }

                    /*
                     * user_badges already has a unique
                     * user + badge rule.
                     *
                     * firstOrCreate also makes the logic
                     * naturally idempotent.
                     */
                    $userBadge =
                        UserBadge::query()
                            ->firstOrCreate(
                                [
                                    'user_id' => $user->user_id,

                                    'badge_id' => $badge->badge_id,
                                ],
                                [
                                    'unlocked_at' => now(),
                                ]
                            );

                    /*
                     * Already owned before this call.
                     */
                    if (
                        ! $userBadge
                            ->wasRecentlyCreated
                    ) {
                        continue;
                    }

                    $reward =
                        (int)
                        $badge->xp_reward;

                    if ($reward > 0) {

                        /*
                         * Award XP through the same ledger
                         * used elsewhere in CitiRx.
                         */
                        XpTransaction::create([
                            'user_id' => $user->user_id,

                            'source_type' => 'badge',

                            'source_id' => $badge->badge_id,

                            'xp_delta' => $reward,

                            'reason' => "Badge unlocked: {$badge->badge_name}",
                        ]);

                        /*
                         * users.total_xp is the cached
                         * total XP value.
                         */
                        $user->total_xp +=
                            $reward;
                    }

                    $newlyUnlocked[] =
                        $badge;
                }

                if (
                    ! empty(
                        $newlyUnlocked
                    )
                ) {
                    $user->save();
                }

                return $newlyUnlocked;
            }
        );
    }

    /**
     * Determine whether a user satisfies
     * one badge's criteria.
     */
    private function qualifies(
        User $user,
        Badge $badge
    ): bool {
        $criteria =
            $badge->criteria_json ?? [];

        $type =
            $criteria['type'] ?? null;

        return match ($type) {

            'practice_sessions_completed' => $this
                ->checkPracticeSessionsCompleted(
                    $user,
                    (int) (
                        $criteria['count']
                        ?? 1
                    )
                ),

            'streak' => $this->checkStreak(
                $user,
                (int) (
                    $criteria['days']
                    ?? 1
                )
            ),

            'diagnostic_completed' => (bool)
                $user
                    ->is_diagnostic_completed,

            'competency_mastery' => $this
                ->checkCompetencyMastery(
                    $user,
                    (float) (
                        $criteria[
                            'threshold'
                        ]
                        ?? 0.85
                    )
                ),

            'practice_domains_touched' => $this
                ->checkPracticeDomainsTouched(
                    $user,
                    (int) (
                        $criteria['count']
                        ?? 6
                    )
                ),

            default => false,
        };
    }

    /**
     * Count completed Practice sessions only.
     *
     * Diagnostic and Mock Board sessions do not
     * count toward the First Step badge.
     */
    private function checkPracticeSessionsCompleted(
        User $user,
        int $count
    ): bool {
        return AssessmentSession::query()
            ->where(
                'user_id',
                $user->user_id
            )
            ->where(
                'session_type',
                'practice'
            )
            ->whereNotNull(
                'completed_at'
            )
            ->count() >= $count;
    }

    /**
     * Check the existing CitiRx streak counter.
     */
    private function checkStreak(
        User $user,
        int $days
    ): bool {
        return
            (int) $user->streak_count
            >= $days;
    }

    /**
     * Check whether any competency has reached
     * the mastery threshold.
     */
    private function checkCompetencyMastery(
        User $user,
        float $threshold
    ): bool {
        return UserKnowledgeState::query()
            ->where(
                'user_id',
                $user->user_id
            )
            ->where(
                'current_mastery_p_l',
                '>=',
                $threshold
            )
            ->exists();
    }

    /**
     * Count distinct domains encountered through
     * Practice telemetry only.
     *
     * Diagnostic telemetry is intentionally excluded.
     */
    private function checkPracticeDomainsTouched(
        User $user,
        int $count
    ): bool {
        $domainsPracticed =
            ResponseTelemetryLog::query()
                ->join(
                    'assessment_sessions',
                    'assessment_sessions.session_id',
                    '=',
                    'response_telemetry_logs.session_id'
                )
                ->join(
                    'tos_competencies',
                    'tos_competencies.competency_id',
                    '=',
                    'response_telemetry_logs.competency_id'
                )
                ->where(
                    'response_telemetry_logs.user_id',
                    $user->user_id
                )
                ->where(
                    'assessment_sessions.session_type',
                    'practice'
                )
                ->distinct()
                ->count(
                    'tos_competencies.domain_id'
                );

        return $domainsPracticed >= $count;
    }
}
