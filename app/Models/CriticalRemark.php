<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CriticalRemark extends Model
{
    protected $table = "critical_remarks";
    protected $fillable = ['user_id', 'reason', 'type', 'active'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
