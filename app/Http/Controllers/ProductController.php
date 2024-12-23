<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Limit;
use App\Models\Product;
use App\Models\StorageRecord;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $warehouse_id = $request->query('warehouse_id');
    
        $products = Product::where('warehouse_id', $warehouse_id)
            ->with(['category', 'user'])
            ->get();
    
        return response()->json($products);
    }
    

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'warehouse_id' => 'required|exists:warehouses,id', // Validate warehouse_id
            'is_long_term' => 'boolean',
            'image' => 'nullable|image|max:2048',
            'code' => 'nullable|string|unique:products,code',
            // 'status' => 'required|in:instock,lowstock,outofstock',
            'user_id' => 'required|exists:users,id',
            'price' => 'nullable|numeric|min:0', // Added validation for price
        ]);
    
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('product-images', 'public');
        }
    
        // Get the warehouse
        $warehouse = Warehouse::findOrFail($validated['warehouse_id']);
    
        // Check if the warehouse has a record in the limits table
        $limit = Limit::where('warehouse_id', $warehouse->id)->first();
        if (!$limit) {
            return response()->json(['error' => '.يجب تحديد عدد المنتجات اولاً لهذا المخزن من قبل المسؤول'], 422);
        }
    
        // Count distinct products already in the warehouse
        $currentProductCount = Product::where('warehouse_id', $warehouse->id)->count();
    
        // Check if adding this product exceeds the limit
        if ($currentProductCount >= $limit->max_products) {
            return response()->json(['error' => 'Warehouse product limit reached!'], 422);
        }
    
        // If all validations pass, create the product
        Product::create($validated);
    
        return response()->json(['message' => 'Product added successfully!'], 201);
    }
    
    

    public function getProductsByWarehouse(Request $request)
    {
        $warehouseId = $request->query('id'); // Use query() for ?id=value
        if (!$warehouseId) {
            Log::error("Warehouse ID is missing in the request");
            return response()->json(['error' => 'Warehouse ID is required'], 400);
        }
    
        Log::info("Requested warehouse ID: $warehouseId");
    
        $records = StorageRecord::where('warehouse_id', $warehouseId)
            ->groupBy('product_id')
            ->selectRaw('product_id, SUM(quantity) as total_quantity')
            ->with('product')
            ->get();
    
        Log::info("Records fetched: ", $records->toArray());
    
        return $records->map(function ($record) {
            if ($record->product) {
                return [
                    'id' => $record->product->id,
                    'name' => $record->product->name,
                    'code' => $record->product->code,
                    'available_quantity' => $record->total_quantity,
                ];
            } else {
                Log::warning("Product missing for record ID: {$record->id}");
                return null;
            }
        })->filter(); // Remove null values
    }
    
    
    





//     public function store(Request $request)
// {
//     $validated = $request->validate([
//         'name' => 'required|string|max:255',
//         'description' => 'nullable|string',
//         'category_id' => 'required|exists:categories,id',
//         'warehouse_id' => 'required|exists:warehouses,id', // Validate warehouse_id
//         'is_long_term' => 'boolean',
//         'image' => 'nullable|image|max:2048',
//         'code' => 'nullable|string|unique:products,code',
//         'status' => 'required|in:instock,lowstock,outofstock',
//         'user_id' => 'required|exists:users,id',
//         'price' => 'nullable|numeric|min:0', // Added validation for price
//     ]);

//     $warehouse = Warehouse::find($validated['warehouse_id']);
//     $warehouseLimit = $warehouse->getStorageLimit(); // Assumes this method returns the limit

//     // Calculate current total quantity in the warehouse
//     $currentTotalQuantity = Inventory::where('warehouse_id', $warehouse->id)->sum('quantity');

//     if ($currentTotalQuantity >= $warehouseLimit) {
//         return response()->json([
//             'error' => 'This warehouse has reached its storage limit and cannot accommodate more products.'
//         ], 400);
//     }

//     if ($request->hasFile('image')) {
//         $validated['image'] = $request->file('image')->store('product-images', 'public');
//     }

//     $product = Product::create($validated);

//     // Add product to inventory (if applicable)
//     // $this->addToInventory($warehouse->id, $product->id);

//     return response()->json(['message' => 'Product added successfully!']);
// }

    

}
