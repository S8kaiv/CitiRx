<?php

namespace App\Http\Controllers;

use App\Models\QuestionBookmark;
use App\Models\RxVault;
use App\Services\PracticeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use InvalidArgumentException;
use LogicException;
use RuntimeException;

class RxVaultController extends Controller
{
    private const PAGE_SIZE = 12;

    private const QUESTION_RELATIONS = [
        'question.correctChoice',
        'question.competency.domain',
    ];

    public function __construct(
        protected PracticeService $practice,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $requestedTab = $request->query(
            'tab',
            PracticeService::MODE_MISTAKES
        );

        $activeTab = in_array(
            $requestedTab,
            PracticeService::DRILL_MODES,
            true
        )
            ? $requestedTab
            : PracticeService::MODE_MISTAKES;

        $vaultQuery = RxVault::query()
            ->where('user_id', $user->user_id);

        $bookmarkQuery = QuestionBookmark::query()
            ->where('user_id', $user->user_id);

        /*
         * Counts remain lightweight while only the active
         * tab's complete records are loaded.
         */
        $activeCount = (clone $vaultQuery)
            ->where('is_cleared', false)
            ->count();

        $clearedCount = (clone $vaultQuery)
            ->where('is_cleared', true)
            ->count();

        $bookmarksCount =
            (clone $bookmarkQuery)->count();

        $grouped = collect();
        $groupedBookmarks = collect();

        if (
            $activeTab
            === PracticeService::MODE_MISTAKES
        ) {
            $records = (clone $vaultQuery)
                ->where('is_cleared', false)
                ->with(self::QUESTION_RELATIONS)
                ->latest('added_at')
                ->simplePaginate(self::PAGE_SIZE)
                ->withQueryString();

            $grouped = $this->groupByDomain(
                $records->getCollection()
            );
        } else {
            $records = (clone $bookmarkQuery)
                ->with(self::QUESTION_RELATIONS)
                ->latest('created_at')
                ->simplePaginate(self::PAGE_SIZE)
                ->withQueryString();

            $groupedBookmarks = $this->groupByDomain(
                $records->getCollection()
            );
        }

        return view('vault.index', [
            'activeTab' => $activeTab,
            'grouped' => $grouped,
            'groupedBookmarks' => $groupedBookmarks,
            'records' => $records,
            'activeCount' => $activeCount,
            'clearedCount' => $clearedCount,
            'bookmarksCount' => $bookmarksCount,
        ]);
    }

    public function startDrill(
        Request $request,
        string $mode,
    ): RedirectResponse {
        if (! in_array(
            $mode,
            PracticeService::DRILL_MODES,
            true
        )) {
            abort(404);
        }

        try {
            $session = $this->practice->startSession(
                $request->user(),
                PracticeService::DEFAULT_DRILL_LENGTH,
                null,
                $mode,
            );
        } catch (
            InvalidArgumentException |
            LogicException |
            RuntimeException $exception
        ) {
            return redirect()
                ->route('vault.index', ['tab' => $mode])
                ->with('error', $exception->getMessage());
        }

        return redirect()->route(
            'practice.show',
            ['session' => $session->session_id]
        );
    }

    /**
     * Group only the currently displayed page by domain.
     */
    private function groupByDomain(
        Collection $records
    ): Collection {
        return $records
            ->filter(
                fn($record) =>
                $record->question?->competency?->domain
                    !== null
            )
            ->groupBy(
                fn($record) =>
                $record
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
