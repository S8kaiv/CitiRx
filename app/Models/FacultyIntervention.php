<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacultyIntervention extends Model
{
    use HasUuids;

    protected $table = 'faculty_interventions';
    protected $primaryKey = 'intervention_id';
    public $incrementing = false;
    protected $keyType = 'string';

    // No created_at/updated_at — assigned_at is a data field
    public $timestamps = false;

    protected $fillable = [
        'faculty_id',
        'target_user_id',
        'domain_id',
        'competency_id',
        'intervention_type',
        'custom_message',
        'is_completed',
        'assigned_at',
        'completed_at',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'assigned_at'  => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(User::class, 'faculty_id', 'user_id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id', 'user_id');
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(TosDomain::class, 'domain_id', 'domain_id');
    }

    public function competency(): BelongsTo
    {
        return $this->belongsTo(TosCompetency::class, 'competency_id', 'competency_id');
    }
}