<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'location',
        'user_id', // Add this line
    ];

    // Relationships
    public function storageRecords()
    {
        return $this->hasMany(StorageRecord::class);
    }

    public function inventory()
    {
        return $this->hasMany(Inventory::class);
    }

    public function staff()
    {
        return $this->hasMany(WarehouseStaff::class, 'warehouse_id');
    }
    
    public function limits()
{
    return $this->hasOne(Limit::class);
}


public function user()
{
    return $this->belongsTo(User::class);
}

public function products()
{
    return $this->hasMany(Product::class);
}

}
