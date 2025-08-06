<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Services\InventorySearchService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InventoryItemController extends Controller
{
    protected $searchService;

    public function __construct(InventorySearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * Display a listing of the resource with search and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search',
            'category',
            'brand',
            'min_stock',
            'max_stock',
            'status',
            'stock_status',
            'sort_by',
            'sort_order',
            'per_page'
        ]);

        // Convert string numbers to integers
        if (isset($filters['min_stock'])) {
            $filters['min_stock'] = (int) $filters['min_stock'];
        }
        if (isset($filters['max_stock'])) {
            $filters['max_stock'] = (int) $filters['max_stock'];
        }

        $perPage = (int) ($filters['per_page'] ?? 15);
        $items = $this->searchService->advancedSearch($filters, $perPage);

        $categories = InventoryItem::getCategories();
        $brands = InventoryItem::getBrands();

        return response()->json([
            'data' => $items->items(),
            'pagination' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'from' => $items->firstItem(),
                'to' => $items->lastItem(),
            ],
            'filters' => [
                'categories' => $categories,
                'brands' => $brands,
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): JsonResponse
    {
        return response()->json([
            'message' => 'This endpoint is for displaying the create form, which is not implemented in this controller.'
        ], 501); // Not Implemented
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:255|unique:inventory_items,sku',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:255',
            'unit_of_measure' => 'required|string|max:50',
            'minimum_stock_level' => 'required|integer|min:0',
            'maximum_stock_level' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $item = InventoryItem::create($validated);

        return response()->json([
            'message' => 'Inventory item created successfully',
            'data' => $item->load('stocks.warehouse')
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $item = InventoryItem::with(['stocks.warehouse', 'stockTransfers'])->findOrFail($id);

        return response()->json([
            'data' => $item,
            'total_quantity' => $item->total_quantity,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id): JsonResponse
    {
        return response()->json([
            'message' => 'This endpoint is for displaying the edit form, which is not implemented in this controller.'
        ], 501); // Not Implemented
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $item = InventoryItem::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:255|unique:inventory_items,sku,' . $id,
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:255',
            'unit_of_measure' => 'required|string|max:50',
            'minimum_stock_level' => 'required|integer|min:0',
            'maximum_stock_level' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $item->update($validated);

        return response()->json([
            'message' => 'Inventory item updated successfully',
            'data' => $item->load('stocks.warehouse')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $item = InventoryItem::findOrFail($id);
        $item->delete();

        return response()->json([
            'message' => 'Inventory item deleted successfully'
        ]);
    }

    /**
     * Get filter options for the frontend.
     */
    public function getFilterOptions(): JsonResponse
    {
        return response()->json([
            'categories' => InventoryItem::getCategories(),
            'brands' => InventoryItem::getBrands(),
        ]);
    }

    /**
     * Search items with autocomplete functionality.
     */
    public function search(Request $request): JsonResponse
    {
        $search = $request->get('q', '');
        $limit = (int) $request->get('limit', 10);

        $items = InventoryItem::search($search)
            ->active()
            ->select('id', 'name', 'sku', 'category', 'brand')
            ->limit($limit)
            ->get();

        return response()->json([
            'data' => $items
        ]);
    }

    /**
     * Get items with low stock.
     */
    public function lowStock(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 15);
        $items = InventoryItem::lowStock()
            ->with(['stocks.warehouse'])
            ->paginate($perPage);

        return response()->json([
            'data' => $items->items(),
            'pagination' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ]
        ]);
    }

    /**
     * Get items that are out of stock.
     */
    public function outOfStock(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 15);
        $items = InventoryItem::outOfStock()
            ->with(['stocks.warehouse'])
            ->paginate($perPage);

        return response()->json([
            'data' => $items->items(),
            'pagination' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ]
        ]);
    }
}
