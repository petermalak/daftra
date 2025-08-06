<?php

namespace App\Http\Controllers;

use App\Models\StockTransfer;
use App\Models\Stock;
use App\Models\Warehouse;
use App\Models\InventoryItem;
use App\Http\Requests\StockTransferRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class StockTransferController extends Controller
{
    /**
     * POST /stock-transfers
     * Transfer stock between warehouses
     */
    public function store(StockTransferRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Transaction for atomicity
        DB::beginTransaction();
        try {
            // Check available stock in source warehouse
            $sourceStock = Stock::where('inventory_item_id', $validated['inventory_item_id'])
                ->where('warehouse_id', $validated['from_warehouse_id'])
                ->lockForUpdate()
                ->first();

            // Decrement source stock
            $sourceStock->quantity -= $validated['quantity'];
            $sourceStock->save();

            // Increment destination stock (or create if not exists)
            $destStock = Stock::firstOrCreate(
                [
                    'inventory_item_id' => $validated['inventory_item_id'],
                    'warehouse_id' => $validated['to_warehouse_id'],
                ],
                [
                    'quantity' => 0,
                    'reserved_quantity' => 0,
                ]
            );
            $destStock->quantity += $validated['quantity'];
            $destStock->save();

            // Create stock transfer record
            $transfer = StockTransfer::create([
                'inventory_item_id' => $validated['inventory_item_id'],
                'from_warehouse_id' => $validated['from_warehouse_id'],
                'to_warehouse_id' => $validated['to_warehouse_id'],
                'quantity' => $validated['quantity'],
                'transfer_date' => now(),
                'status' => 'completed',
                'notes' => $validated['notes'] ?? null,
                'transferred_by' => $validated['transferred_by'] ?? null,
                'approved_by' => null,
                'approved_at' => null,
            ]);

            DB::commit();
            return response()->json([
                'message' => 'Stock transferred successfully.',
                'data' => $transfer->load(['inventoryItem', 'fromWarehouse', 'toWarehouse'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Stock transfer failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
