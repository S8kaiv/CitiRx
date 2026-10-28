<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserCanManageMicroPearls
{
    public function handle(
        Request $request,
        Closure $next,
    ): Response {
        abort_unless(
            in_array(
                $request->user()?->role,
                ['admin', 'faculty'],
                true,
            ),
            403,
            'Only administrators and faculty may manage Micro Pearls.',
        );

        return $next($request);
    }
}
