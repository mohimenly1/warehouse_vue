<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DisbursementOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_name',
        'warehouse_address',
        'recipient_name',
        'recipient_contact',
        'department',
        'disbursement_reason',
        'total_cost',
        'warehouse_id',
        'payment_method',
        'notes',
    ];

    // Relationships

    /**
     * Products included in the disbursement order.
     */
    public function items()
    {
        return $this->hasMany(DisbursementOrderItem::class);
    }

    /**
     * Adjust stock in the storage_records table after creating a disbursement order.
     */
    public static function boot()
    {
        parent::boot();

        static::created(function ($order) {
            foreach ($order->items as $item) {
                $storageRecord = StorageRecord::where('product_id', $item->product_id)
                    ->where('warehouse_id', $item->warehouse_id)
                    ->first();

                if ($storageRecord) {
                    // Reduce stock in storage_records
                    $storageRecord->decrement('quantity', $item->quantity_disbursed);

                    // Recalculate product status
                    StorageRecord::updateProductStatus($item->product_id, $item->warehouse_id);
                }
            }
        });
    }
}
