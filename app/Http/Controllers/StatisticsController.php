<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class StatisticsController extends Controller
{
    /**
     * Fetch statistics like the count of warehouses and users.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatistics()
    {
        // Fetch the statistics
        $warehouseCount = Warehouse::count();
        $userCount = User::count();
        $productCount = Product::count();

        // Return the data as JSON response
        return response()->json([
            'warehouses_count' => $warehouseCount,
            'users_count' => $userCount,
            'product_count' => $productCount,
        ]);
    }
}