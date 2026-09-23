<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AssessmentSession extends Model
{
    use HasUuids;

    protected $table = 'assessment_sessions';

    protected $primaryKey = 'session_id';

    public $incrementing = false;

    protected $keyType = 'string';

    // No created_at column — only started_at and updated_at
    const CREATED_AT = null;

    protected $fillable = [
        'user_id',
        'session_type',
        'research_phase',
        'served_question_ids',
        'total_items',
        'correct_items',
        'xp_awarded',
        'started_at',
        'completed_at',
        'target_length',
        'domain_filter_id',
        'practice_mode',
        'current_question_id',
        'current_question_started_at',
        'draft_answers',
        'expires_at',
        'readiness_snapshot_pct',
        'domain_mastery_snapshot',
        'exam_form_version',
        'submission_reason',
    ];

    protected $casts = [
        'total_items' => 'integer',
        'correct_items' => 'integer',
        'xp_awarded' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'updated_at' => 'datetime',
        'served_question_ids' => 'array',
        'target_length' => 'integer',
        'domain_filter_id' => 'integer',
        'current_question_started_at' => 'datetime',
        'draft_answers' => 'array',
        'expires_at' => 'datetime',
        'readiness_snapshot_pct' => 'decimal:2',
        'domain_mastery_snapshot' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function telemetryLogs(): HasMany
    {
        return $this->hasMany(ResponseTelemetryLog::class, 'session_id', 'session_id');
    }

    public function pvpMatch(): HasOne
    {
        return $this->hasOne(PvpMatch::class, 'session_id', 'session_id');
    }

    public function domainFilter(): BelongsTo
    {
        return $this->belongsTo(
            TosDomain::class,
            'domain_filter_id',
            'domain_id'
        );
    }

    public function currentQuestion(): BelongsTo
    {
        return $this->belongsTo(
            Question::class,
            'current_question_id',
            'question_id'
        );
    }

    public function responses(): HasMany
    {
        return $this->hasMany(
            AssessmentResponse::class,
            'session_id',
            'session_id',
        );
    }
}
