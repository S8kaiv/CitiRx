<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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
        'post_test_opens_at',
        'post_test_closes_at',
    ];

    protected $casts = [
        'target_phle_date' => 'date',
        'created_at' => 'datetime',
        'post_test_opens_at' => 'datetime',
        'post_test_closes_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(
            User::class,
            'cohort_id',
            'cohort_id',
        );
    }

    public function researchSnapshots(): HasMany
    {
        return $this->hasMany(
            ResearchAnalyticsSnapshot::class,
            'cohort_id',
            'cohort_id',
        );
    }

    public function faculty(): HasMany
    {
        return $this->users()->where(
            'role',
            'faculty',
        );
    }

    public function students(): HasMany
    {
        return $this->users()->where(
            'role',
            'student',
        );
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    public function postTestWindowStatus(): string
    {
        if ($this->archived_at !== null) {
            return 'archived';
        }

        if (
            $this->post_test_opens_at === null
            || $this->post_test_closes_at === null
        ) {
            return 'closed';
        }

        if (now()->lt($this->post_test_opens_at)) {
            return 'scheduled';
        }

        if (now()->gte($this->post_test_closes_at)) {
            return 'ended';
        }

        return 'open';
    }
}
