<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResearchFormImport extends Model
{
    use HasUuids;

    public const TYPE_CONTENT = 'content';

    public const TYPE_VALIDATION = 'validation';

    public const TYPES = [
        self::TYPE_CONTENT,
        self::TYPE_VALIDATION,
    ];

    public const STATUS_PREVIEWED = 'previewed';

    public const STATUS_COMMITTED = 'committed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_EXPIRED = 'expired';

    protected $table = 'research_form_imports';

    protected $primaryKey = 'import_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'admin_id',
        'form_version_id',
        'validator_id',
        'import_type',
        'status',
        'original_filename',
        'storage_path',
        'file_sha256',
        'file_size',
        'expected_form_lock_version',
        'lock_version',
        'preview_output',
        'failure_message',
        'reason',
        'expires_at',
        'committed_at',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'expected_form_lock_version' => 'integer',
        'lock_version' => 'integer',
        'expires_at' => 'datetime',
        'committed_at' => 'datetime',
    ];

    public function administrator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'admin_id',
            'user_id',
        )->withTrashed();
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(
            ResearchFormVersion::class,
            'form_version_id',
            'form_version_id',
        );
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'validator_id',
            'user_id',
        )->withTrashed();
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function canCommit(): bool
    {
        return $this->status === self::STATUS_PREVIEWED
            && ! $this->isExpired();
    }
}
