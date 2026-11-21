<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\StudentNameRequest;
use App\Services\ReadinessService;
use App\Services\StudentNameService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(
        protected ReadinessService $readiness,
        protected StudentNameService $names,
    ) {}

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();
        $isStudent = $user->role === 'student';

        $badgesCount = 0;
        $readinessBand = null;

        if ($isStudent) {
            $user->loadMissing([
                'cohort',
                'level.tier',
            ]);

            $badgesCount = $user
                ->badges()
                ->count();

            $readinessBand =
                $user->predicted_readiness_pct !== null
                ? $this->readiness->band(
                    (float) $user->predicted_readiness_pct
                )
                : null;
        }

        return view('profile.edit', [
            'user' => $user,
            'isStudent' => $isStudent,
            'badgesCount' => $badgesCount,
            'readinessBand' => $readinessBand,
            'nameRequest' => $isStudent ? StudentNameRequest::query()->where('user_id', $user->user_id)->latest()->first() : null,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $data = $request->validated();
        if ($request->user()->role === 'student') {
            $this->names->request($request->user(), $data);
            $data = Arr::except($data, StudentNameService::FIELDS);
        }
        $request->user()->fill($data);

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        DB::transaction(function () use ($user): void {
            $user->forceFill([
                'first_name' => 'Deleted',
                'middle_name' => null,
                'last_name' => 'User',
                'student_id' => null,
                'email' => "deleted-{$user->user_id}@deleted.invalid",
                'email_verified_at' => null,
                'password' => Str::random(64),
                'remember_token' => null,
            ])->saveQuietly();

            $user->delete();
        });

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::route('login');
    }
}
