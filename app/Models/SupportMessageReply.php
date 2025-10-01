<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportMessageReply extends BaseModel
{
    protected $table = 'support_message_replies';
    protected $fillable = [
        'uuid',
        'admin_reply',
        'support_message_uuid',
        'reference',
    ];


    public function supportMessage()
    {
        return $this->belongsTo(SupportMessage::class, 'support_message_uuid', 'uuid');
    }

}
