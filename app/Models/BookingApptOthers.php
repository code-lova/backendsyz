<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingApptOthers extends BaseModel
{
    protected $table = 'booking_appt_others';
    protected $fillable = [
        'uuid',
        'booking_appts_uuid',
        'medical_services',
        'other_extra_service',
    ];

    protected $casts = [
        'medical_services' => 'array',
        'other_extra_service' => 'array',
    ];

    // Relationship: belongs to a GuidedRateSystem
    public function bookingAppt(): BelongsTo
    {
        return $this->belongsTo(BookingAppt::class, 'booking_appts_uuid', 'uuid');
    }
}
