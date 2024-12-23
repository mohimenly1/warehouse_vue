<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ReceiptOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'receipt_order_id',
        'product_id',
        'warehouse_id',
        'quantity_received',
        'wholesale_price',
        'retail_price',
        'batch_number',
        'expiration_date',
        'product_type',
    ];

    // Relationships

    /**
     * The receipt order to which this item belongs.
     */
    public function receiptOrder()
    {
        return $this->belongsTo(ReceiptOrder::class);
    }

    /**
     * The product being received.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * The warehouse where the product is being stored.
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    // Stock Update

    /**
     * Increase stock in the storage_records table after creating a receipt order item.
     */

     public static function boot(): void
     {
         parent::boot();
     
         static::created(function ($item) {
             // Log the entire item for debugging
             Log::info('ReceiptOrderItem created:', $item->toArray());
     
             // Ensure related product data is loaded
             $item->load('product');
     
             // Validate warehouse_id
             if (!$item->warehouse_id) {
                 Log::error('Warehouse ID is missing for ReceiptOrderItem ID: ' . $item->id);
                 return;
             }
     
             // Create or update product
             $product = Product::updateOrCreate(
                 ['id' => $item->product_id],
                 [
                     'name' => $item->product->name ?? 'Unknown Product',
                     'category_id' => $item->product->category_id ?? null,
                     'warehouse_id' => $item->warehouse_id,
                     'wholesale_price' => $item->wholesale_price,
                     'price' => $item->retail_price,
                     'status' => 'instock',
                 ]
             );
     
             // Log product creation
             Log::info('Product updated/created:', $product->toArray());
     
             // Update stock in storage_records
             $storageRecord = StorageRecord::firstOrCreate(
                 [
                     'product_id' => $product->id,
                     'warehouse_id' => $item->warehouse_id,
                 ],
                 [
                     'entry_date' => now(),
                     'expiry_date' => $item->expiration_date,
                     'quantity' => 0,
                 ]
             );
     
             $storageRecord->increment('quantity', $item->quantity_received);
     
             // Log storage record update
             Log::info('StorageRecord updated/created:', $storageRecord->toArray());
     
             // Update product status
             StorageRecord::updateProductStatus($product->id, $item->warehouse_id);
     
             Log::info('Product status updated for product ID: ' . $product->id);
         });
     }
     
}
