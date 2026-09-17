<?php

namespace App\Http\Controllers;

use App\Models\Badge;
use App\Models\UserBadge;
use App\Models\XpTransaction;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BadgeController extends Controller
{
    public function index(
        Request $request
    ): View {
        $user =
            $request->user();

        $badges =
            Badge::query()
                ->orderBy(
                    'badge_name'
                )
                ->get();

        /*
         * Keep the whole UserBadge model rather than
         * only plucking unlocked_at.
         *
         * This guarantees that unlocked_at uses the
         * UserBadge datetime cast.
         */
        $userBadges =
            UserBadge::query()
                ->where(
                    'user_id',
                    $user->user_id
                )
                ->get()
                ->keyBy(
                    'badge_id'
                );

        /*
         * Calculate historical badge XP from the XP
         * ledger instead of re-summing current badge
         * reward values.
         *
         * If a badge reward changes in the future,
         * historical XP remains correct.
         */
        $badgeXpTotal =
            XpTransaction::query()
                ->where(
                    'user_id',
                    $user->user_id
                )
                ->where(
                    'source_type',
                    'badge'
                )
                ->sum(
                    'xp_delta'
                );

        return view(
            'badges.index',
            [
                'badges' => $badges,

                'userBadges' => $userBadges,

                'badgeXpTotal' => (int)
                    $badgeXpTotal,
            ]
        );
    }
}
