<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Question extends Model
{
    use HasUuids;

    protected $table = 'questions';
    protected $primaryKey = 'question_id';
    public $incrementing = false;
    protected $keyType = 'string';

    const UPDATED_AT = null;

    protected $fillable = [
        'competency_id',
        'question_text',
        'hypercorrection_rationale',
        'is_diagnostic_pool',
        'difficulty_index_p',
        'speed_flag_count',
        'is_active',
    ];

    protected $casts = [
        'is_diagnostic_pool' => 'boolean',
        'is_active'          => 'boolean',
        'difficulty_index_p' => 'decimal:3',
        'speed_flag_count'   => 'integer',
        'created_at'         => 'datetime',
    ];

    public function competency(): BelongsTo
    {
        return $this->belongsTo(TosCompetency::class, 'competency_id', 'competency_id');
    }

    public function choices(): HasMany
    {
        return $this->hasMany(QuestionChoice::class, 'question_id', 'question_id');
    }

    /**
     * Convenience accessor: get only the correct choice.
     * Usage: $question->correctChoice
     */
    public function correctChoice(): HasOne
    {
        return $this->hasOne(
            QuestionChoice::class,
            'question_id',
            'question_id'
        )->where('is_correct', true);
    }
}