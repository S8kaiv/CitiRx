<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminActionRequest;
use App\Http\Requests\Admin\IndexUserRequest;
use App\Http\Requests\Admin\RestoreUserRequest;
use App\Http\Requests\Admin\UpdateUserAssignmentRequest;
use App\Models\AdminAuditLog;
use App\Models\Cohort;
use App\Models\User;
use App\Services\AdminAuditService;
use App\Services\AdminUserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
                'assign' => false,
            ],
            $request->validated(),
        );

        $filters['status'] ??= 'active';

        if ((bool) $filters['assign']) {
            $filters['role'] = 'student';
            $filters['status'] = 'active';
        }

        $query = match ($filters['status']) {
            'deleted' => User::onlyTrashed(),
            'all' => User::withTrashed(),
            default => User::query(),
        };

        $query->with('cohort')->withExists(['assessmentSessions as research_locked' => fn ($q) => $q->whereIn('research_phase', ['pre_test', 'post_test'])]);

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

        if ($filters['role'] === 'student') {
            $query
                ->orderByRaw('users.cohort_id IS NULL')
                ->orderBy(
                    Cohort::query()
                        ->select('cohort_name')
                        ->whereColumn('cohorts.cohort_id', 'users.cohort_id')
                        ->limit(1),
                )
                ->orderBy('users.cohort_id');
        }

        return view('admin.users.index', [
            'users' => $query
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->orderBy('user_id')
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

    public function verifyEmail(
        AdminActionRequest $request,
        string $userId,
        AdminAuditService $audit,
    ): RedirectResponse {
        $data = $request->validate([
            'reviewed_email' => ['required', 'string', 'max:255'],
        ]);

        $verified = DB::transaction(function () use ($request, $userId, $data, $audit): bool {
            $student = User::query()
                ->where('role', 'student')
                ->lockForUpdate()
                ->findOrFail($userId);

            if ($student->email !== $data['reviewed_email']) {
                return false;
            }

            if (! $student->hasVerifiedEmail()) {
                $student->markEmailAsVerified();

                $audit->record($request->user(), 'user.email_verified', $student, [
                    'email_verified_at' => [
                        'old' => null,
                        'new' => $student->email_verified_at->toDateTimeString(),
                    ],
                ], $request->validated('reason'));
            }

            return true;
        });

        return redirect()->route('admin.users.edit', ['userId' => $userId])->with(
            $verified ? 'status' : 'error',
            $verified ? 'Student email verified.' : 'The email changed. Review the current address before verifying.',
        );
    }

    public function assignCohort(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'student_ids' => ['required', 'array', 'min:1', 'max:100'],
            'student_ids.*' => ['required', 'uuid', 'distinct'],
            'cohort_id' => ['required', 'uuid', 'exists:cohorts,cohort_id'],
            'original_cohorts' => ['required', 'array'], 'original_cohorts.*' => ['nullable', 'uuid'],
        ]);
        $count = $this->users->assignCohort($request->user(), $data['student_ids'], $data['cohort_id'], $data['original_cohorts']);

        return back()->with('status', "{$count} students moved to the selected cohort.");
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
        RestoreUserRequest $request,
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
