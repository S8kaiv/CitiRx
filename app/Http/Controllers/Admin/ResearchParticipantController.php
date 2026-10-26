<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexResearchParticipantRequest;
use App\Http\Requests\Admin\UpdateResearchParticipantRequest;
use App\Models\AdminAuditLog;
use App\Models\Cohort;
use App\Models\ResearchParticipant;
use App\Models\User;
use App\Services\ResearchParticipantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use LogicException;

class ResearchParticipantController extends Controller
{
    public function __construct(
        protected ResearchParticipantService $participants,
    ) {}

    public function index(
        IndexResearchParticipantRequest $request,
    ): View {
        $filters = array_merge([
            'q' => null,
            'status' => null,
            'cohort_id' => null,
            'account_status' => 'active',
        ], $request->validated());

        return view('admin.research-participants.index', [
            'participants' => $this->participants->paginate(
                $filters,
            ),
            'statusCounts' => $this->participants
                ->statusCounts(),
            'statuses' => ResearchParticipant::STATUSES,
            'cohorts' => Cohort::query()
                ->orderByRaw('archived_at IS NOT NULL')
                ->orderBy('cohort_name')
                ->get(),
            'filters' => $filters,
        ]);
    }

    public function edit(string $userId): View
    {
        $student = User::withTrashed()
            ->where('role', 'student')
            ->with([
                'cohort',
                'researchParticipant.changedBy',
            ])
            ->findOrFail($userId);

        $participant = $student->researchParticipant;

        return view('admin.research-participants.edit', [
            'student' => $student,
            'participant' => $participant,
            'statuses' => ResearchParticipant::STATUSES,
            'recentAudits' => $participant === null
                ? collect()
                : AdminAuditLog::query()
                    ->where(
                        'target_type',
                        ResearchParticipant::class,
                    )
                    ->where(
                        'target_id',
                        $participant->research_participant_id,
                    )
                    ->with('admin')
                    ->latest('created_at')
                    ->limit(20)
                    ->get(),
        ]);
    }

    public function update(
        UpdateResearchParticipantRequest $request,
        string $userId,
    ): RedirectResponse {
        try {
            $this->participants->changeStatus(
                administrator: $request->user(),
                userId: $userId,
                status: $request->validated('status'),
                expectedLockVersion: (int) $request->validated(
                    'expected_lock_version',
                ),
                reason: $request->validated('reason'),
            );
        } catch (LogicException $exception) {
            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route(
                'admin.research-participants.edit',
                ['userId' => $userId],
            )
            ->with(
                'status',
                'Research-participant status updated and audited.',
            );
    }
}
