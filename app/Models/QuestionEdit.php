<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class QuestionEdit extends Model
{
    use HasUuids;

    protected $table = 'question_edits';

    protected $primaryKey = 'edit_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'question_id',
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

    public function question(): BelongsTo
    {
        return $this->belongsTo(
            Question::class,
            'question_id',
            'question_id',
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
                'Question audit records are immutable.',
            ),
        );

        static::deleting(
            fn () => throw new LogicException(
                'Question audit records cannot be deleted.',
            ),
        );
    }
}
