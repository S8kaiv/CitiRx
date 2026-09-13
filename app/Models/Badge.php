<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Badge extends Model
{
    use HasUuids;

    protected $table = 'badges';
    protected $primaryKey = 'badge_id';
    public $incrementing = false;
    protected $keyType = 'string';

    // Only created_at exists
    const UPDATED_AT = null;

    protected $fillable = [
        'badge_code',
        'badge_name',
        'description',
        'icon_path',
        'criteria_json',
    ];

    protected $casts = [
        'criteria_json' => 'array',   // auto JSON encode/decode
        'created_at'    => 'datetime',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'user_badges',
            'badge_id',
            'user_id'
        )->withPivot('unlocked_at');
    }
}