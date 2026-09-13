<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tier extends Model
{
    protected $table = 'tiers';
    protected $primaryKey = 'tier_id';
    public $incrementing = true;    // TINYINT AUTO_INCREMENT
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'tier_name',
        'tier_rank',
    ];

    public function levels(): HasMany
    {
        return $this->hasMany(Level::class, 'tier_id', 'tier_id');
    }
}