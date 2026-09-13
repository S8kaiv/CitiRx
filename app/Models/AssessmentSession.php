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
        'total_items',
        'correct_items',
        'xp_awarded',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'total_items'    => 'integer',
        'correct_items'  => 'integer',
        'xp_awarded'     => 'integer',
        'started_at'     => 'datetime',
        'completed_at'   => 'datetime',
        'updated_at'     => 'datetime',
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
}