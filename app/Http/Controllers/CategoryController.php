<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{

    public function index(Request $request)
    {
        // Ensure the request contains the user_id
        $userId = $request->query('warehouse_id'); // 13

        // Fetch categories associated with the provided user_id
        $categories = Category::where('warehouse_id', $userId)->get(); 

        return response()->json([
            'categories' => $categories,
        ]);
    }

public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'user_id' => 'required|exists:users,id',
        'warehouse_id' => 'required|exists:warehouses,id',
    ]);

    Category::create($validated);

    return response()->json(['message' => 'Category created successfully!']);
}


}
