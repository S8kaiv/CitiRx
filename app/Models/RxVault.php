<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RxVault extends Model
{
    use HasUuids;

    protected $table = 'rx_vault';
    protected $primaryKey = 'vault_id';
    public $incrementing = false;
    protected $keyType = 'string';

    // No created_at/updated_at — added_at is a data field
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'question_id',
        'consecutive_correct_count',
        'is_cleared',
        'added_at',
        'cleared_at',
    ];

    protected $casts = [
        'consecutive_correct_count' => 'integer',
        'is_cleared'                => 'boolean',
        'added_at'                  => 'datetime',
        'cleared_at'                => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'question_id', 'question_id');
    }
}