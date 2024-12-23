<?php

namespace App\Http\Controllers;

use App\Models\DisbursementOrder;
use App\Models\Product;
use App\Models\StorageRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DisbursementOrderController extends Controller
{
    public function index(Request $request)
    {
        $warehouseId = $request->query('warehouse_id'); // Get warehouse_id from query parameters
    
        $ordersQuery = DisbursementOrder::with('items.product');
    
        if ($warehouseId) {
            $ordersQuery->where('warehouse_id', $warehouseId);
        }
    
        $orders = $ordersQuery->get();
    
        return response()->json($orders, 200);
    }
    

    public function store(Request $request)
    {
        $validated = $request->validate([
            'recipient_name' => 'required|string',
            'department' => 'nullable|string',
            'disbursement_reason' => 'nullable|string',
            'warehouse_id' => 'required|exists:warehouses,id',
            'warehouse_name' => 'required|string',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity_disbursed' => 'required|integer|min:1',
            'price_type' => 'required|in:price,wholesale_price', // Add validation for price type
        ]);
    
        DB::beginTransaction();
    
        try {
            $order = DisbursementOrder::create([
                'recipient_name' => $validated['recipient_name'],
                'department' => $validated['department'],
                'disbursement_reason' => $validated['disbursement_reason'],
                'warehouse_id' => $validated['warehouse_id'],
                'warehouse_address' => '',
                'warehouse_name' => $validated['warehouse_name'],
            ]);
    
            $totalCost = 0;
    
            foreach ($validated['items'] as $item) {
                $storageRecord = StorageRecord::where('product_id', $item['product_id'])
                    ->where('warehouse_id', $validated['warehouse_id'])
                    ->first();
    
                if (!$storageRecord || $storageRecord->quantity < $item['quantity_disbursed']) {
                    DB::rollBack();
                    return response()->json([
                        'error' => "Insufficient stock for product ID {$item['product_id']}"
                    ], 400);
                }
    
                $storageRecord->decrement('quantity', $item['quantity_disbursed']);
                StorageRecord::updateProductStatus($item['product_id'], $validated['warehouse_id']);
    
                $product = Product::find($item['product_id']);
                $price = $validated['price_type'] === 'price' ? $product->price : $product->wholesale_price;
    
                $totalCost += $price * $item['quantity_disbursed'];
    
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity_disbursed' => $item['quantity_disbursed'],
                    'product_cost' => $price,
                ]);
            }
    
            $order->update(['total_cost' => $totalCost]);
    
            DB::commit();
            return response()->json(['message' => 'Disbursement order created successfully'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Disbursement error: ', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'An internal server error occurred'], 500);
        }
    }
    

    
    
    

}
