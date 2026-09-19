<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory;
    use HasUuids;
    use Notifiable;
    use SoftDeletes;

    protected $table = 'users';
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'first_name',
        'last_name',
        'middle_name',
        'cohort_id',
        'student_id',
        'email',
        'password',
        'role',
        'is_diagnostic_completed',
        'predicted_readiness_pct',
        'total_xp',
        'current_level',
        'streak_count',
        'last_active_date',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at'        => 'datetime',
        'password'                 => 'hashed',
        'is_diagnostic_completed'  => 'boolean',
        'predicted_readiness_pct'  => 'decimal:2',
        'total_xp'                 => 'integer',
        'current_level'             => 'integer',
        'streak_count'             => 'integer',
        'last_active_date'         => 'date',
        'deleted_at'               => 'datetime',
    ];

    // ------------------------------------------------------------------
    // Relationships — Belongs To
    // ------------------------------------------------------------------

    public function cohort(): BelongsTo
    {
        return $this->belongsTo(Cohort::class, 'cohort_id', 'cohort_id');
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class, 'current_level', 'level_number');
    }

    // ------------------------------------------------------------------
    // Relationships — Has Many
    // ------------------------------------------------------------------

    public function assessmentSessions(): HasMany
    {
        return $this->hasMany(AssessmentSession::class, 'user_id', 'user_id');
    }

    public function responseTelemetryLogs(): HasMany
    {
        return $this->hasMany(ResponseTelemetryLog::class, 'user_id', 'user_id');
    }

    public function knowledgeStates(): HasMany
    {
        return $this->hasMany(UserKnowledgeState::class, 'user_id', 'user_id');
    }

    public function rxVaultEntries(): HasMany
    {
        return $this->hasMany(RxVault::class, 'user_id', 'user_id');
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(QuestionBookmark::class, 'user_id', 'user_id');
    }

    public function pvpMatches(): HasMany
    {
        return $this->hasMany(PvpMatch::class, 'user_id', 'user_id');
    }

    public function surveyEvaluations(): HasMany
    {
        return $this->hasMany(IsoSurveyEvaluation::class, 'user_id', 'user_id');
    }

    public function xpTransactions(): HasMany
    {
        return $this->hasMany(XpTransaction::class, 'user_id', 'user_id');
    }

    public function facultyInterventionsAssigned(): HasMany
    {
        return $this->hasMany(FacultyIntervention::class, 'faculty_id', 'user_id');
    }

    public function facultyInterventionsReceived(): HasMany
    {
        return $this->hasMany(FacultyIntervention::class, 'target_user_id', 'user_id');
    }

    // ------------------------------------------------------------------
    // Relationships — Belongs To Many (via pivot)
    // ------------------------------------------------------------------

    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(
            Badge::class,       // related model
            'user_badges',      // pivot table
            'user_id',          // FK on pivot pointing to users
            'badge_id'          // FK on pivot pointing to badges
        )->withPivot('unlocked_at');
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    public function fullName(): string
    {
        return collect([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
        ])->filter()->implode(' ');
    }
}