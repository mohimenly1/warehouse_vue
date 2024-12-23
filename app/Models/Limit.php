<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Limit extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'max_products',
        'max_quantity',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
