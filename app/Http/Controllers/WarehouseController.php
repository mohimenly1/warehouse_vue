<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Limit;
use App\Models\Product;
use App\Models\StorageRecord;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WarehouseController extends Controller
{

    public function getStatistics($id)
    {
        $warehouse = Warehouse::with('limits')->find($id);

        if (!$warehouse) {
            return response()->json(['error' => 'Warehouse not found'], 404);
        }

        // Get values from the limits table
        $maxProducts = $warehouse->limits?->max_products ?? 0;
        $maxQuantity = $warehouse->limits?->max_quantity ?? 0;

        // Total count of products in the products table for the warehouse
        $totalProducts = Product::where('warehouse_id', $id)->count();

        // Total count of products in the storage_records table for the warehouse
        $totalStorageRecords = StorageRecord::where('warehouse_id', $id)->count();

        // Total quantity of all products in the storage_records table for the warehouse
        $totalQuantity = StorageRecord::where('warehouse_id', $id)->sum('quantity');
        $totalCategories = Category::where('warehouse_id', $id)->count();
        // Prepare the statistics data
        $statistics = [
            'max_products' => $maxProducts,
            'total_products' => $totalProducts,
            'max_quantity' => $maxQuantity,
            'total_storage_records' => $totalStorageRecords,
            'total_quantity' => $totalQuantity,
            'total_categories' => $totalCategories, // Add total categories
        ];

        return response()->json($statistics, 200);
    }

    // public function getInfoWarehouses()
    // {
    //     $warehouses = Warehouse::with([
    //             'staff',
    //             'storageRecords.product',
    //             'user',
    //             'limits'
    //         ])
    //         ->withCount([
    //             'storageRecords as product_count' => function ($query) {
    //                 $query->select(DB::raw('count(DISTINCT product_id)'));
    //             }
    //         ])
    //         ->withSum('storageRecords as total_quantity', 'quantity')
    //         ->get();
    
    //     return response()->json($warehouses);
    // }



    public function getInfoWarehouses()
{
    $warehouses = Warehouse::with([
            'staff',
            'storageRecords.product',
            'user',
            'limits'
        ])
        ->withCount([
            'storageRecords as product_count' => function ($query) {
                $query->select(DB::raw('count(DISTINCT product_id)'));
            }
        ])
        ->withSum('storageRecords as total_quantity', 'quantity')
        ->withCount([
            'products as total_products' => function ($query) {
                // Count all products associated with each warehouse
                $query->select(DB::raw('count(*)'));
            }
        ])
        ->get();

    return response()->json($warehouses);
}

    
    

    public function setLimitations(Request $request, $warehouseId)
{
    $request->validate([
        'max_products' => 'required|integer|min:0',
        'max_quantity' => 'required|integer|min:0',
    ]);

    $limit = Limit::updateOrCreate(
        ['warehouse_id' => $warehouseId],
        [
            'max_products' => $request->input('max_products'),
            'max_quantity' => $request->input('max_quantity'),
        ]
    );

    return response()->json(['message' => 'Limitations updated successfully', 'data' => $limit]);
}
    
    public function index()
{
    $warehouses = Warehouse::where('user_id', auth()->id())->get();

    return response()->json([
        'warehouses' => $warehouses,
    ]);
}

public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'location' => 'required|string|max:255',
        'user_id' => 'required|integer|exists:users,id',
    ]);

    $warehouse = Warehouse::create([
        'name' => $validated['name'],
        'location' => $validated['location'],
        'user_id' => $validated['user_id'], // Associate the warehouse with the logged-in user
    ]);

    return response()->json([
        'warehouse_id' => $warehouse->id,
        'warehouse_name' => $warehouse->name,
    ], 201);
}













// In your WarehouseController or UserController (depending on your structure)

public function getAllUsersWithWarehouses()
{
    // Fetch all users with their warehouse information
    $users = User::with(['warehouses.warehouse', 'warehouses.warehouse.user'])->get();

    // Prepare response structure
    $userData = $users->map(function ($user) {
        $warehouses = $user->warehouses->map(function ($warehouseStaff) {
            return [
                'warehouse_id' => $warehouseStaff->warehouse->id,
                'warehouse_name' => $warehouseStaff->warehouse->name,
                'warehouse_owner' => $warehouseStaff->warehouse->user->id === $warehouseStaff->user_id,
                'staff_user' => $warehouseStaff->user->id
            ];
        });

        return [
            'user_id' => $user->id,
            'name' => $user->name,
            'user_type' => $user->user_type,
            'warehouses' => $warehouses
        ];
    });

    return response()->json($userData);
}


}
