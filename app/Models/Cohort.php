<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cohort extends Model
{
    use HasUuids;

    protected $table = 'cohorts';
    protected $primaryKey = 'cohort_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'cohort_name',
        'academic_year',
        'target_phle_date',
    ];

    protected $casts = [
        'target_phle_date' => 'date',
        'created_at'       => 'datetime',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'cohort_id', 'cohort_id');
    }

    public function researchSnapshots(): HasMany
    {
        return $this->hasMany(ResearchAnalyticsSnapshot::class, 'cohort_id', 'cohort_id');
    }
}