<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class WarehouseController extends Controller
{
    /**
     * GET /warehouses
     * List all warehouses
     */
    public function index(Request $request): JsonResponse
    {
        $warehouses = Warehouse::all();
        return response()->json(['data' => $warehouses], 200);
    }

    /**
     * GET /warehouses/{id}/inventory
     * Get inventory for a specific warehouse with caching
     */
    public function inventory($id, Request $request): JsonResponse
    {
        $warehouse = Warehouse::findOrFail($id);
        $perPage = (int) $request->get('per_page', 15);
        $search = $request->get('search');
        $minQuantity = $request->get('min_quantity');
        $maxQuantity = $request->get('max_quantity');

        // Generate cache key based on parameters
        $cacheKey = "warehouse_inventory_{$id}_" . md5(serialize([
            'per_page' => $perPage,
            'search' => $search,
            'min_quantity' => $minQuantity,
            'max_quantity' => $maxQuantity,
            'page' => $request->get('page', 1)
        ]));

        // Cache for 5 minutes (300 seconds)
        $result = Cache::remember($cacheKey, 300, function () use ($warehouse, $perPage, $search, $minQuantity, $maxQuantity, $request) {
            $query = Stock::with(['inventoryItem'])
                ->where('warehouse_id', $warehouse->id);

            if ($search) {
                $query->whereHas('inventoryItem', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('brand', 'like', "%{$search}%")
                      ->orWhere('category', 'like', "%{$search}%");
                });
            }
            if ($minQuantity !== null) {
                $query->where('quantity', '>=', (int)$minQuantity);
            }
            if ($maxQuantity !== null) {
                $query->where('quantity', '<=', (int)$maxQuantity);
            }

            $stocks = $query->paginate($perPage);

            return [
                'warehouse' => $warehouse,
                'data' => $stocks->items(),
                'pagination' => [
                    'current_page' => $stocks->currentPage(),
                    'last_page' => $stocks->lastPage(),
                    'per_page' => $stocks->perPage(),
                    'total' => $stocks->total(),
                    'from' => $stocks->firstItem(),
                    'to' => $stocks->lastItem(),
                ]
            ];
        });

        return response()->json($result, 200);
    }

    /**
     * Clear cache for a specific warehouse's inventory.
     */
    public function clearInventoryCache($id): JsonResponse
    {
        $warehouse = Warehouse::findOrFail($id);

        // Clear all cache keys for this warehouse's inventory
        $keys = Cache::get('warehouse_inventory_keys_' . $id, []);
        foreach ($keys as $key) {
            Cache::forget($key);
        }
        Cache::forget('warehouse_inventory_keys_' . $id);

        return response()->json([
            'message' => 'Warehouse inventory cache cleared successfully.'
        ], 200);
    }
}
