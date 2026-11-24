<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AdminAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StudentAccountApprovalController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(in_array($request->user()->role, ['admin', 'dean'], true), 403);

        return view('auth.student-approvals', [
            'students' => User::query()
                ->where('role', 'student')
                ->where('student_approval_pending', true)
                ->whereNotNull('email_verified_at')
                ->oldest('created_at')
                ->orderBy('user_id')
                ->paginate(25),
        ]);
    }

    public function store(
        Request $request,
        string $student,
        AdminAuditService $audit,
    ): RedirectResponse {
        $reviewer = $request->user();
        abort_unless(in_array($reviewer->role, ['admin', 'dean'], true), 403);

        $data = $request->validate([
            'reviewed_email' => ['required', 'string', 'max:255'],
        ]);

        $approved = DB::transaction(function () use ($student, $data, $reviewer, $audit): bool {
            $account = User::query()
                ->where('role', 'student')
                ->lockForUpdate()
                ->findOrFail($student);

            if ($account->email !== $data['reviewed_email'] || ! $account->hasVerifiedEmail()) {
                return false;
            }

            if ($account->student_approval_pending) {
                $account->student_approval_pending = false;
                $account->save();

                $audit->record($reviewer, 'user.student_account_approved', $account, [
                    'student_approval_pending' => ['old' => true, 'new' => false],
                ]);
            }

            return true;
        });

        return redirect()->route('student-approvals.index')->with(
            $approved ? 'status' : 'error',
            $approved
                ? 'Student account approved.'
                : 'The email changed or is awaiting confirmation. Review the account again.',
        );
    }
}
