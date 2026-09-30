<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ResearchAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResearchAnalyticsController extends Controller
{
    public function __construct(
        protected ResearchAnalyticsService $analytics,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()?->role === 'admin', 403);

        return view('admin.research-analytics.index', [
            'report' => $this->analytics->fullReport(),
        ]);
    }
}
