<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;


class GuidedRateServiceType extends Model
{
    protected $table = 'guided_rate_service_types';
    protected $fillable = [
        'uuid',
        'grs_uuid',
        'service_type',
    ];

    protected static function boot(){
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    // Relationship: belongs to a GuidedRateSystem
    public function guidedRateSystem(): BelongsTo
    {
        return $this->belongsTo(GuidedRateSystem::class, 'grs_uuid', 'uuid');
    }

}
