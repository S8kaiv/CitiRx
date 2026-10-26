<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MicroPearl extends Model
{
    use HasUuids;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_IN_REVIEW = 'in_review';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_RETIRED = 'retired';

    public const RIGHTS_PENDING = 'pending';

    public const RIGHTS_ORIGINAL = 'original';

    public const RIGHTS_AUTHORIZED = 'authorized';

    public const RIGHTS_PUBLIC_DOMAIN = 'public_domain';

    public const PUBLISHABLE_RIGHTS_STATUSES = [
        self::RIGHTS_ORIGINAL,
        self::RIGHTS_AUTHORIZED,
        self::RIGHTS_PUBLIC_DOMAIN,
    ];

    protected $table = 'micro_pearls';

    protected $primaryKey = 'pearl_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'competency_id',
        'slug',
        'title',
        'mnemonic_summary',
        'lesson_content',
        'learning_objectives',
        'key_points',
        'common_pitfalls',
        'recall_prompts',
        'source_references',
        'estimated_read_seconds',
        'order_index',
        'ai_enrichment_allowed',
        'created_by',
        'submitted_at',
        'retired_at',
        'edit_version',
    ];

    protected $casts = [
        'learning_objectives' => 'array',
        'key_points' => 'array',
        'common_pitfalls' => 'array',
        'recall_prompts' => 'array',
        'source_references' => 'array',
        'generation_metadata' => 'array',
        'estimated_read_seconds' => 'integer',
        'order_index' => 'integer',
        'content_version' => 'integer',
        'is_ai_generated' => 'boolean',
        'ai_enrichment_allowed' => 'boolean',
        'reviewed_at' => 'datetime',
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'submitted_at' => 'datetime',
        'retired_at' => 'datetime',
        'edit_version' => 'integer',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function competency(): BelongsTo
    {
        return $this->belongsTo(
            TosCompetency::class,
            'competency_id',
            'competency_id'
        );
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'reviewed_by',
            'user_id'
        );
    }

    public function scopePublished(
        Builder $query
    ): Builder {
        return $query
            ->where(
                'micro_pearls.content_status',
                self::STATUS_PUBLISHED
            )
            ->whereIn(
                'micro_pearls.rights_status',
                self::PUBLISHABLE_RIGHTS_STATUSES
            )
            ->whereNotNull(
                'micro_pearls.slug'
            )
            ->whereNotNull(
                'micro_pearls.lesson_content'
            )
            ->where(
                'micro_pearls.lesson_content',
                '<>',
                ''
            )
            ->whereJsonLength(
                'micro_pearls.source_references',
                '>',
                0
            )
            ->where(
                function (
                    Builder $aiQuery
                ): void {
                    $aiQuery
                        ->where(
                            'micro_pearls.is_ai_generated',
                            false
                        )
                        ->orWhere(
                            function (
                                Builder $generatedQuery
                            ): void {
                                $generatedQuery
                                    ->where(
                                        'micro_pearls.is_ai_generated',
                                        true
                                    )
                                    ->whereNotNull(
                                        'micro_pearls.generation_metadata'
                                    );
                            }
                        );
                }
            )
            ->whereNotNull(
                'micro_pearls.reviewed_by'
            )
            ->whereNotNull(
                'micro_pearls.reviewed_at'
            )
            ->whereNotNull(
                'micro_pearls.published_at'
            )
            ->where(
                'micro_pearls.published_at',
                '<=',
                now()
            );
    }

    public function isPublished(): bool
    {
        $hasGenerationMetadata =
            ! $this->is_ai_generated
            || filled(
                $this->generation_metadata
            );

        return $this->content_status
            === self::STATUS_PUBLISHED
            && in_array(
                $this->rights_status,
                self::PUBLISHABLE_RIGHTS_STATUSES,
                true
            )
            && filled($this->slug)
            && filled($this->lesson_content)
            && filled($this->source_references)
            && $hasGenerationMetadata
            && $this->reviewed_by !== null
            && $this->reviewed_at !== null
            && $this->published_at !== null
            && ! $this->published_at->isFuture();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by',
            'user_id',
        )->withTrashed();
    }

    public function edits(): HasMany
    {
        return $this->hasMany(
            MicroPearlEdit::class,
            'pearl_id',
            'pearl_id',
        )->latest('edited_at');
    }
}
