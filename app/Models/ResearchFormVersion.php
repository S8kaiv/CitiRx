<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResearchFormVersion extends Model
{
    use HasUuids;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_IN_REVIEW = 'in_review';

    public const STATUS_VALIDATED = 'validated';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_RETIRED = 'retired';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_IN_REVIEW,
        self::STATUS_VALIDATED,
        self::STATUS_PUBLISHED,
        self::STATUS_RETIRED,
    ];

    protected $table =
        'research_form_versions';

    protected $primaryKey =
        'form_version_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'research_form',
        'version_number',
        'version_code',
        'status',
        'is_active',
        'active_slot',
        'created_by',
        'published_by',
        'published_at',
        'first_served_at',
        'lock_version',
    ];

    protected $casts = [
        'version_number' => 'integer',
        'is_active' => 'boolean',
        'published_at' => 'datetime',
        'first_served_at' => 'datetime',
        'lock_version' => 'integer',
    ];

    public function scopeActive(
        Builder $query,
    ): Builder {
        return $query
            ->where('is_active', true)
            ->whereNotNull('active_slot');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(
            Question::class,
            'form_version_id',
            'form_version_id',
        );
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(
            AssessmentSession::class,
            'form_version_id',
            'form_version_id',
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by',
            'user_id',
        )->withTrashed();
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'published_by',
            'user_id',
        )->withTrashed();
    }
}
