<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LatePaymentReason extends Model
{
    protected $table = "late_payment_reasons";
    protected $fillable = ['user_id','payment_id','reason_text'] ;

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
