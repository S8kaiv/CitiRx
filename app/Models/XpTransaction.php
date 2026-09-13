<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class XpTransaction extends Model
{
    use HasUuids;

    protected $table = 'xp_transactions';
    protected $primaryKey = 'transaction_id';
    public $incrementing = false;
    protected $keyType = 'string';

    // Only created_at exists
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'source_type',
        'source_id',
        'xp_delta',
        'reason',
    ];

    protected $casts = [
        'xp_delta'   => 'integer',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}