<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSettings extends BaseModel
{
    protected $table = 'user_settings';

    protected $fillable = [
        'user_uuid',
        'email_notifications',
        'push_notifications',
        'booking_reminders',
        'marketing_updates',
        'profile_visibility',
        'activity_status',
        'data_collection',
        'third_party_cookies'
];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_uuid', 'uuid');
    }
}
