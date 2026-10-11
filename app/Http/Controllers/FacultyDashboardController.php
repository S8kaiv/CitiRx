<?php

namespace App\Http\Controllers;

use App\Services\FacultyDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FacultyDashboardController extends Controller
{
    public function __construct(
        protected FacultyDashboardService $dashboard,
    ) {}

    public function index(
        Request $request,
    ): View {
        return view(
            'dashboard.faculty',
            $this->dashboard->forFaculty(
                $request->user(),
            ),
        );
    }
}
