<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Limit;
use App\Models\StorageRecord;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryController extends Controller
{
    // public function store(Request $request)
    // {
    //     $validated = $request->validate([
    //         'warehouse_id' => 'required|exists:warehouses,id',
    //         'product_id' => 'required|exists:products,id',
    //         'quantity' => 'required|integer|min:1',
    //     ]);

    //     $warehouse = Warehouse::find($validated['warehouse_id']);

    //     if ($warehouse->user_id !== auth()->id()) {
    //         return response()->json(['error' => 'Unauthorized'], 403);
    //     }

    //     Inventory::updateOrCreate(
    //         ['warehouse_id' => $validated['warehouse_id'], 'product_id' => $validated['product_id']],
    //         ['quantity' => DB::raw("quantity + {$validated['quantity']}")]
    //     );

    //     return response()->json(['message' => 'Inventory updated successfully!']);
    // }



    public function getInventoriesByWarehouse(Request $request)
    {
        $warehouseId = $request->query('warehouse_id');
        
        // Validate that warehouse_id is provided
        if (!$warehouseId) {
            return response()->json(['error' => 'Warehouse ID is required'], 400);
        }
    
        // Fetch the latest storage records for the given warehouse
        $inventories = StorageRecord::where('warehouse_id', $warehouseId)
            ->with('product:id,name,price,wholesale_price')  // Load the product name for each storage record
            ->latest('entry_date')     // Order by entry_date to get the latest record first
            ->get()
            ->map(function ($storageRecord) {
                // Log the storage record data for debugging
                Log::debug('Storage Record:', $storageRecord->toArray());
    
                // Format the entry_date and expiry_date
                $entryDate = Carbon::parse($storageRecord->entry_date)->toDateTimeString();
                $expiryDate = $storageRecord->expiry_date ? Carbon::parse($storageRecord->expiry_date)->toDateTimeString() : null;
    
                return [
                    'product_name' => $storageRecord->product->name ?? 'Unknown Product',
                    'quantity' => $storageRecord->quantity,
                    'price' => $storageRecord->product->price,
                    'wholesale_price' => $storageRecord->product->wholesale_price,
                    'entry_date' => $entryDate,
                    'expiry_date' => $expiryDate,
                ];
            });
    
        return response()->json(['inventories' => $inventories], 200);
    }
    
    
    public function store(Request $request)
    {
        // Validate incoming request
        $validatedData = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'quantity' => 'required|integer|min:1',
            'entry_date' => 'required|date',
            'expiry_date' => 'nullable|date|after_or_equal:entry_date',
        ]);
    
        // Extract data from request
        $productId = $validatedData['product_id'];
        $warehouseId = $validatedData['warehouse_id'];
        $quantity = $validatedData['quantity'];
        $entryDate = $validatedData['entry_date'];
        $expiryDate = $validatedData['expiry_date'] ?? null;
    
        // Check if the warehouse has a limit
        $limit = Limit::where('warehouse_id', $warehouseId)->first();
        if ($limit) {
            // Calculate the current total quantity in the warehouse
            $currentTotalQuantity = Inventory::where('warehouse_id', $warehouseId)->sum('quantity');
            
            // Check if adding this quantity exceeds the limit
            if (($currentTotalQuantity + $quantity) > $limit->max_quantity) {
                return response()->json([
                    'message' => 'Adding this quantity exceeds the warehouse limit.',
                ], 400); // Return HTTP 400 Bad Request
            }
        }
    
        // Find inventory record
        $inventory = Inventory::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->first();
    
        if ($inventory) {
            // If inventory exists, increment quantity
            $inventory->quantity += $quantity;
            $inventory->save();
        } else {
            // If inventory does not exist, create a new record
            $inventory = Inventory::create([
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'quantity' => $quantity,
            ]);
        }
    
        // Log the addition in storage records
        StorageRecord::create([
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'quantity' => $quantity,
            'entry_date' => $entryDate,
            'expiry_date' => $expiryDate,
        ]);

            // Update the product status dynamically
    StorageRecord::updateProductStatus($productId, $warehouseId);
    
        return response()->json($inventory, 201); // Return updated inventory with HTTP status 201
    }
    
    
    
}
