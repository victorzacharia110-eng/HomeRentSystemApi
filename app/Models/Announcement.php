<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Announcement extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'message'
    ];

    /**
     * The user who created the announcement
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getCreatedAtFormattedAttribute()
{
    return Carbon::parse($this->created_at)->format('d M Y');
}
}