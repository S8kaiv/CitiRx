<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionBookmark extends Model
{
    use HasUuids;

    protected $table = 'question_bookmarks';
    protected $primaryKey = 'bookmark_id';
    public $incrementing = false;
    protected $keyType = 'string';

    // Only created_at exists
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'question_id',
        'personal_notes',
    ];

    protected $casts = [
        'created_at' => 'datetime',
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