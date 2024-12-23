<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'warehouse_id', // Add this line
        'name',
        'description',
        'image',
        'is_long_term',
        'code', // Add this line
        'status', // Add this line
        'price', // سعر قطاعي
        'wholesale_price' // سعر بجملة
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

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function storageRecords()
    {
        return $this->hasMany(StorageRecord::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function trackingLogs()
    {
        return $this->hasMany(TrackingLog::class);
    }
}
