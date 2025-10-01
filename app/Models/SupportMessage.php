<?php

namespace App\Models;

class SupportMessage extends BaseModel
{
    protected $table = 'support_messages';
    protected $fillable = [
        'uuid',
        'user_uuid',
        'subject',
        'message',
        'status',
        'reference',
    ];


    public function user(){
        return $this->belongsTo(User::class, 'user_uuid', 'uuid');
    }

    public function replies()
    {
        return $this->hasMany(SupportMessageReply::class, 'support_message_uuid', 'uuid');
    }
}
