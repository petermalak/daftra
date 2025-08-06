<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InventoryController extends Controller
{
    /**
     * GET /inventory
     * Paginated list of inventory per warehouse (with filters)
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['warehouse_id', 'item_id', 'search', 'min_quantity', 'max_quantity', 'per_page']);
        $perPage = (int) ($filters['per_page'] ?? 15);

        $query = Stock::with(['inventoryItem', 'warehouse']);

        if (!empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }
        if (!empty($filters['item_id'])) {
            $query->where('inventory_item_id', $filters['item_id']);
        }
        if (!empty($filters['search'])) {
            $query->whereHas('inventoryItem', function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('sku', 'like', "%{$filters['search']}%")
                  ->orWhere('description', 'like', "%{$filters['search']}%")
                  ->orWhere('brand', 'like', "%{$filters['search']}%")
                  ->orWhere('category', 'like', "%{$filters['search']}%")
                  ->orWhere('brand', 'like', "%{$filters['search']}%")
                  ->orWhere('category', 'like', "%{$filters['search']}%");
            });
        }
        if (isset($filters['min_quantity'])) {
            $query->where('quantity', '>=', (int)$filters['min_quantity']);
        }
        if (isset($filters['max_quantity'])) {
            $query->where('quantity', '<=', (int)$filters['max_quantity']);
        }

        $stocks = $query->paginate($perPage);

        return response()->json([
            'data' => $stocks->items(),
            'pagination' => [
                'current_page' => $stocks->currentPage(),
                'last_page' => $stocks->lastPage(),
                'per_page' => $stocks->perPage(),
                'total' => $stocks->total(),
                'from' => $stocks->firstItem(),
                'to' => $stocks->lastItem(),
            ]
        ], 200);
    }
}
