<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentResponse extends Model
{
    use HasUuids;

    protected $table = 'assessment_responses';

    protected $primaryKey = 'response_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'session_id',
        'question_id',
        'selected_choice_id',
        'item_position',
        'is_correct',
        'answered_at',
    ];

    protected $casts = [
        'item_position' => 'integer',
        'is_correct' => 'boolean',
        'answered_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(
            AssessmentSession::class,
            'session_id',
            'session_id',
        );
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(
            Question::class,
            'question_id',
            'question_id',
        );
    }

    public function selectedChoice(): BelongsTo
    {
        return $this->belongsTo(
            QuestionChoice::class,
            'selected_choice_id',
            'choice_id',
        );
    }
}
