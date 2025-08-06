<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InventoryItemController;

Route::get('/', function () {
    return view('welcome');
});

// Inventory Items Routes
Route::prefix('inventory-items')->group(function () {
    Route::get('/', [InventoryItemController::class, 'index'])->name('inventory-items.index');
    Route::get('/create', [InventoryItemController::class, 'create'])->name('inventory-items.create');
    Route::post('/', [InventoryItemController::class, 'store'])->name('inventory-items.store');
    Route::get('/{id}', [InventoryItemController::class, 'show'])->name('inventory-items.show');
    Route::get('/{id}/edit', [InventoryItemController::class, 'edit'])->name('inventory-items.edit');
    Route::put('/{id}', [InventoryItemController::class, 'update'])->name('inventory-items.update');
    Route::delete('/{id}', [InventoryItemController::class, 'destroy'])->name('inventory-items.destroy');
    
    // Additional search and filter routes
    Route::get('/search/autocomplete', [InventoryItemController::class, 'search'])->name('inventory-items.search');
    Route::get('/filters/options', [InventoryItemController::class, 'getFilterOptions'])->name('inventory-items.filter-options');
    Route::get('/low-stock', [InventoryItemController::class, 'lowStock'])->name('inventory-items.low-stock');
    Route::get('/out-of-stock', [InventoryItemController::class, 'outOfStock'])->name('inventory-items.out-of-stock');
});
