<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnavailableDates extends BaseModel
{
    protected $table = 'unavailable_dates';
    protected $fillable = ['user_uuid', 'date'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_uuid', 'uuid');
    }
}
