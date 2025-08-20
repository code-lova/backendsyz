<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeletedAccounts extends Model
{
    protected $table = "deleted_accounts";
    protected $fillable = [
        'user_uuid',
        'fullname',
        'email',
        'reasons_for_deletion',
    ];
}
