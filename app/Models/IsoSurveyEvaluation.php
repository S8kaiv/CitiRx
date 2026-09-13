<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IsoSurveyEvaluation extends Model
{
    use HasUuids;

    protected $table = 'iso_survey_evaluations';
    protected $primaryKey = 'evaluation_id';
    public $incrementing = false;
    protected $keyType = 'string';

    // No created_at/updated_at — submitted_at is a data field
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'evaluator_role',
        'has_submitted_external_form',
        'overall_mean_score',
        'submitted_at',
    ];

    protected $casts = [
        'has_submitted_external_form' => 'boolean',
        'overall_mean_score'          => 'decimal:2',
        'submitted_at'                => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class, 'evaluation_id', 'evaluation_id');
    }
}