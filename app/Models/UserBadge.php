<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBadge extends Model
{
    use HasUuids;

    protected $table = 'user_badges';
    protected $primaryKey = 'user_badge_id';
    public $incrementing = false;
    protected $keyType = 'string';

    // No created_at/updated_at — unlocked_at is a data field
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'badge_id',
        'unlocked_at',
    ];

    protected $casts = [
        'unlocked_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function badge(): BelongsTo
    {
        return $this->belongsTo(Badge::class, 'badge_id', 'badge_id');
    }
}