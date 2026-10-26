<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class MicroPearlEdit extends Model
{
    use HasUuids;

    protected $table = 'micro_pearl_edits';

    protected $primaryKey = 'edit_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'pearl_id',
        'editor_id',
        'action',
        'changes',
        'reason',
        'version_before',
        'version_after',
        'edited_at',
    ];

    protected $casts = [
        'changes' => 'array',
        'version_before' => 'integer',
        'version_after' => 'integer',
        'edited_at' => 'datetime',
    ];

    public function pearl(): BelongsTo
    {
        return $this->belongsTo(
            MicroPearl::class,
            'pearl_id',
            'pearl_id',
        );
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'editor_id',
            'user_id',
        )->withTrashed();
    }

    protected static function booted(): void
    {
        static::updating(
            fn () => throw new LogicException(
                'Micro Pearl audit records are immutable.',
            ),
        );

        static::deleting(
            fn () => throw new LogicException(
                'Micro Pearl audit records cannot be deleted.',
            ),
        );
    }
}
