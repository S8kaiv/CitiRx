<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MicroPearl extends Model
{
    use HasUuids;

    protected $table = 'micro_pearls';
    protected $primaryKey = 'pearl_id';
    public $incrementing = false;
    protected $keyType = 'string';

    // Only created_at exists — no updated_at
    const UPDATED_AT = null;

    protected $fillable = [
        'competency_id',
        'title',
        'mnemonic_summary',
        'estimated_read_seconds',
        'order_index',
    ];

    protected $casts = [
        'estimated_read_seconds' => 'integer',
        'order_index'            => 'integer',
        'created_at'             => 'datetime',
    ];

    public function competency(): BelongsTo
    {
        return $this->belongsTo(TosCompetency::class, 'competency_id', 'competency_id');
    }
}