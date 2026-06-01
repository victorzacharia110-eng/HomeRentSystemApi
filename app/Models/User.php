<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\CanResetPassword;

class User extends Authenticatable implements CanResetPassword
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'last_name',
        'phone_number',
        'email',
        'password',
        'is_landlord',
        'room_id'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

public function sendPasswordResetNotification($token)
{
    $url = config('app.frontend_url')
        . '/reset-password?token=' . $token
        . '&email=' . urlencode($this->email);

    $this->notify(new \App\Notifications\ResetPassword($url));
}

public function room()
{
    return $this->belongsTo(Room::class);
}

public function criticalRemarks()
{
    return $this->hasMany(CriticalRemark::class);
}

}
