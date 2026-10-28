<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResearchParticipant extends Model
{
    use HasUuids;

    public const STATUS_ELIGIBLE = 'eligible';

    public const STATUS_INCLUDED = 'included';

    public const STATUS_EXCLUDED = 'excluded';

    public const STATUS_WITHDRAWN = 'withdrawn';

    public const STATUS_DEVELOPMENT = 'development';

    public const STATUS_UNREGISTERED = 'unregistered';

    public const STATUSES = [
        self::STATUS_ELIGIBLE,
        self::STATUS_INCLUDED,
        self::STATUS_EXCLUDED,
        self::STATUS_WITHDRAWN,
        self::STATUS_DEVELOPMENT,
    ];

    protected $table = 'research_participants';

    protected $primaryKey = 'research_participant_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'status',
        'reason',
        'changed_by',
        'changed_at',
        'lock_version',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
        'lock_version' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id',
            'user_id',
        )->withTrashed();
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'changed_by',
            'user_id',
        )->withTrashed();
    }

    public function isIncluded(): bool
    {
        return $this->status === self::STATUS_INCLUDED;
    }

    public static function label(string $status): string
    {
        return match ($status) {
            self::STATUS_ELIGIBLE => 'Eligible—not yet included',
            self::STATUS_INCLUDED => 'Included in official analysis',
            self::STATUS_EXCLUDED => 'Excluded',
            self::STATUS_WITHDRAWN => 'Withdrawn',
            self::STATUS_DEVELOPMENT => 'Development/test account',
            self::STATUS_UNREGISTERED => 'Unregistered',
            default => str($status)->replace('_', ' ')->title()->toString(),
        };
    }
}
