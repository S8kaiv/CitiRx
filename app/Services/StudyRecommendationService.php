<?php

namespace App\Services;

use App\Models\MicroPearl;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

final class StudyRecommendationService
{
    public const ENGINE_VERSION = 'rules-v1';

    public const MAX_PRIORITY_COMPETENCIES = 3;

    public const MAX_RECOMMENDED_PEARLS = 3;

    public const DEFAULT_PRACTICE_LENGTH = 10;

    public function __construct(
        protected ReadinessService $readiness,
    ) {}

    /**
     * Build a deterministic recommendation from the student's
     * current BKT knowledge states and weighted readiness breakdown.
     *
     * @param  array<string, mixed>|null  $breakdown
     * @return array<string, mixed>
     */
    public function forUser(
        User $student,
        ?array $breakdown = null,
    ): array {
        if (! $student->is_diagnostic_completed) {
            return $this->emptyRecommendation(
                reason: 'Complete the diagnostic test to unlock your personalized study plan.',
                requiresDiagnostic: true,
            );
        }

        try {
            $breakdown ??=
                $this->readiness->breakdown(
                    $student,
                );
        } catch (RuntimeException $exception) {
            report($exception);

            return $this->emptyRecommendation(
                reason: 'Your personalized study plan is temporarily unavailable because your competency data is incomplete.',
            );
        }

        $domains = collect(
            $breakdown['domains'] ?? [],
        );

        if ($domains->isEmpty()) {
            return $this->emptyRecommendation(
                reason: 'Your personalized study plan is temporarily unavailable because no competency estimates were found.',
            );
        }

        /*
         * Domain priority is based on the same weighted domain
         * mastery used by ReadinessService.
         *
         * Domain number provides deterministic tie-breaking.
         */
        $priorityDomainRow = $domains
            ->sort(
                function (
                    array $left,
                    array $right,
                ): int {
                    $masteryComparison =
                        (float) $left['mastery']
                        <=>
                        (float) $right['mastery'];

                    if ($masteryComparison !== 0) {
                        return $masteryComparison;
                    }

                    return (int) $left['domain_number']
                        <=>
                        (int) $right['domain_number'];
                },
            )
            ->first();

        if (! is_array($priorityDomainRow)) {
            return $this->emptyRecommendation(
                reason: 'Your personalized study plan could not be calculated.',
            );
        }

        $priorityCompetencies = collect(
            $priorityDomainRow['competencies'] ?? [],
        )
            ->sort(
                function (
                    array $left,
                    array $right,
                ): int {
                    $masteryComparison =
                        (float) $left['mastery']
                        <=>
                        (float) $right['mastery'];

                    if ($masteryComparison !== 0) {
                        return $masteryComparison;
                    }

                    return (int) $left['order_index']
                        <=>
                        (int) $right['order_index'];
                },
            )
            ->take(
                self::MAX_PRIORITY_COMPETENCIES,
            )
            ->map(
                fn (array $competency): array => [
                    'competency_id' => $competency['competency_id'],

                    'code' => $competency['code'],

                    'title' => $competency['title'],

                    'domain_id' => (int) $priorityDomainRow['domain_id'],

                    'domain_name' => $priorityDomainRow['domain_name'],

                    'mastery_pct' => round(
                        (float) $competency['mastery'] * 100,
                        1,
                    ),
                ],
            )
            ->values()
            ->all();

        $domainMastery =
            (float) $priorityDomainRow['mastery'];

        $mode = $domainMastery
            >= BktService::MASTERY_THRESHOLD
            ? 'maintenance'
            : 'remediation';

        $priorityDomain = [
            'domain_id' => (int) $priorityDomainRow['domain_id'],

            'domain_number' => (int) $priorityDomainRow['domain_number'],

            'domain_name' => $priorityDomainRow['domain_name'],

            'mastery_pct' => round(
                $domainMastery * 100,
                1,
            ),
        ];

        $competencyIds = array_column(
            $priorityCompetencies,
            'competency_id',
        );

        return [
            'status' => 'ready',

            'has_data' => true,

            'requires_diagnostic' => false,

            'mode' => $mode,

            'readiness_pct' => round(
                (float) ($breakdown['total'] ?? 0),
                2,
            ),

            'priority_domain' => $priorityDomain,

            'priority_competencies' => $priorityCompetencies,

            'recommended_action' => $this->practiceAction(
                $priorityDomain,
                $mode,
            ),

            'recommended_pearls' => $this->publishedPearlsForCompetencies(
                $competencyIds,
            ),

            'reason' => $this->reason(
                $priorityDomain,
                $mode,
            ),

            'engine_version' => self::ENGINE_VERSION,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyRecommendation(
        string $reason,
        bool $requiresDiagnostic = false,
    ): array {
        return [
            'status' => $requiresDiagnostic
                ? 'diagnostic_required'
                : 'unavailable',

            'has_data' => false,

            'requires_diagnostic' => $requiresDiagnostic,

            'mode' => null,

            'readiness_pct' => null,

            'priority_domain' => null,

            'priority_competencies' => [],

            'recommended_action' => null,

            'recommended_pearls' => [],

            'reason' => $reason,

            'engine_version' => self::ENGINE_VERSION,
        ];
    }

    /**
     * @param  array<string, mixed>  $priorityDomain
     * @return array<string, mixed>|null
     */
    private function practiceAction(
        array $priorityDomain,
        string $mode,
    ): ?array {
        $domainId =
            (int) $priorityDomain['domain_id'];

        $hasPracticeQuestions = Question::query()
            ->where(
                'is_active',
                true,
            )
            ->whereNull(
                'research_form',
            )
            ->whereHas(
                'competency',
                fn (Builder $query) => $query->where(
                    'domain_id',
                    $domainId,
                ),
            )
            ->exists();

        if (! $hasPracticeQuestions) {
            return null;
        }

        $sessionType = $mode === 'maintenance'
            ? 'maintenance'
            : 'targeted';

        return [
            'type' => 'practice',

            'domain_id' => $domainId,

            'suggested_length' => self::DEFAULT_PRACTICE_LENGTH,

            'message' => sprintf(
                'Set up a %d-question %s practice session in %s.',
                self::DEFAULT_PRACTICE_LENGTH,
                $sessionType,
                $priorityDomain['domain_name'],
            ),
        ];
    }

    /**
     * @param  array<int, string>  $competencyIds
     * @return array<int, array<string, mixed>>
     */
    private function publishedPearlsForCompetencies(
        array $competencyIds,
    ): array {
        if ($competencyIds === []) {
            return [];
        }

        return MicroPearl::query()
            ->published()
            ->whereIn(
                'competency_id',
                $competencyIds,
            )
            ->with(
                'competency:competency_id,title',
            )
            ->orderBy(
                'order_index',
            )
            ->orderBy(
                'title',
            )
            ->limit(
                self::MAX_RECOMMENDED_PEARLS,
            )
            ->get()
            ->map(
                fn (MicroPearl $pearl): array => [
                    'slug' => $pearl->slug,

                    'title' => $pearl->title,

                    'competency_title' => $pearl->competency->title,

                    'estimated_read_seconds' => (int) $pearl
                        ->estimated_read_seconds,
                ],
            )
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $priorityDomain
     */
    private function reason(
        array $priorityDomain,
        string $mode,
    ): string {
        if ($mode === 'maintenance') {
            return sprintf(
                'All tracked domains are at or above the %.0f%% BKT mastery threshold. %s is currently your lowest domain and is recommended for maintenance review.',
                BktService::MASTERY_THRESHOLD * 100,
                $priorityDomain['domain_name'],
            );
        }

        return sprintf(
            '%s currently has your lowest weighted mastery estimate at %.1f%%.',
            $priorityDomain['domain_name'],
            $priorityDomain['mastery_pct'],
        );
    }
}
