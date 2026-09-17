<?php

namespace App\Http\Controllers;

use App\Models\RxVault;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RxVaultController extends Controller
{
    public function index(
        Request $request
    ): View {
        $user = $request->user();

        /*
         * Active questions still needing review.
         */
        $active = RxVault::query()
            ->where(
                'user_id',
                $user->user_id
            )
            ->where(
                'is_cleared',
                false
            )
            ->with([
                'question.correctChoice',
                'question.competency.domain',
            ])
            ->orderByDesc(
                'added_at'
            )
            ->get();

        /*
         * Historical cleared count.
         */
        $clearedCount =
            RxVault::query()
                ->where(
                    'user_id',
                    $user->user_id
                )
                ->where(
                    'is_cleared',
                    true
                )
                ->count();

        /*
         * Group active questions by domain.
         *
         * domain_id is used for ordering so we do not
         * depend on a possibly different display-order
         * column name.
         */
        $grouped =
            $active
                ->groupBy(
                    fn ($entry) => $entry
                        ->question
                        ->competency
                        ->domain
                        ->domain_id
                )
                ->sortBy(
                    fn ($group) => $group
                        ->first()
                        ->question
                        ->competency
                        ->domain
                        ->domain_id
                )
                ->map(
                    function ($group) {

                        $domain =
                            $group
                                ->first()
                                ->question
                                ->competency
                                ->domain;

                        return [
                            'domain_id' => $domain->domain_id,

                            'domain_name' => $domain->domain_name,

                            'entries' => $group,
                        ];
                    }
                )
                ->values();

        return view(
            'vault.index',
            [
                'grouped' => $grouped,

                'activeCount' => $active->count(),

                'clearedCount' => $clearedCount,
            ]
        );
    }
}
