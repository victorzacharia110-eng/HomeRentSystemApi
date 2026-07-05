<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomPhoto extends Model
{
    protected $table = 'room_photos';
    protected $fillable = ['room_id', 'photo'];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
