<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Comment extends Model
{
    protected $fillable = [
        'user_id',
        'comment',
        'rating',
        
    ];


    /**
     * The user who wrote the comment
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }


}
