<?php

namespace App\Services;

use App\Models\Cohort;
use App\Models\Question;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use LogicException;

class AdminCohortService
{
    public function __construct(
        protected AdminAuditService $audit,
        protected ResearchFormService $researchForms,
    ) {}

    public function create(
        User $administrator,
        array $data,
    ): Cohort {
        return DB::transaction(function () use (
            $administrator,
            $data,
        ): Cohort {
            $cohort = Cohort::query()->create($data);

            $this->audit->record(
                $administrator,
                'cohort.created',
                $cohort,
                [
                    'cohort_name' => [
                        'old' => null,
                        'new' => $cohort->cohort_name,
                    ],
                    'academic_year' => [
                        'old' => null,
                        'new' => $cohort->academic_year,
                    ],
                ],
            );

            return $cohort;
        });
    }

    public function update(
        User $administrator,
        Cohort $cohort,
        array $data,
    ): bool {
        return DB::transaction(function () use (
            $administrator,
            $cohort,
            $data,
        ): bool {
            $cohort = Cohort::query()
                ->lockForUpdate()
                ->findOrFail($cohort->cohort_id);

            if ($cohort->archived_at !== null) {
                throw new LogicException(
                    'Unarchive this cohort before editing it.',
                );
            }

            $changes = [];

            foreach ($data as $field => $newValue) {
                $oldValue = $cohort->getRawOriginal($field);

                if ((string) $oldValue !== (string) $newValue) {
                    $changes[$field] = [
                        'old' => $oldValue,
                        'new' => $newValue,
                    ];
                }
            }

            if ($changes === []) {
                return false;
            }

            $cohort->update($data);

            $this->audit->record(
                $administrator,
                'cohort.updated',
                $cohort,
                $changes,
            );

            return true;
        });
    }

    public function updatePostTestWindow(
        User $administrator,
        Cohort $cohort,
        array $data,
        string $reason,
    ): void {
        DB::transaction(function () use (
            $administrator,
            $cohort,
            $data,
            $reason,
        ): void {
            $cohort = Cohort::query()
                ->lockForUpdate()
                ->findOrFail($cohort->cohort_id);

            if ($cohort->archived_at !== null) {
                throw new LogicException(
                    'An archived cohort cannot open a post-test window.',
                );
            }

            $clearWindow = (bool) (
                $data['clear_window'] ?? false
            );

            if ($clearWindow) {
                $opensAt = null;
                $closesAt = null;
            } else {
                /*
                 * This checks the complete Form B blueprint.
                 * In production, ResearchFormService also requires
                 * formally validated questions.
                 */
                $this->researchForms->load(
                    Question::FORM_POST_TEST_B,
                );

                $opensAt = CarbonImmutable::parse(
                    $data['post_test_opens_at'],
                );

                $closesAt = CarbonImmutable::parse(
                    $data['post_test_closes_at'],
                );
            }

            $changes = [
                'post_test_opens_at' => [
                    'old' => $cohort
                        ->post_test_opens_at
                        ?->toDateTimeString(),
                    'new' => $opensAt?->toDateTimeString(),
                ],
                'post_test_closes_at' => [
                    'old' => $cohort
                        ->post_test_closes_at
                        ?->toDateTimeString(),
                    'new' => $closesAt?->toDateTimeString(),
                ],
            ];

            $cohort->forceFill([
                'post_test_opens_at' => $opensAt,
                'post_test_closes_at' => $closesAt,
            ])->save();

            $this->audit->record(
                $administrator,
                $clearWindow
                    ? 'cohort.post_test_window_cleared'
                    : 'cohort.post_test_window_updated',
                $cohort,
                $changes,
                $reason,
            );
        });
    }

    public function archive(
        User $administrator,
        Cohort $cohort,
        string $reason,
    ): void {
        DB::transaction(function () use (
            $administrator,
            $cohort,
            $reason,
        ): void {
            $cohort = Cohort::query()
                ->lockForUpdate()
                ->findOrFail($cohort->cohort_id);

            if ($cohort->archived_at !== null) {
                throw new LogicException(
                    'This cohort is already archived.',
                );
            }

            $status = $cohort->postTestWindowStatus();
            $archivedAt = now();

            $changes = [
                'archived_at' => [
                    'old' => null,
                    'new' => $archivedAt->toDateTimeString(),
                ],
            ];

            /*
             * Preserve an ended window for history.
             * Cancel a future schedule and close an open window.
             */
            if ($status === 'scheduled') {
                $changes['post_test_opens_at'] = [
                    'old' => $cohort
                        ->post_test_opens_at
                        ?->toDateTimeString(),
                    'new' => null,
                ];

                $changes['post_test_closes_at'] = [
                    'old' => $cohort
                        ->post_test_closes_at
                        ?->toDateTimeString(),
                    'new' => null,
                ];

                $cohort->post_test_opens_at = null;
                $cohort->post_test_closes_at = null;
            } elseif ($status === 'open') {
                $changes['post_test_closes_at'] = [
                    'old' => $cohort
                        ->post_test_closes_at
                        ?->toDateTimeString(),
                    'new' => $archivedAt->toDateTimeString(),
                ];

                $cohort->post_test_closes_at = $archivedAt;
            }

            /*
             * Keep users assigned to preserve cohort-level
             * assessment and analytics history.
             */
            $cohort->archived_at = $archivedAt;
            $cohort->save();

            $this->audit->record(
                $administrator,
                'cohort.archived',
                $cohort,
                $changes,
                $reason,
            );
        });
    }

    public function unarchive(
        User $administrator,
        Cohort $cohort,
        string $reason,
    ): void {
        DB::transaction(function () use (
            $administrator,
            $cohort,
            $reason,
        ): void {
            $cohort = Cohort::query()
                ->lockForUpdate()
                ->findOrFail($cohort->cohort_id);

            if ($cohort->archived_at === null) {
                throw new LogicException(
                    'This cohort is not archived.',
                );
            }

            $oldArchivedAt = $cohort
                ->archived_at
                ->toDateTimeString();

            $cohort->archived_at = null;
            $cohort->save();

            /*
             * Unarchiving deliberately does not reopen or
             * recreate a post-test window.
             */
            $this->audit->record(
                $administrator,
                'cohort.unarchived',
                $cohort,
                [
                    'archived_at' => [
                        'old' => $oldArchivedAt,
                        'new' => null,
                    ],
                ],
                $reason,
            );
        });
    }
}
