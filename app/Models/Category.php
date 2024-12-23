<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'user_id', // Add user_id to the fillable fields
        'warehouse_id'
    ];

    // Relationships
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
