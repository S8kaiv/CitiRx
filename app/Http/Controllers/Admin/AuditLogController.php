<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexAuditLogRequest;
use App\Services\AdminAuditLogQueryService;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    public function __construct(
        protected AdminAuditLogQueryService $auditLogs,
    ) {}

    public function index(
        IndexAuditLogRequest $request,
    ): View {
        $filters = array_merge(
            [
                'action' => null,
                'admin_id' => null,
                'target_type' => null,
                'target_id' => null,
                'date_from' => null,
                'date_to' => null,
            ],
            $request->validated(),
        );

        $logs = $this->auditLogs
            ->filtered($filters)
            ->with([
                'admin:user_id,first_name,middle_name,last_name,deleted_at',
            ])
            ->latest('created_at')
            ->latest('log_id')
            ->paginate(40)
            ->withQueryString();

        return view(
            'admin.audit-logs.index',
            [
                'logs' => $logs,
                'targetLinks' => $this->auditLogs
                    ->targetLinks($logs->getCollection()),
                'administrators' => $this->auditLogs
                    ->administrators(),
                'actions' => $this->auditLogs->actions(),
                'targetTypes' => AdminAuditLogQueryService::TARGET_TYPES,
                'filters' => $filters,
            ],
        );
    }

    public function export(
        IndexAuditLogRequest $request,
    ): StreamedResponse {
        $filters = $request->validated();

        return response()->streamDownload(
            function () use ($filters): void {
                $output = fopen('php://output', 'wb');

                if ($output === false) {
                    return;
                }

                fwrite($output, "\xEF\xBB\xBF");

                fputcsv(
                    $output,
                    [
                        'timestamp',
                        'administrator',
                        'action',
                        'target_type',
                        'target_id',
                        'changed_fields',
                    ],
                    ',',
                    '"',
                    '',
                );

                $logs = $this->auditLogs
                    ->filtered($filters)
                    ->orderBy('created_at')
                    ->orderBy('log_id')
                    ->cursor();

                foreach ($logs as $log) {
                    $cells = [
                        $log->created_at?->toIso8601String(),
                        $log->actor_name
                            ?? 'Unknown administrator',
                        $log->action,
                        $this->auditLogs->targetLabel(
                            $log->target_type,
                        ),
                        $log->target_id,
                        implode(
                            '|',
                            array_keys($log->changes ?? []),
                        ),
                    ];

                    fputcsv(
                        $output,
                        array_map(
                            $this->auditLogs->safeCsvCell(...),
                            $cells,
                        ),
                        ',',
                        '"',
                        '',
                    );
                }

                fclose($output);
            },
            'administrative-audit-'.now()->format('Ymd-His').'.csv',
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'no-store, private',
            ],
        );
    }
}
