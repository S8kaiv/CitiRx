<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResponseTelemetryLog extends Model
{
    use HasUuids;

    protected $table = 'response_telemetry_logs';
    protected $primaryKey = 'log_id';
    public $incrementing = false;
    protected $keyType = 'string';

    // Only created_at exists — no updated_at
    const UPDATED_AT = null;

    protected $fillable = [
        'session_id',
        'user_id',
        'question_id',
        'competency_id',
        'selected_choice_id',
        'is_correct',
        'response_time_seconds',
        'item_position',
        'is_speed_flagged',
        'prior_p_l',
        'posterior_p_l',
    ];

    protected $casts = [
        'is_correct'             => 'boolean',
        'is_speed_flagged'       => 'boolean',
        'response_time_seconds'  => 'decimal:2',
        'item_position'          => 'integer',
        'prior_p_l'              => 'decimal:4',
        'posterior_p_l'          => 'decimal:4',
        'created_at'             => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(AssessmentSession::class, 'session_id', 'session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'question_id', 'question_id');
    }

    public function competency(): BelongsTo
    {
        return $this->belongsTo(TosCompetency::class, 'competency_id', 'competency_id');
    }

    /**
     * The choice the user actually selected.
     * Note: FK column is `selected_choice_id` but references `choice_id`.
     */
    public function selectedChoice(): BelongsTo
    {
        return $this->belongsTo(
            QuestionChoice::class,
            'selected_choice_id',
            'choice_id'
        );
    }
}