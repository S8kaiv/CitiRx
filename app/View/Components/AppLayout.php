<?php

namespace App\View\Components;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;
use Illuminate\View\View;

class AppLayout extends Component
{
    public string $surface;

    public function __construct(
        ?string $surface = null,
    ) {
        $user = Auth::user();

        $roleAttribute = $user instanceof User
            ? $user->getAttribute('role')
            : null;

        $role = is_string($roleAttribute)
            ? $roleAttribute
            : null;

        $resolvedSurface = $surface ?? match ($role) {
            'admin' => 'admin',
            'faculty' => 'faculty',
            default => 'student',
        };

        $this->surface = in_array(
            $resolvedSurface,
            [
                'student',
                'faculty',
                'admin',
            ],
            true,
        )
            ? $resolvedSurface
            : 'student';
    }

    public function render(): View
    {
        return view('layouts.app');
    }
}
