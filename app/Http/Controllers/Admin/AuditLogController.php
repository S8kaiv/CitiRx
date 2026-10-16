<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexAuditLogRequest;
use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(
        IndexAuditLogRequest $request,
    ): View {
        $filters = array_merge(
            [
                'action' => null,
                'admin_id' => null,
                'target_type' => null,
            ],
            $request->validated(),
        );

        $query = AdminAuditLog::query()
            ->with('admin');

        foreach (
            [
                'action',
                'admin_id',
                'target_type',
            ] as $field
        ) {
            if ($filters[$field] !== null) {
                $query->where(
                    $field,
                    $filters[$field],
                );
            }
        }

        return view('admin.audit-logs.index', [
            'logs' => $query
                ->latest('created_at')
                ->paginate(40)
                ->withQueryString(),

            /*
             * Use audit records instead of current roles.
             * A former administrator remains available in
             * historical audit filters after role changes.
             */
            'administrators' => User::withTrashed()
                ->whereIn(
                    'user_id',
                    AdminAuditLog::query()
                        ->select('admin_id')
                        ->distinct(),
                )
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(),

            'actions' => AdminAuditLog::query()
                ->distinct()
                ->orderBy('action')
                ->pluck('action'),

            'filters' => $filters,
        ]);
    }
}
