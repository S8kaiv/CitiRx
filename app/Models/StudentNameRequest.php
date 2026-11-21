<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentNameRequest extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = ['original_names' => 'array', 'requested_names' => 'array', 'decided_at' => 'datetime'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by', 'user_id')->withTrashed();
    }
}
