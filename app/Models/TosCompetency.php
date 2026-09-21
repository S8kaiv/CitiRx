<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TosCompetency extends Model
{
    use HasUuids;

    protected $table = 'tos_competencies';

    protected $primaryKey = 'competency_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'domain_id',
        'competency_code',
        'title',
        'order_index',
        'bkt_transition_p_t',
        'tos_weight_percentage',
    ];

    protected $casts = [
        'order_index' => 'integer',
        'bkt_transition_p_t' => 'decimal:4',
        'tos_weight_percentage' => 'decimal:2',
    ];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(TosDomain::class, 'domain_id', 'domain_id');
    }

    public function pearls(): HasMany
    {
        return $this->hasMany(MicroPearl::class, 'competency_id', 'competency_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'competency_id', 'competency_id');
    }

    public function knowledgeStates(): HasMany
    {
        return $this->hasMany(UserKnowledgeState::class, 'competency_id', 'competency_id');
    }
}
