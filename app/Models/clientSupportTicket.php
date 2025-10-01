<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class clientSupportTicket extends BaseModel
{
    protected $table = 'client_support_tickets';
    protected $fillable = ['uuid', 'user_uuid', 'reference', 'subject', 'message', 'status'];
    protected $casts = [
        'uuid' => 'string',
        'user_uuid' => 'string',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_uuid', 'uuid');
    }

    public function messages()
    {
        return $this->hasMany(clientSupportMessages::class, 'ticket_uuid', 'uuid');
    }
}
