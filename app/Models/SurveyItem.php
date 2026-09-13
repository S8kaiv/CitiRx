<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurveyItem extends Model
{
    use HasUuids;

    protected $table = 'survey_items';
    protected $primaryKey = 'item_id';
    public $incrementing = false;
    protected $keyType = 'string';

    // Only created_at exists
    const UPDATED_AT = null;

    protected $fillable = [
        'iso_characteristic',
        'item_code',
        'item_text',
        'order_index',
    ];

    protected $casts = [
        'order_index' => 'integer',
        'created_at'  => 'datetime',
    ];

    public function responses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class, 'item_id', 'item_id');
    }
}