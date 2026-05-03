<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $fillable = [
        "user_id",
        "airtel_money_number",
        "m_pesa_number",
        "mixx_by_yas_number",
        "halopesa_number",
        "nmb_account_number",
        "crdb_account_number",
        "nbc_account_number",
    ];
}
