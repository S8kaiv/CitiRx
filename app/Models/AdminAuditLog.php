<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

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
        'actor_name',
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

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException(
                'Administrative audit records are immutable.',
            );
        });

        static::deleting(function (): never {
            throw new LogicException(
                'Administrative audit records are immutable.',
            );
        });
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'admin_id',
            'user_id',
        )->withTrashed();
    }

    public function actorDisplayName(): string
    {
        return filled($this->actor_name)
            ? $this->actor_name
            : ($this->admin?->fullName()
                ?? 'Unknown administrator');
    }
}
