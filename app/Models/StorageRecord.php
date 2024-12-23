<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class StorageRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'quantity',
        'entry_date',
        'expiry_date',
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }


     // Method to recalculate product status
     public static function updateProductStatus($productId, $warehouseId)
     {
         // Calculate the total quantity of the product in the warehouse
         Log::info('Warehouse ID in updateProductStatus: ' . $warehouseId);
         $totalQuantity = StorageRecord::where('product_id', $productId)
             ->where('warehouse_id', $warehouseId)
             ->sum('quantity');
     
         // Determine the status based on quantity
         $status = match (true) {
             $totalQuantity == 0 => 'outofstock',
             $totalQuantity <= 10 => 'lowstock',
             $totalQuantity > 10 => 'instock', // Fix for "instock" status
         };
     
         // Update the product status
         Product::where('id', $productId)
             ->update(['status' => $status]);
     }
     
}
