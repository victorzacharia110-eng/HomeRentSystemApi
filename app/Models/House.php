<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class House extends Model
{
    protected $fillable = ['user_id','house_name','total_rooms','address','description'] ;
}
