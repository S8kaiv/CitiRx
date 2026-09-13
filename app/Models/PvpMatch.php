<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PvpMatch extends Model
{
    use HasUuids;

    protected $table = 'pvp_matches';
    protected $primaryKey = 'match_id';
    public $incrementing = false;
    protected $keyType = 'string';

    // Only created_at exists
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'session_id',
        'bot_persona_name',
        'user_final_score',
        'bot_final_score',
        'outcome',
        'xp_earned',
    ];

    protected $casts = [
        'user_final_score' => 'integer',
        'bot_final_score'  => 'integer',
        'xp_earned'        => 'integer',
        'created_at'       => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AssessmentSession::class, 'session_id', 'session_id');
    }
}