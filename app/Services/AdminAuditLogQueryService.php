<?php

namespace App\Services;

use App\Models\AdminAuditLog;
use App\Models\Cohort;
use App\Models\MicroPearl;
use App\Models\PilotControl;
use App\Models\Question;
use App\Models\ResearchFormVersion;
use App\Models\ResearchParticipant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AdminAuditLogQueryService
{
    public const TARGET_TYPES = [
        User::class => 'User',
        Cohort::class => 'Cohort',
        Question::class => 'Question',
        ResearchFormVersion::class => 'Research form version',
        ResearchParticipant::class => 'Research participant',
        PilotControl::class => 'Pilot control',
        MicroPearl::class => 'Micro Pearl',
    ];

    /**
     * @param  array<string, mixed>  $filters
     */
    public function filtered(array $filters): Builder
    {
        return AdminAuditLog::query()
            ->when(
                filled($filters['action'] ?? null),
                fn (Builder $query): Builder => $query->where(
                    'action',
                    $filters['action'],
                ),
            )
            ->when(
                filled($filters['admin_id'] ?? null),
                fn (Builder $query): Builder => $query->where(
                    'admin_id',
                    $filters['admin_id'],
                ),
            )
            ->when(
                filled($filters['target_type'] ?? null),
                fn (Builder $query): Builder => $query->where(
                    'target_type',
                    $filters['target_type'],
                ),
            )
            ->when(
                filled($filters['target_id'] ?? null),
                fn (Builder $query): Builder => $query->where(
                    'target_id',
                    $filters['target_id'],
                ),
            )
            ->when(
                filled($filters['date_from'] ?? null),
                fn (Builder $query): Builder => $query->where(
                    'created_at',
                    '>=',
                    Carbon::createFromFormat(
                        'Y-m-d',
                        $filters['date_from'],
                    )->startOfDay(),
                ),
            )
            ->when(
                filled($filters['date_to'] ?? null),
                fn (Builder $query): Builder => $query->where(
                    'created_at',
                    '<=',
                    Carbon::createFromFormat(
                        'Y-m-d',
                        $filters['date_to'],
                    )->endOfDay(),
                ),
            );
    }

    public function actions(): Collection
    {
        return AdminAuditLog::query()
            ->distinct()
            ->orderBy('action')
            ->pluck('action');
    }

    public function administrators(): Collection
    {
        return User::withTrashed()
            ->whereIn(
                'user_id',
                AdminAuditLog::query()
                    ->select('admin_id')
                    ->distinct(),
            )
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get([
                'user_id',
                'first_name',
                'middle_name',
                'last_name',
                'deleted_at',
            ]);
    }

    public function targetLabel(string $targetType): string
    {
        return self::TARGET_TYPES[$targetType]
            ?? class_basename($targetType);
    }

    /**
     * @param  Collection<int, AdminAuditLog>  $logs
     * @return array<string, string>
     */
    public function targetLinks(Collection $logs): array
    {
        $links = [];

        $this->mapModelLinks(
            $logs,
            User::class,
            User::withTrashed(),
            'user_id',
            'admin.users.edit',
            'userId',
            $links,
        );

        $this->mapModelLinks(
            $logs,
            Cohort::class,
            Cohort::query(),
            'cohort_id',
            'admin.cohorts.show',
            'cohort',
            $links,
        );

        $this->mapModelLinks(
            $logs,
            Question::class,
            Question::query(),
            'question_id',
            'admin.questions.show',
            'question',
            $links,
        );

        $this->mapModelLinks(
            $logs,
            MicroPearl::class,
            MicroPearl::query(),
            'pearl_id',
            'content.pearls.edit',
            'pearl',
            $links,
        );

        $participantLogs = $logs->where(
            'target_type',
            ResearchParticipant::class,
        );

        if ($participantLogs->isNotEmpty()) {
            $participants = ResearchParticipant::query()
                ->whereIn(
                    'research_participant_id',
                    $participantLogs->pluck('target_id'),
                )
                ->pluck(
                    'user_id',
                    'research_participant_id',
                );

            foreach ($participantLogs as $log) {
                $userId = $participants->get($log->target_id);

                if ($userId !== null) {
                    $links[$log->log_id] = route(
                        'admin.research-participants.edit',
                        ['userId' => $userId],
                    );
                }
            }
        }

        $this->mapStaticLinks(
            $logs,
            ResearchFormVersion::class,
            ResearchFormVersion::query(),
            'form_version_id',
            route('admin.research-forms.index'),
            $links,
        );

        $this->mapStaticLinks(
            $logs,
            PilotControl::class,
            PilotControl::query(),
            'pilot_control_id',
            route('admin.pilot.index'),
            $links,
        );

        return $links;
    }

    public function safeCsvCell(mixed $value): string
    {
        $cell = (string) ($value ?? '');

        if (preg_match('/^\s*[=+\-@]/u', $cell) === 1) {
            return "'{$cell}";
        }

        return $cell;
    }

    /**
     * @param  array<string, string>  $links
     */
    private function mapModelLinks(
        Collection $logs,
        string $targetType,
        Builder $query,
        string $keyName,
        string $routeName,
        string $routeParameter,
        array &$links,
    ): void {
        $matchingLogs = $logs->where(
            'target_type',
            $targetType,
        );

        if ($matchingLogs->isEmpty()) {
            return;
        }

        $existingIds = $query
            ->whereIn(
                $keyName,
                $matchingLogs->pluck('target_id'),
            )
            ->pluck($keyName)
            ->flip();

        foreach ($matchingLogs as $log) {
            if ($existingIds->has($log->target_id)) {
                $links[$log->log_id] = route(
                    $routeName,
                    [
                        $routeParameter => $log->target_id,
                    ],
                );
            }
        }
    }

    /**
     * @param  array<string, string>  $links
     */
    private function mapStaticLinks(
        Collection $logs,
        string $targetType,
        Builder $query,
        string $keyName,
        string $url,
        array &$links,
    ): void {
        $matchingLogs = $logs->where(
            'target_type',
            $targetType,
        );

        if ($matchingLogs->isEmpty()) {
            return;
        }

        $existingIds = $query
            ->whereIn(
                $keyName,
                $matchingLogs->pluck('target_id'),
            )
            ->pluck($keyName)
            ->flip();

        foreach ($matchingLogs as $log) {
            if ($existingIds->has($log->target_id)) {
                $links[$log->log_id] = $url;
            }
        }
    }
}
