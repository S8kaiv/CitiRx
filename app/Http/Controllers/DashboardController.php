<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return match ($user->role) {
            'admin'   => view('dashboard.admin'),
            'faculty' => view('dashboard.faculty'),
            'student' => view('dashboard.student'),
            default   => abort(403, 'Unknown role.'),
        };
    }
}