<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword as CanResetPasswordTrait;

class User extends Authenticatable implements CanResetPassword
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, CanResetPasswordTrait;

    protected static function boot(){
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'practitioner',
        'about',
        'phone',
        'gender',
        'date_of_birth',
        'image',
        'image_public_id',
        'address',
        'religion',
        'email_verification_code',
        'email_verification_code_expires_at',
        'latitude',
        'longitude',
        'last_logged_in',
        'uuid',
        'country',
        'region',
        'working_hours',
        'two_factor_enabled',
        'two_factor_code',
        'two_factor_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function sessions()
    {
        return $this->hasMany(UserSession::class, 'user_uuid', 'uuid');
    }

    public function settings()
    {
        return $this->hasOne(UserSettings::class, 'user_uuid', 'uuid');
    }

    public function healthworkerReviews()
    {
        return $this->hasMany(HealthWorkerReview::class, 'healthworker_uuid', 'uuid');
    }
}
