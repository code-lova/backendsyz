<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class clientSupportMessages extends BaseModel
{
    protected $table = 'client_support_messages';
    protected $fillable = ['uuid', 'ticket_uuid', 'sender_uuid', 'sender_type', 'message'];
    protected $casts = [
        'uuid' => 'string',
        'ticket_uuid' => 'string',
        'sender_uuid' => 'string',
    ];

    public function ticket()
    {
        return $this->belongsTo(clientSupportTicket::class, 'ticket_uuid', 'uuid');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_uuid', 'uuid');
    }
}
