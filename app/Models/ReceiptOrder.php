<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceiptOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'warehouse_name',
        'warehouse_address',
        'supplier_name',
        'supplier_address',
        'supplier_contact',
        'order_number',
        'receipt_date',
        'notes',
    ];

    // Relationships

    /**
     * Items in the receipt order.
     */
    public function items()
    {
        return $this->hasMany(ReceiptOrderItem::class, 'receipt_order_id', 'id');
    }
    
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
    
    /**
     * Adjust stock in the storage_records table when a receipt order is created.
     */
    public static function boot()
    {
        parent::boot();

        static::created(function ($order) {
            foreach ($order->items as $item) {
                $storageRecord = StorageRecord::firstOrCreate(
                    [
                        'product_id' => $item->product_id,
                        'warehouse_id' => $item->warehouse_id,
                    ],
                    [
                        'entry_date' => now(),
                        'expiry_date' => $item->expiration_date,
                        'quantity' => 0,
                    ]
                );

                // Increment stock in storage_records
                $storageRecord->increment('quantity', $item->quantity_received);

                // Recalculate product status
                StorageRecord::updateProductStatus($item->product_id, $item->warehouse_id);
            }
        });
    }
}
