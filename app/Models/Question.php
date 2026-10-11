<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LogicException;

class Question extends Model
{
    use HasUuids;

    public const FORM_PRE_TEST_A = 'pre_test_a';

    public const FORM_POST_TEST_B = 'post_test_b';

    public const COGNITIVE_KNOWLEDGE =
        'knowledge';

    public const COGNITIVE_COMPREHENSION =
        'comprehension';

    public const COGNITIVE_APPLICATION =
        'application';

    public const COGNITIVE_ANALYSIS =
        'analysis';

    public const COGNITIVE_SYNTHESIS =
        'synthesis';

    public const COGNITIVE_EVALUATION =
        'evaluation';

    public const COGNITIVE_LEVELS = [
        self::COGNITIVE_KNOWLEDGE,
        self::COGNITIVE_COMPREHENSION,
        self::COGNITIVE_APPLICATION,
        self::COGNITIVE_ANALYSIS,
        self::COGNITIVE_SYNTHESIS,
        self::COGNITIVE_EVALUATION,
    ];

    /*
     * These fields define the identity and interpretation
     * of a question. They become immutable once the question
     * is served or receives validation metadata.
     *
     * difficulty_index_p is deliberately not included because
     * it is a derived psychometric statistic.
     */
    public const FROZEN_CONTENT_FIELDS = [
        'competency_id',
        'question_text',
        'hypercorrection_rationale',
        'is_diagnostic_pool',
        'research_form',
        'form_position',
        'cognitive_level',
    ];

    protected $table = 'questions';

    protected $primaryKey = 'question_id';

    public $incrementing = false;

    protected $keyType = 'string';

    /*
     * questions has created_at but no updated_at.
     */
    const UPDATED_AT = null;

    protected $fillable = [
        'competency_id',
        'question_text',
        'hypercorrection_rationale',
        'is_diagnostic_pool',
        'difficulty_index_p',
        'speed_flag_count',
        'is_active',
        'research_form',
        'form_position',
        'cognitive_level',
        'research_validated_at',
        'research_validated_by',
    ];

    protected $casts = [
        'is_diagnostic_pool' => 'boolean',
        'is_active' => 'boolean',
        'difficulty_index_p' => 'decimal:3',
        'speed_flag_count' => 'integer',
        'created_at' => 'datetime',
        'form_position' => 'integer',
        'research_validated_at' => 'datetime',
        'first_served_at' => 'datetime',
        'edit_version' => 'integer',
    ];

    public function competency(): BelongsTo
    {
        return $this->belongsTo(
            TosCompetency::class,
            'competency_id',
            'competency_id',
        );
    }

    public function choices(): HasMany
    {
        return $this->hasMany(
            QuestionChoice::class,
            'question_id',
            'question_id',
        );
    }

    public function correctChoice(): HasOne
    {
        return $this->hasOne(
            QuestionChoice::class,
            'question_id',
            'question_id',
        )->where(
            'is_correct',
            true,
        );
    }

    public function researchValidator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'research_validated_by',
            'user_id',
        );
    }

    public function edits(): HasMany
    {
        return $this->hasMany(
            QuestionEdit::class,
            'question_id',
            'question_id',
        )->orderByDesc(
            'edited_at',
        );
    }

    public function contentIsFrozen(): bool
    {
        /*
         * Lock partial validation states too.
         *
         * If either validation value exists, the safest action is
         * to freeze the question until the inconsistent state is
         * investigated.
         */
        return $this->first_served_at !== null
            || $this->research_validated_at !== null
            || $this->research_validated_by !== null;
    }

    protected static function booted(): void
    {
        static::updating(
            function (
                Question $question,
            ): void {
                $wasFrozen =
                    $question->getRawOriginal(
                        'first_served_at',
                    ) !== null
                    || $question->getRawOriginal(
                        'research_validated_at',
                    ) !== null
                    || $question->getRawOriginal(
                        'research_validated_by',
                    ) !== null;

                $becomingFrozen =
                    $question->first_served_at !== null
                    || $question->research_validated_at
                        !== null
                    || $question->research_validated_by
                        !== null;

                if (
                    ($wasFrozen || $becomingFrozen)
                    && $question->isDirty(
                        self::FROZEN_CONTENT_FIELDS,
                    )
                ) {
                    throw new LogicException(
                        'Served or validated question '
                            .'content cannot be changed.',
                    );
                }
            },
        );
    }
}
