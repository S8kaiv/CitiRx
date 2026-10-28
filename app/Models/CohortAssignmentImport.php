<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CohortAssignmentImport extends Model
{
    use HasUuids;
    use MassPrunable;

    public const UPDATED_AT = null;

    protected $table = 'cohort_assignment_imports';

    protected $primaryKey = 'import_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'admin_id',
        'original_filename',
        'file_sha256',
        'assignment_rows',
        'summary',
        'lock_version',
        'expires_at',
        'committed_at',
    ];

    protected $casts = [
        'assignment_rows' => 'array',
        'summary' => 'array',
        'lock_version' => 'integer',
        'expires_at' => 'datetime',
        'committed_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function administrator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'admin_id',
            'user_id',
        )->withTrashed();
    }

    public function prunable(): Builder
    {
        return static::query()->where(
            function (Builder $query): void {
                $query
                    ->where(
                        function (Builder $expired): void {
                            $expired
                                ->whereNull('committed_at')
                                ->where(
                                    'expires_at',
                                    '<',
                                    now()->subDay(),
                                );
                        },
                    )
                    ->orWhere(
                        'committed_at',
                        '<',
                        now()->subDays(7),
                    );
            },
        );
    }
}
