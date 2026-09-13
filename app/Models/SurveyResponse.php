<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyResponse extends Model
{
    use HasUuids;

    protected $table = 'survey_responses';
    protected $primaryKey = 'response_id';
    public $incrementing = false;
    protected $keyType = 'string';

    // Only created_at exists
    const UPDATED_AT = null;

    protected $fillable = [
        'evaluation_id',
        'item_id',
        'likert_score',
    ];

    protected $casts = [
        'likert_score' => 'integer',
        'created_at'   => 'datetime',
    ];

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(IsoSurveyEvaluation::class, 'evaluation_id', 'evaluation_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(SurveyItem::class, 'item_id', 'item_id');
    }
}