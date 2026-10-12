<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view(
            'auth.register',
        );
    }

    public function store(
        RegisterUserRequest $request,
    ): RedirectResponse {
        $validated = $request->validated();

        $user = User::query()->create([
            'first_name' => $validated[
                'first_name'
            ],

            'middle_name' => $validated[
                'middle_name'
            ] ?? null,

            'last_name' => $validated[
                'last_name'
            ],

            'email' => $validated[
                'email'
            ],

            'password' => $validated[
                'password'
            ],
        ]);

        /*
         * Because User now implements MustVerifyEmail, this event
         * sends Laravel's verification notification.
         */
        event(
            new Registered($user),
        );

        Auth::login($user);

        return redirect()->route(
            'verification.notice',
        );
    }
}
