<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $fillable = ['code', 'is_used', 'created_by','amount'];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
