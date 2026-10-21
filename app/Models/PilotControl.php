<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PilotControl extends Model
{
    use HasUuids;

    public const PHASE_SETUP = 'setup';

    public const PHASE_DIAGNOSTIC_OPEN = 'diagnostic_open';

    public const PHASE_BETWEEN_ASSESSMENTS =
        'between_assessments';

    public const PHASE_POST_TEST_OPEN = 'post_test_open';

    public const PHASE_CLOSED = 'closed';

    public const PHASES = [
        self::PHASE_SETUP,
        self::PHASE_DIAGNOSTIC_OPEN,
        self::PHASE_BETWEEN_ASSESSMENTS,
        self::PHASE_POST_TEST_OPEN,
        self::PHASE_CLOSED,
    ];

    protected $table = 'pilot_controls';

    protected $primaryKey = 'pilot_control_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'phase',
        'official_collection_started_at',
        'official_collection_started_by',
        'lock_version',
    ];

    protected $casts = [
        'official_collection_started_at' => 'datetime',
        'lock_version' => 'integer',
    ];

    public function starter(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'official_collection_started_by',
            'user_id',
        )->withTrashed();
    }

    public function diagnosticIsOpen(): bool
    {
        return $this->phase
            === self::PHASE_DIAGNOSTIC_OPEN;
    }

    public function postTestIsOpen(): bool
    {
        return $this->phase
            === self::PHASE_POST_TEST_OPEN;
    }

    public function officialCollectionHasStarted(): bool
    {
        return $this->official_collection_started_at
            !== null;
    }
}
