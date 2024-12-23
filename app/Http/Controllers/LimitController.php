<?php

namespace App\Http\Controllers;

use App\Models\Limit;
use Illuminate\Http\Request;

class LimitController extends Controller
{
    public function storeOrUpdateLimit(Request $request)
{
    $validated = $request->validate([
        'warehouse_id' => 'required|exists:warehouses,id',
        'max_products' => 'required|integer|min:0',
        'max_quantity' => 'required|integer|min:0',
    ]);

    $limit = Limit::updateOrCreate(
        ['warehouse_id' => $validated['warehouse_id']],
        $validated
    );

    return response()->json(['message' => 'Limits updated successfully!', 'limit' => $limit]);
}

}
