<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\LeaderboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaderboardController extends Controller
{
    public function __construct(
        protected LeaderboardService $leaderboard,
    ) {}

    public function index(
        Request $request,
    ): View {
        /** @var User $student */
        $student = $request->user();

        return view(
            'leaderboard.index',
            $this->leaderboard->forStudent(
                $student,
            ),
        );
    }
}
