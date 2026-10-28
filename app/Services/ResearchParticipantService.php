<?php

namespace App\Services;

use App\Models\ResearchParticipant;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use LogicException;

class ResearchParticipantService
{
    public function __construct(
        protected AdminAuditService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = match ($filters['account_status']) {
            'deleted' => User::onlyTrashed(),
            'all' => User::withTrashed(),
            default => User::query(),
        };

        $query
            ->where('role', 'student')
            ->with([
                'cohort',
                'researchParticipant.changedBy',
            ]);

        if (filled($filters['q'])) {
            $search = addcslashes(
                (string) $filters['q'],
                '\\%_',
            );

            $query->where(
                function (Builder $query) use ($search): void {
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

        if (filled($filters['cohort_id'])) {
            $query->where(
                'cohort_id',
                $filters['cohort_id'],
            );
        }

        if (
            $filters['status']
            === ResearchParticipant::STATUS_UNREGISTERED
        ) {
            $query->doesntHave('researchParticipant');
        } elseif (filled($filters['status'])) {
            $query->whereHas(
                'researchParticipant',
                fn (Builder $query): Builder => $query->where(
                    'status',
                    $filters['status'],
                ),
            );
        }

        return $query
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(30)
            ->withQueryString();
    }

    /**
     * @return array<string, int>
     */
    public function statusCounts(): array
    {
        $counts = array_fill_keys(
            [
                ...ResearchParticipant::STATUSES,
                ResearchParticipant::STATUS_UNREGISTERED,
            ],
            0,
        );

        User::withTrashed()
            ->where('users.role', 'student')
            ->leftJoin(
                'research_participants as rp',
                'rp.user_id',
                '=',
                'users.user_id',
            )
            ->selectRaw(
                "COALESCE(rp.status, 'unregistered') AS participant_status, "
                    .'COUNT(*) AS aggregate',
            )
            ->groupBy('participant_status')
            ->get()
            ->each(function (object $row) use (&$counts): void {
                $counts[(string) $row->participant_status] =
                    (int) $row->aggregate;
            });

        return $counts;
    }

    public function changeStatus(
        User $administrator,
        string $userId,
        string $status,
        int $expectedLockVersion,
        string $reason,
    ): ResearchParticipant {
        $this->assertAdministrator($administrator);

        return DB::transaction(function () use (
            $administrator,
            $userId,
            $status,
            $expectedLockVersion,
            $reason,
        ): ResearchParticipant {
            $student = User::withTrashed()
                ->whereKey($userId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($student->role !== 'student') {
                throw new LogicException(
                    'Only student accounts can receive '
                        .'a research-participant status.',
                );
            }

            if (
                $status === ResearchParticipant::STATUS_INCLUDED
                && $student->cohort_id === null
            ) {
                throw new LogicException(
                    'Assign the student to a cohort before '
                        .'including them in official analysis.',
                );
            }

            if (
                $status === ResearchParticipant::STATUS_INCLUDED
                && $student->email_verified_at === null
            ) {
                throw new LogicException(
                    'Verify the student email before including '
                        .'them in official analysis.',
                );
            }

            $participant = ResearchParticipant::query()
                ->where('user_id', $student->user_id)
                ->lockForUpdate()
                ->first();

            $actualLockVersion = $participant?->lock_version ?? 0;

            if ($actualLockVersion !== $expectedLockVersion) {
                throw new LogicException(
                    'The participant status changed in another '
                        .'browser session. Refresh and try again.',
                );
            }

            $reason = trim($reason);
            $oldStatus = $participant?->status;
            $oldReason = $participant?->reason;

            if (
                $oldStatus === $status
                && $oldReason === $reason
            ) {
                throw new LogicException(
                    'No participant-status change was provided.',
                );
            }

            if ($participant === null) {
                $participant = new ResearchParticipant([
                    'user_id' => $student->user_id,
                    'lock_version' => 1,
                ]);
            } else {
                $participant->lock_version++;
            }

            $participant->forceFill([
                'status' => $status,
                'reason' => $reason,
                'changed_by' => $administrator->user_id,
                'changed_at' => now(),
            ])->save();

            $this->audit->record(
                $administrator,
                'research_participant.status_changed',
                $participant,
                [
                    'status' => [
                        'old' => $oldStatus,
                        'new' => $status,
                    ],
                    'reason' => [
                        'old' => $oldReason,
                        'new' => $reason,
                    ],
                    'lock_version' => [
                        'old' => $actualLockVersion,
                        'new' => $participant->lock_version,
                    ],
                ],
                $reason,
            );

            return $participant->fresh([
                'user',
                'changedBy',
            ]);
        });
    }

    private function assertAdministrator(User $administrator): void
    {
        if ($administrator->role !== 'admin') {
            throw new LogicException(
                'Only administrators may change '
                    .'research-participant status.',
            );
        }
    }
}
