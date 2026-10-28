<?php

namespace App\Services;

use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class AdminAuditService
{
    /**
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     */
    public function record(
        User $administrator,
        string $action,
        Model $target,
        array $changes = [],
        ?string $reason = null,
    ): AdminAuditLog {
        if ($administrator->role !== 'admin') {
            throw new LogicException(
                'Only administrators can create administrative audit records.',
            );
        }

        return AdminAuditLog::query()->create([
            'admin_id' => $administrator->user_id,
            'actor_name' => $administrator->fullName(),
            'action' => $action,
            'target_type' => $target::class,
            'target_id' => (string) $target->getKey(),
            'changes' => $changes !== [] ? $changes : null,
            'reason' => filled($reason)
                ? trim((string) $reason)
                : null,
        ]);
    }
}
