<?php

namespace App\Http\Controllers;

use App\Models\QuestionBookmark;
use App\Models\RxVault;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RxVaultController extends Controller
{
    public function index(
        Request $request
    ): View {
        $user = $request->user();

        $activeTab = $request->query('tab', 'mistakes');

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
        $clearedCount = RxVault::query()
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
         */
        $grouped = $active
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
                    $domain = $group
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

        /*
         * Bookmarked questions saved for revision.
         */
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

        /*
         * Group bookmarked questions by domain.
         */
        $groupedBookmarks = $bookmarks
            ->groupBy(
                fn ($bookmark) => $bookmark
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
                    $domain = $group
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
                'activeTab' => $activeTab,
                'grouped' => $grouped,
                'groupedBookmarks' => $groupedBookmarks,
                'activeCount' => $active->count(),
                'clearedCount' => $clearedCount,
                'bookmarksCount' => $bookmarks->count(),
            ]
        );
    }

    public function drillMistakes(
        Request $request
    ): RedirectResponse {
        $user = $request->user();

        $hasMistakes = RxVault::query()
            ->where(
                'user_id',
                $user->user_id
            )
            ->where(
                'is_cleared',
                false
            )
            ->exists();

        if (! $hasMistakes) {
            return redirect()
                ->route('vault.index', ['tab' => 'mistakes'])
                ->with(
                    'status',
                    'Your Mistake Locker is clear! Keep practicing to identify weak spots.'
                );
        }

        return redirect()->route(
            'practice.start',
            ['mode' => 'mistakes']
        );
    }

    public function drillBookmarks(
        Request $request
    ): RedirectResponse {
        $user = $request->user();

        $hasBookmarks = QuestionBookmark::query()
            ->where(
                'user_id',
                $user->user_id
            )
            ->exists();

        if (! $hasBookmarks) {
            return redirect()
                ->route('vault.index', ['tab' => 'bookmarks'])
                ->with(
                    'error',
                    'You have not bookmarked any questions yet.'
                );
        }

        return redirect()->route(
            'practice.start',
            ['mode' => 'bookmarks']
        );
    }
}