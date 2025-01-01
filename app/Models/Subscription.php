<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'user_id',
        'subscription_type',
        'amount',
        'payment_method',
        'voucher_code',
        'paid_at',
        'is_paid',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
