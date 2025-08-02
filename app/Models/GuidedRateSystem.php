<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\HasMany;


class GuidedRateSystem extends Model
{
    protected $table = 'guided_rate_systems';
    protected $fillable = [
        'uuid',
        'user_uuid',
        'rate_type',
        'nurse_type',
        'guided_rate',
        'care_duration',
        'guided_rate_justification',
    ];

    protected static function boot(){
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

     // Relationship: GRS has many service types
    public function serviceTypes(): HasMany
    {
        return $this->hasMany(GuidedRateServiceType::class, 'grs_uuid', 'uuid');
    }

    //relationship to User if needed
    public function user()
    {
        return $this->belongsTo(User::class, 'user_uuid', 'uuid');
    }
}
