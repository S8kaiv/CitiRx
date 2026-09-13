<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Level extends Model
{
    protected $table = 'levels';
    protected $primaryKey = 'level_number';
    public $incrementing = false;   // Natural key — 1, 2, 3...
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'level_number',
        'min_xp',
        'tier_id',
    ];

    protected $casts = [
        'level_number' => 'integer',
        'min_xp'       => 'integer',
        'tier_id'      => 'integer',
    ];

    public function tier(): BelongsTo
    {
        return $this->belongsTo(Tier::class, 'tier_id', 'tier_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'current_level', 'level_number');
    }
}