<?php

namespace App\Http\Controllers;

use App\Services\LevelService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgressController extends Controller
{
    public function __construct(
        protected LevelService $levels,
    ) {}

    public function index(
        Request $request
    ): View {
        return view(
            'progress.index',
            $this->levels->progress(
                $request->user()
            )
        );
    }
}