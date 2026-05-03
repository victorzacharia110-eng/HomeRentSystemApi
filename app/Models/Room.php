<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = ['user_id', 'room_price', 'room_number', 'type', 'status', 'photo'];


    public function user()
    {
        return $this->hasMany(User::class);
    }

public function latestPayment()
{
    return $this->hasOne(Payment::class)->latestOfMany();
}
}
