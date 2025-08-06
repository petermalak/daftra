<?php

namespace Tests\Unit;

use App\Models\InventoryItem;
use App\Models\Stock;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StockTransferLogicTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_prevents_transfer_when_source_has_insufficient_stock()
    {
        $item = InventoryItem::factory()->create();
        $sourceWarehouse = Warehouse::factory()->create();
        $destinationWarehouse = Warehouse::factory()->create();

        Stock::factory()->create([
            'inventory_item_id' => $item->id,
            'warehouse_id' => $sourceWarehouse->id,
            'quantity' => 10,
        ]);

        $response = $this->postJson('/api/stock-transfers', [
            'inventory_item_id' => $item->id,
            'from_warehouse_id' => $sourceWarehouse->id,
            'to_warehouse_id' => $destinationWarehouse->id,
            'quantity' => 15,
            'transferred_by' => 1,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['quantity']);

        $this->assertEquals(10, $sourceWarehouse->stocks()->first()->quantity);
        $this->assertNull($destinationWarehouse->stocks()->first());
    }
}
