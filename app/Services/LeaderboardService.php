<?php

namespace App\Services;

use App\Models\Cohort;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LeaderboardService
{
    /**
     * Build the cohort-scoped leaderboard for one student.
     *
     * @return array{
     *     cohort: Cohort|null,
     *     rows: Collection<int, array{
     *         user_id: string,
     *         display_name: string,
     *         total_xp: int,
     *         current_level: int|null,
     *         rank: int
     *     }>,
     *     currentRank: int|null,
     *     currentRow: array{
     *         user_id: string,
     *         display_name: string,
     *         total_xp: int,
     *         current_level: int|null,
     *         rank: int
     *     }|null,
     *     totalStudents: int
     * }
     */
    public function forStudent(User $student): array
    {
        $student->loadMissing('cohort');

        $cohort = $student->cohort;

        if ($cohort === null) {
            return $this->emptyResult();
        }

        /*
         * Query only fields required by the view.
         *
         * Unverified accounts are excluded because they have not
         * completed account activation and should not appear to
         * other students.
         */
        $rows = DB::table('users')
            ->where(
                'cohort_id',
                $cohort->cohort_id,
            )
            ->where(
                'role',
                'student',
            )
            ->whereNotNull(
                'email_verified_at',
            )
            ->whereNull(
                'deleted_at',
            )
            ->orderByDesc(
                'total_xp',
            )
            ->orderBy(
                'last_name',
            )
            ->orderBy(
                'first_name',
            )
            ->orderBy(
                'user_id',
            )
            ->get([
                'user_id',
                'first_name',
                'last_name',
                'total_xp',
                'current_level',
            ]);

        $ranked = $this->assignCompetitionRanks(
            $rows,
        );

        $currentRow = $ranked->firstWhere(
            'user_id',
            (string) $student->user_id,
        );

        return [
            'cohort' => $cohort,

            'rows' => $ranked,

            'currentRank' => $currentRow[
                'rank'
            ] ?? null,

            'currentRow' => $currentRow,

            'totalStudents' => $ranked->count(),
        ];
    }

    /**
     * Assign competition ranks:
     *
     * 1, 2, 2, 4
     *
     * Rank depends only on XP. Names are used only to make
     * the display order deterministic within an XP tie.
     *
     * @param  Collection<int, object>  $rows
     * @return Collection<int, array{
     *     user_id: string,
     *     display_name: string,
     *     total_xp: int,
     *     current_level: int|null,
     *     rank: int
     * }>
     */
    private function assignCompetitionRanks(
        Collection $rows,
    ): Collection {
        $previousXp = null;
        $previousRank = 0;

        return $rows
            ->values()
            ->map(
                function (
                    object $row,
                    int $index,
                ) use (
                    &$previousXp,
                    &$previousRank,
                ): array {
                    $xp = (int) $row->total_xp;

                    if (
                        $index === 0
                        || $xp !== $previousXp
                    ) {
                        $previousRank = $index + 1;
                    }

                    $previousXp = $xp;

                    return [
                        'user_id' => (string) $row->user_id,

                        'display_name' => $this->displayName(
                            (string) $row->first_name,
                            (string) $row->last_name,
                        ),

                        'total_xp' => $xp,

                        'current_level' => $row->current_level === null
                                ? null
                                : (int) $row->current_level,

                        'rank' => $previousRank,
                    ];
                },
            );
    }

    private function displayName(
        string $firstName,
        string $lastName,
    ): string {
        $firstName = trim($firstName);
        $lastName = trim($lastName);

        if ($firstName === '') {
            $firstName = 'Student';
        }

        if ($lastName === '') {
            return $firstName;
        }

        return sprintf(
            '%s %s.',
            $firstName,
            mb_strtoupper(
                mb_substr(
                    $lastName,
                    0,
                    1,
                ),
            ),
        );
    }

    /**
     * @return array{
     *     cohort: null,
     *     rows: Collection,
     *     currentRank: null,
     *     currentRow: null,
     *     totalStudents: int
     * }
     */
    private function emptyResult(): array
    {
        return [
            'cohort' => null,
            'rows' => collect(),
            'currentRank' => null,
            'currentRow' => null,
            'totalStudents' => 0,
        ];
    }
}
