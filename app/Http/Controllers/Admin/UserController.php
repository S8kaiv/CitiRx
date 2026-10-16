<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminActionRequest;
use App\Http\Requests\Admin\IndexUserRequest;
use App\Http\Requests\Admin\UpdateUserAssignmentRequest;
use App\Models\AdminAuditLog;
use App\Models\Cohort;
use App\Models\User;
use App\Services\AdminUserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use LogicException;

class UserController extends Controller
{
    public function __construct(
        protected AdminUserService $users,
    ) {}

    public function index(
        IndexUserRequest $request,
    ): View {
        $filters = array_merge(
            [
                'q' => null,
                'role' => null,
                'cohort_id' => null,
                'verification' => null,
                'status' => 'active',
                'no_cohort' => false,
            ],
            $request->validated(),
        );

        $query = match ($filters['status']) {
            'deleted' => User::onlyTrashed(),
            'all' => User::withTrashed(),
            default => User::query(),
        };

        $query->with('cohort');

        if ($filters['role'] !== null) {
            $query->where(
                'role',
                $filters['role'],
            );
        }

        if ($filters['cohort_id'] !== null) {
            $query->where(
                'cohort_id',
                $filters['cohort_id'],
            );
        }

        if ($filters['verification'] === 'verified') {
            $query->whereNotNull('email_verified_at');
        }

        if ($filters['verification'] === 'unverified') {
            $query->whereNull('email_verified_at');
        }

        if ((bool) $filters['no_cohort']) {
            $query
                ->whereNull('cohort_id')
                ->whereIn(
                    'role',
                    [
                        'student',
                        'faculty',
                    ],
                );
        }

        if (filled($filters['q'])) {
            $search = addcslashes(
                $filters['q'],
                '\\%_',
            );

            $query->where(
                function ($query) use ($search): void {
                    $query
                        ->where(
                            'first_name',
                            'like',
                            "%{$search}%",
                        )
                        ->orWhere(
                            'last_name',
                            'like',
                            "%{$search}%",
                        )
                        ->orWhere(
                            'email',
                            'like',
                            "%{$search}%",
                        )
                        ->orWhere(
                            'student_id',
                            'like',
                            "%{$search}%",
                        );
                },
            );
        }

        return view('admin.users.index', [
            'users' => $query
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->paginate(30)
                ->withQueryString(),

            'cohorts' => Cohort::query()
                ->orderByRaw('archived_at IS NOT NULL')
                ->orderBy('cohort_name')
                ->get(),

            'filters' => $filters,
        ]);
    }

    public function edit(
        string $userId,
    ): View {
        $managedUser = User::withTrashed()
            ->with('cohort')
            ->withCount([
                'assessmentSessions',
                'facultyInterventionsAssigned',
            ])
            ->findOrFail($userId);

        return view('admin.users.edit', [
            'managedUser' => $managedUser,

            'cohorts' => Cohort::query()
                ->active()
                ->orderBy('cohort_name')
                ->get(),

            'recentAudits' => AdminAuditLog::query()
                ->where('target_type', User::class)
                ->where(
                    'target_id',
                    $managedUser->user_id,
                )
                ->with('admin')
                ->latest('created_at')
                ->limit(20)
                ->get(),
        ]);
    }

    public function update(
        UpdateUserAssignmentRequest $request,
        string $userId,
    ): RedirectResponse {
        try {
            $changed = $this->users->updateAssignment(
                $request->user(),
                $userId,
                $request->safe()->only([
                    'role',
                    'cohort_id',
                ]),
                $request->validated('reason'),
            );
        } catch (LogicException $exception) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    $exception->getMessage(),
                );
        }

        return back()->with(
            'status',
            $changed
                ? 'User assignment updated.'
                : 'No changes were required.',
        );
    }

    public function destroy(
        AdminActionRequest $request,
        string $userId,
    ): RedirectResponse {
        try {
            $this->users->softDelete(
                $request->user(),
                $userId,
                $request->validated('reason'),
            );
        } catch (LogicException $exception) {
            return back()->with(
                'error',
                $exception->getMessage(),
            );
        }

        return redirect()
            ->route('admin.users.index')
            ->with(
                'status',
                'User account soft-deleted.',
            );
    }

    public function restore(
        AdminActionRequest $request,
        string $userId,
    ): RedirectResponse {
        $this->users->restore(
            $request->user(),
            $userId,
            $request->validated('reason'),
        );

        return redirect()
            ->route(
                'admin.users.edit',
                ['userId' => $userId],
            )
            ->with(
                'status',
                'User account restored.',
            );
    }
}
