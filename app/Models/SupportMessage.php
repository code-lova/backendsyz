<?php

namespace App\Models;

class SupportMessage extends BaseModel
{
    protected $table = 'support_messages';
    protected $fillable = [
        'user_uuid',
        'subject',
        'message',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_uuid', 'uuid');
    }
}
