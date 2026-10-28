<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SystemHealthService;
use Illuminate\View\View;

class SystemHealthController extends Controller
{
    public function __construct(
        protected SystemHealthService $health,
    ) {}

    public function index(): View
    {
        return view(
            'admin.system-health.index',
            [
                'report' => $this->health->report(),
            ],
        );
    }
}
