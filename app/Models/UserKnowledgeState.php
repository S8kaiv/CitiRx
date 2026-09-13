<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserKnowledgeState extends Model
{
    use HasUuids;

    protected $table = 'user_knowledge_states';
    protected $primaryKey = 'state_id';
    public $incrementing = false;
    protected $keyType = 'string';

    // No created_at or updated_at columns on this table
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'competency_id',
        'current_mastery_p_l',
        'total_attempts',
        'total_correct',
        'last_evaluated_at',
    ];

    protected $casts = [
        'current_mastery_p_l' => 'decimal:4',
        'total_attempts'      => 'integer',
        'total_correct'       => 'integer',
        'last_evaluated_at'   => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function competency(): BelongsTo
    {
        return $this->belongsTo(TosCompetency::class, 'competency_id', 'competency_id');
    }
}