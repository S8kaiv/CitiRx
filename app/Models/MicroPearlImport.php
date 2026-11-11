<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MicroPearlImport extends Model
{
    use HasUuids;

    public const STATUS_PREVIEWED = 'previewed';

    public const STATUS_COMMITTED = 'committed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_EXPIRED = 'expired';

    protected $table = 'micro_pearl_imports';

    protected $primaryKey = 'import_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'uploader_id',
        'status',
        'original_filename',
        'storage_path',
        'file_sha256',
        'file_size',
        'row_count',
        'preview_rows',
        'lock_version',
        'reason',
        'failure_message',
        'expires_at',
        'committed_at',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'row_count' => 'integer',
        'preview_rows' => 'array',
        'lock_version' => 'integer',
        'expires_at' => 'datetime',
        'committed_at' => 'datetime',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'uploader_id',
            'user_id',
        )->withTrashed();
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function canCommit(): bool
    {
        return $this->status
            === self::STATUS_PREVIEWED
            && ! $this->isExpired();
    }
}
