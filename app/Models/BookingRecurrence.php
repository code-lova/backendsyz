<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingRecurrence extends BaseModel
{
    protected $table = 'booking_recurrences';

    protected $fillable = [
        'uuid',
        'booking_appts_uuid',
        'is_recurring',
        'recurrence_type',
        'recurrence_days',
        'recurrence_end_type',
        'recurrence_end_date',
        'recurrence_occurrences',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'recurrence_days' => 'array',
        'recurrence_end_date' => 'date',
        'recurrence_occurrences' => 'integer',
    ];

    /**
     * Get the booking appointment that owns the recurrence.
     */
    public function bookingAppt(): BelongsTo
    {
        return $this->belongsTo(BookingAppt::class, 'booking_appts_uuid', 'uuid');
    }
}
