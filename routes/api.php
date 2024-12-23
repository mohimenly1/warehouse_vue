<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DisbursementOrderController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReceiptOrderController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// endpoint
Route::post('/register', [AuthController::class, 'register']);
Route::post('/register-staff', [AuthController::class, 'createWarehouseStaff']);
Route::get('/warehouse-staff', [AuthController::class, 'indexStaff']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::get('/warehouses', [WarehouseController::class, 'index']);
Route::get('/info-warehouses', [WarehouseController::class, 'getInfoWarehouses']);
Route::post('/warehouses', [WarehouseController::class, 'store']);
Route::post('/warehouses/{warehouse_id}/set-limitations', [WarehouseController::class, 'setLimitations']);
Route::get('/get-users-with-warehouses', [WarehouseController::class, 'getAllUsersWithWarehouses']);

// Category Routes
Route::get('/categories', [CategoryController::class, 'index']);
Route::post('/categories', [CategoryController::class, 'store']);

// Product Routes
Route::post('/products', [ProductController::class, 'store']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products-warehouse', [ProductController::class, 'getProductsByWarehouse']);

Route::post('/disbursement', [DisbursementOrderController::class, 'store']);
Route::get('/disbursement-orders', [DisbursementOrderController::class, 'index']);

Route::post('/inventories',[InventoryController::class,'store']);
Route::post('/receipt-orders',[ReceiptOrderController::class,'store']);
Route::get('/receipt-orders',[ReceiptOrderController::class,'index']);
Route::get('/inventories',[InventoryController::class,'getInventoriesByWarehouse']);
Route::get('/warehouses/{id}/statistics', [WarehouseController::class, 'getStatistics']);
Route::get('/statistics-users-warehouses-count',[StatisticsController::class,'getStatistics']);


