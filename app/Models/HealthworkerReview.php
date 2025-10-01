<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HealthworkerReview extends BaseModel
{
    protected $fillable = [
        'booking_appt_uuid',
        'client_uuid',
        'healthworker_uuid',
        'rating',
        'review',
        'reviewed_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'reviewed_at' => 'datetime',
        'rating' => 'integer',
    ];

    public function bookingAppt()
    {
        return $this->belongsTo(BookingAppt::class, 'booking_appt_uuid', 'uuid');
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_uuid', 'uuid');
    }

    public function healthworker()
    {
        return $this->belongsTo(User::class, 'healthworker_uuid', 'uuid');
    }
}
