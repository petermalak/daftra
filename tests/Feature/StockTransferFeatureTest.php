<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\Stock;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StockTransferFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_successfully_transfers_stock_between_warehouses()
    {
        // Create test data
        $item = InventoryItem::factory()->create();
        $sourceWarehouse = Warehouse::factory()->create();
        $destinationWarehouse = Warehouse::factory()->create();

        Stock::factory()->create([
            'inventory_item_id' => $item->id,
            'warehouse_id' => $sourceWarehouse->id,
            'quantity' => 20,
        ]);

        // Make the request
        $response = $this->postJson('/stock-transfers', [
            'inventory_item_id' => $item->id,
            'from_warehouse_id' => $sourceWarehouse->id,
            'to_warehouse_id' => $destinationWarehouse->id,
            'quantity' => 5,
            'transferred_by' => 1,
            'notes' => 'Test transfer',
        ]);

        // Assertions
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'data' => [
                'inventory_item_id',
                'from_warehouse_id',
                'to_warehouse_id',
                'quantity',
                'status',
                'notes'
            ]
        ]);

        // Verify database changes
        $this->assertDatabaseHas('stocks', [
            'warehouse_id' => $sourceWarehouse->id,
            'quantity' => 15
        ]);

        $this->assertDatabaseHas('stocks', [
            'warehouse_id' => $destinationWarehouse->id,
            'quantity' => 5
        ]);
    }
}
