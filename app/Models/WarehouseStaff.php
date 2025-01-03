<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseStaff extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'user_id',
    ];

    // Relationships
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
