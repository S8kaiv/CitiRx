<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResearchAnalyticsSnapshot extends Model
{
    use HasUuids;

    protected $table = 'research_analytics_snapshots';
    protected $primaryKey = 'snapshot_id';
    public $incrementing = false;
    protected $keyType = 'string';

    // Only created_at exists
    const UPDATED_AT = null;

    protected $fillable = [
        'cohort_id',
        'snapshot_date',
        'cohort_size',
        'cohort_mean_predicted_readiness_pct',
        'projected_pass_rate_pct',
        'speed_floor_compliance_pct',
        'bkt_mae',
        'bkt_rmse',
        'wilcoxon_w_statistic',
        'wilcoxon_p_value',
    ];

    protected $casts = [
        'snapshot_date'                       => 'date',
        'cohort_size'                         => 'integer',
        'cohort_mean_predicted_readiness_pct' => 'decimal:2',
        'projected_pass_rate_pct'             => 'decimal:2',
        'speed_floor_compliance_pct'          => 'decimal:2',
        'bkt_mae'                             => 'decimal:4',
        'bkt_rmse'                            => 'decimal:4',
        'wilcoxon_w_statistic'                => 'decimal:2',
        'wilcoxon_p_value'                    => 'decimal:4',
        'created_at'                          => 'datetime',
    ];

    public function cohort(): BelongsTo
    {
        return $this->belongsTo(Cohort::class, 'cohort_id', 'cohort_id');
    }
}