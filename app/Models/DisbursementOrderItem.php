<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DisbursementOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'disbursement_order_id',
        'product_id',
        'warehouse_id',
        'quantity_disbursed',
        'product_cost',
    ];

    // Relationships

    /**
     * The disbursement order to which this item belongs.
     */
    public function disbursementOrder()
    {
        return $this->belongsTo(DisbursementOrder::class);
    }

    /**
     * The product being disbursed.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * The warehouse from which the product is being disbursed.
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
