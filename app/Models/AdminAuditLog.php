<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminAuditLog extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected $table = 'admin_audit_logs';

    protected $primaryKey = 'log_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'admin_id',
        'action',
        'target_type',
        'target_id',
        'changes',
        'reason',
    ];

    protected $casts = [
        'changes' => 'array',
        'created_at' => 'datetime',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'admin_id',
            'user_id',
        )->withTrashed();
    }
}
