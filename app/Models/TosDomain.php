<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TosDomain extends Model
{
    protected $table = 'tos_domains';
    protected $primaryKey = 'domain_id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'domain_number',
        'domain_name',
        'prc_weight_percentage',
    ];

    protected $casts = [
        'domain_number'         => 'integer',
        'prc_weight_percentage' => 'decimal:2',
    ];

    public function competencies(): HasMany
    {
        return $this->hasMany(TosCompetency::class, 'domain_id', 'domain_id');
    }

    public function facultyInterventions(): HasMany
    {
        return $this->hasMany(FacultyIntervention::class, 'domain_id', 'domain_id');
    }
}