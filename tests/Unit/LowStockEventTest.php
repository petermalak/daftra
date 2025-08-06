<?php

namespace Tests\Unit;

use App\Events\LowStockDetected;
use App\Models\InventoryItem;
use App\Models\Stock;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LowStockEventTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_fires_low_stock_event_when_stock_falls_below_threshold()
    {
        // Ensure the column exists for SQLite
        Schema::table('inventory_items', function ($table) {
            if (!Schema::hasColumn('inventory_items', 'low_stock_threshold')) {
                $table->integer('low_stock_threshold')->default(0);
            }
        });

        Event::fake();
        Queue::fake();

        $item = InventoryItem::factory()->create(['low_stock_threshold' => 10]);
        $sourceWarehouse = Warehouse::factory()->create();
        $destinationWarehouse = Warehouse::factory()->create();

        Stock::factory()->create([
            'inventory_item_id' => $item->id,
            'warehouse_id' => $sourceWarehouse->id,
            'quantity' => 15,
        ]);

        $this->postJson('/api/stock-transfers', [
            'inventory_item_id' => $item->id,
            'from_warehouse_id' => $sourceWarehouse->id,
            'to_warehouse_id' => $destinationWarehouse->id,
            'quantity' => 6,
            'transferred_by' => 1,
        ]);

        Event::assertDispatched(LowStockDetected::class, function ($event) use ($item, $sourceWarehouse) {
            return $event->inventoryItem->id === $item->id
                && $event->warehouse->id === $sourceWarehouse->id
                && $event->currentQuantity === 9;
        });

        Queue::assertPushed(function ($job) {
            return $job->getName() === 'App\Listeners\HandleLowStockNotification';
        });
    }
}
