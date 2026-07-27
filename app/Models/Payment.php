<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Payment extends Model
{
    protected $fillable = [
        'user_id', 
        'room_id', 
        'month', 
        'year', 
        'amount', 
        'status', 
        'due_date',
        'clickpesa_transaction_id',
        'clickpesa_response',
        'paid_at',
        'payment_method',
        'confirmed_by_landlord',
        'room_selected',
        'confirmation_message'
    ];

    protected $appends = ['month_name', 'due_date_formatted'];

    protected $casts = [
        'paid_at' => 'datetime',
        'due_date' => 'date',
        'clickpesa_response' => 'array',
        'amount' => 'decimal:2',
        'confirmed_by_landlord' => 'boolean',
        'room_selected' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id', 'id');
    }

    public function getMonthNameAttribute()
    {
        return [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
        ][$this->month] ?? 'Unknown';
    }

    public function getDueDateFormattedAttribute()
    {
        return Carbon::parse($this->due_date)->format('d M Y');
    }

    public function isPaid()
    {
        return $this->status === 'paid';
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isFailed()
    {
        return $this->status === 'failed';
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }
}
