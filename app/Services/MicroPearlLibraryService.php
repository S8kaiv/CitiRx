<?php

namespace App\Services;

use App\Models\MicroPearl;
use App\Models\TosDomain;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class MicroPearlLibraryService
{
    /**
     * @param  array{
     *     q?: string|null,
     *     domain_id?: int|string|null,
     *     competency_id?: string|null
     * }  $input
     * @return array{
     *     pearls: LengthAwarePaginator,
     *     domains: Collection,
     *     filters: array{
     *         q: string,
     *         domain_id: int|null,
     *         competency_id: string|null
     *     }
     * }
     */
    public function browse(
        array $input
    ): array {
        $filters = [
            'q' => trim(
                (string) ($input['q'] ?? '')
            ),

            'domain_id' => isset(
                $input['domain_id']
            )
                ? (int) $input['domain_id']
                : null,

            'competency_id' => $input['competency_id']
                ?? null,
        ];

        $query = MicroPearl::query()
            ->select('micro_pearls.*')
            ->join(
                'tos_competencies',
                'tos_competencies.competency_id',
                '=',
                'micro_pearls.competency_id'
            )
            ->join(
                'tos_domains',
                'tos_domains.domain_id',
                '=',
                'tos_competencies.domain_id'
            )
            ->with([
                'competency.domain',
            ])
            ->published();

        if ($filters['domain_id'] !== null) {
            $query->where(
                'tos_domains.domain_id',
                $filters['domain_id']
            );
        }

        if (
            $filters['competency_id']
            !== null
        ) {
            $query->where(
                'micro_pearls.competency_id',
                $filters['competency_id']
            );
        }

        if ($filters['q'] !== '') {
            $like = '%'.$filters['q'].'%';

            $query->where(
                function (
                    Builder $searchQuery
                ) use ($like): void {
                    $searchQuery
                        ->where(
                            'micro_pearls.title',
                            'like',
                            $like
                        )
                        ->orWhere(
                            'micro_pearls.mnemonic_summary',
                            'like',
                            $like
                        )
                        ->orWhere(
                            'micro_pearls.lesson_content',
                            'like',
                            $like
                        );
                }
            );
        }

        $pearls = $query
            ->orderBy(
                'tos_domains.domain_number'
            )
            ->orderBy(
                'tos_competencies.order_index'
            )
            ->orderBy(
                'micro_pearls.order_index'
            )
            ->paginate(12)
            ->withQueryString();

        return [
            'pearls' => $pearls,
            'domains' => $this->availableDomains(),
            'filters' => $filters,
        ];
    }

    private function availableDomains(): Collection
    {
        return TosDomain::query()
            ->with([
                'competencies' => function (
                    HasMany $relation
                ): void {
                    $relation->orderBy(
                        'order_index'
                    );
                },
            ])
            ->orderBy('domain_number')
            ->get();
    }
}
