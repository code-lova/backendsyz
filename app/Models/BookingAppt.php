<?php

namespace App\Models;

use Faker\Provider\Base;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BookingAppt extends BaseModel
{
    protected $table = 'booking_appts';
    protected $fillable = [
        'uuid',
        'booking_reference',
        'user_uuid',
        'health_worker_uuid',
        'requesting_for',
        'someone_name',
        'someone_phone',
        'someone_email',
        'care_duration',
        'care_duration_value',
        'care_type',
        'accommodation',
        'meal',
        'num_of_meals',
        'special_notes',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'start_time_period',
        'end_time_period',
        'status',
        'reason_for_cancellation',
        'cancelled_by_user_uuid',
        'confirmed_at',
        'confirmed_by',
        'started_at',
        'updated_by',

    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_uuid', 'uuid');
    }

    public function healthWorker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'health_worker_uuid', 'uuid');
    }

    public function others(): HasMany
    {
        return $this->hasMany(BookingApptOthers::class, 'booking_appts_uuid', 'uuid');
    }

    public function recurrence(): HasOne
    {
        return $this->hasOne(BookingRecurrence::class, 'booking_appts_uuid', 'uuid');
    }
}
