<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class SystemBackupRecord extends Model
{
    use HasUuids;

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    public const STATUSES = [
        self::STATUS_SUCCESS,
        self::STATUS_FAILED,
    ];

    public const UPDATED_AT = null;

    protected $table = 'system_backup_records';

    protected $primaryKey = 'backup_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'status',
        'size_bytes',
        'checksum_sha256',
        'completed_at',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException(
                'Backup status records are immutable.',
            );
        });

        static::deleting(function (): never {
            throw new LogicException(
                'Backup status records are immutable.',
            );
        });
    }

    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where(
            'status',
            self::STATUS_SUCCESS,
        );
    }
}
