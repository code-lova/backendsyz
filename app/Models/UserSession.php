<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSession extends BaseModel
{
    protected $table = 'user_sessions';
    protected $fillable = [
        'user_uuid',
        'ip_address',
        'device',
        'platform',
        'browser',
        'status',
        'last_used_at',
    ];


}
