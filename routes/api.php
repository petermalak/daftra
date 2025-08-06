<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\InventoryItemController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Add login route for API authentication
Route::post('/login', function (Request $request) {
    $credentials = $request->only('email', 'password');
    if (Auth::attempt($credentials)) {
        $user = Auth::user();
        $token = $user->createToken('api-token')->plainTextToken;
        return response()->json(['token' => $token]);
    }
    return response()->json(['message' => 'Invalid credentials'], 401);
});

// Inventory listing per warehouse (with filters)
Route::get('/inventory', [InventoryController::class, 'index']);

// Stock transfers (admin only)
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::post('/stock-transfers', [StockTransferController::class, 'store']);
});

// Inventory item create (admin, manager)
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::post('/inventory-items', [InventoryItemController::class, 'store']);
    Route::put('/inventory-items/{id}', [InventoryItemController::class, 'update']);
});

// Inventory item delete (admin only)
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::delete('/inventory-items/{id}', [InventoryItemController::class, 'destroy']);
});

// Warehouses
Route::get('/warehouses', [WarehouseController::class, 'index']);
Route::get('/warehouses/{id}/inventory', [WarehouseController::class, 'inventory']);
Route::delete('/warehouses/{id}/inventory/cache', [WarehouseController::class, 'clearInventoryCache']);
