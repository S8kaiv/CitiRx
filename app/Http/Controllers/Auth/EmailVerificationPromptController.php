<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationPromptController extends Controller
{
    /**
     * Display the email verification prompt.
     */
    public function __invoke(Request $request): RedirectResponse|View
    {
        $user = $request->user();

        if (! $user->hasVerifiedEmail()) {
            return view('auth.verify-email');

        }

        if ($user->role === 'student' && $user->student_approval_pending) {
            return view('auth.student-approval-pending');
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
