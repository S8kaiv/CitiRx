<?php

namespace App\Http\Controllers;

use App\Models\QuestionBookmark;
use App\Models\RxVault;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class RxVaultController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $requestedTab = $request->query(
            'tab',
            'mistakes'
        );

        $activeTab = in_array(
            $requestedTab,
            ['mistakes', 'bookmarks'],
            true
        ) ? $requestedTab : 'mistakes';

        $vaultQuery = RxVault::query()
            ->where(
                'user_id',
                $user->user_id
            );

        $active = (clone $vaultQuery)
            ->where('is_cleared', false)
            ->with([
                'question.correctChoice',
                'question.competency.domain',
            ])
            ->orderByDesc('added_at')
            ->get();

        $clearedCount = (clone $vaultQuery)
            ->where('is_cleared', true)
            ->count();

        $bookmarks = QuestionBookmark::query()
            ->where(
                'user_id',
                $user->user_id
            )
            ->with([
                'question.correctChoice',
                'question.competency.domain',
            ])
            ->latest()
            ->get();

        return view('vault.index', [
            'activeTab' => $activeTab,

            'grouped' => $this->groupByDomain(
                $active
            ),

            'groupedBookmarks' => $this->groupByDomain(
                $bookmarks
            ),

            'activeCount' => $active->count(),

            'clearedCount' => $clearedCount,

            'bookmarksCount' => $bookmarks->count(),
        ]);
    }

    /**
     * Group Rx Vault entries or bookmarks by TOS domain.
     */
    private function groupByDomain(
        Collection $records
    ): Collection {
        return $records
            ->filter(
                fn ($record) =>
                    $record->question?->competency?->domain
                    !== null
            )
            ->groupBy(
                fn ($record) => $record
                    ->question
                    ->competency
                    ->domain
                    ->domain_id
            )
            ->map(function (Collection $records): array {
                $domain = $records
                    ->first()
                    ->question
                    ->competency
                    ->domain;

                return [
                    'domain_id' => $domain->domain_id,
                    'domain_name' => $domain->domain_name,
                    'entries' => $records,
                ];
            })
            ->sortBy('domain_id')
            ->values();
    }
}
