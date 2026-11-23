<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class PracticeQuestionImport extends Model
{
    use HasUuids, Prunable;

    protected $primaryKey = 'import_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['uploader_id', 'original_filename', 'preview_rows', 'row_count', 'expires_at', 'committed_at'];

    protected $casts = ['preview_rows' => 'array', 'row_count' => 'integer', 'expires_at' => 'datetime', 'committed_at' => 'datetime'];

    public function canCommit(): bool
    {
        return $this->committed_at === null && $this->expires_at->isFuture();
    }

    public function prunable(): Builder
    {
        return static::query()->where('expires_at', '<=', now());
    }
}
