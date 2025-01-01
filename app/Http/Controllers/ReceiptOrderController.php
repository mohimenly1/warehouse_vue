<?php

namespace App\Http\Controllers;

use App\Models\Limit;
use App\Models\Product;
use App\Models\ReceiptOrder;
use App\Models\ReceiptOrderItem;
use App\Models\StorageRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReceiptOrderController extends Controller
{

    public function index(Request $request)
    {
        // Ensure the relationship 'items' is correctly defined in the ReceiptOrder model
        $query = ReceiptOrder::with(['items.product', 'items.warehouse']);
    
        // Filter by warehouse_id using receipt_order_id in the receipt_order_items table
        if ($request->has('warehouse_id')) {
            $warehouseId = $request->input('warehouse_id');
            $query->whereHas('items', function ($query) use ($warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            });
        }
    
        // Search functionality
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($query) use ($search) {
                $query->where('supplier_name', 'like', '%' . $search . '%')
                      ->orWhere('order_number', 'like', '%' . $search . '%');
            });
        }
    
        // Pagination
        $receiptOrders = $query->paginate($request->input('per_page', 10));
    
        return response()->json($receiptOrders);
    }
    
    

    
    public function store(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'warehouse_name' => 'required|string|max:255',
            'supplier_name' => 'required|string|max:255',
            'supplier_address' => 'required|string|max:255',
            'supplier_contact' => 'nullable|string|max:255',
            'order_number' => 'required|unique:receipt_orders,order_number',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array',
            'items.*.product_name' => 'required|string|max:255',
            'items.*.product_type' => 'required|in:Wholesale,Retail',
            'items.*.quantity_received' => 'required|numeric|min:1',
            'items.*.wholesale_price' => 'required|numeric|min:0',
            'items.*.retail_price' => 'required|numeric|min:0',
            'items.*.batch_number' => 'nullable|string|max:255',
            'items.*.expiration_date' => 'nullable|date',
        ]);
    
        // Wrap the operation in a transaction
        return DB::transaction(function () use ($request) {
            $warehouseId = $request->warehouse_id;
    
            // Fetch the warehouse limits
            $limit = Limit::where('warehouse_id', $warehouseId)->first();
            if (!$limit) {
                return response()->json(['error' => 'No limits defined for this warehouse.'], 422);
            }
    
            // Check the current product count in the warehouse
            $currentProductCount = Product::where('warehouse_id', $warehouseId)->count();
            if ($currentProductCount >= $limit->max_products) {
                abort(422, 'Warehouse product limit reached!');
            }
            
            $currentTotalQuantity = StorageRecord::where('warehouse_id', $warehouseId)->sum('quantity');
            $newTotalQuantity = $currentTotalQuantity + array_sum(array_column($request->items, 'quantity_received'));
            if ($newTotalQuantity > $limit->max_quantity) {
                abort(422, 'Warehouse quantity limit exceeded!');
            }
            
    
            // Create the receipt order
            $receiptOrder = ReceiptOrder::create($request->only([
                'warehouse_id',
                'warehouse_name',
                'supplier_name',
                'supplier_address',
                'supplier_contact',
                'order_number',
                'notes',
            ]));
    
            // Process each item in the order
            foreach ($request->items as $itemData) {
                $product = Product::firstOrCreate(
                    ['name' => $itemData['product_name']],
                    [
                        'category_id' => $itemData['category_id'] ?? null,
                        'warehouse_id' => $warehouseId,
                        'user_id' => $request->user_id ?? null,
                        'description' => 'Created from Receipt Order',
                        'wholesale_price' => $itemData['wholesale_price'],
                        'price' => $itemData['retail_price'],
                        'status' => 'instock',
                    ]
                );
    
                ReceiptOrderItem::create([
                    'receipt_order_id' => $receiptOrder->id,
                    'product_id' => $product->id,
                    'quantity_received' => $itemData['quantity_received'],
                    'wholesale_price' => $itemData['wholesale_price'],
                    'retail_price' => $itemData['retail_price'],
                    'batch_number' => $itemData['batch_number'],
                    'expiration_date' => $itemData['expiration_date'],
                    'product_type' => $itemData['product_type'],
                ]);
    
                $storageRecord = StorageRecord::firstOrCreate(
                    [
                        'product_id' => $product->id,
                        'warehouse_id' => $warehouseId,
                    ],
                    [
                        'entry_date' => now(),
                        'expiry_date' => $itemData['expiration_date'],
                        'quantity' => 0,
                    ]
                );
    
                $storageRecord->increment('quantity', $itemData['quantity_received']);
                StorageRecord::updateProductStatus($product->id, $warehouseId);
            }
    
            return response()->json(['message' => 'Receipt order created successfully'], 201);
        }, 5); // Retry 5 times in case of deadlock
    }
    
    
    
    
    
    

}
