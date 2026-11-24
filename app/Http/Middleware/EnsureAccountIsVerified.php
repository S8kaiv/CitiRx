<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;

class EnsureAccountIsVerified extends EnsureEmailIsVerified
{
    public function handle($request, Closure $next, $redirectToRoute = null)
    {
        $user = $request->user();

        if (
            $user?->role === 'student'
            && $user->student_approval_pending
            && $user->hasVerifiedEmail()
        ) {
            if ($request->expectsJson()) {
                abort(403, 'Your student account is awaiting staff approval.');
            }

            return redirect()->guest(route($redirectToRoute ?: 'verification.notice'));
        }

        return parent::handle($request, $next, $redirectToRoute);
    }
}
