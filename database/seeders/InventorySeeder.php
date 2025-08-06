<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create warehouses
        $warehouses = \App\Models\Warehouse::factory(5)->create();
        
        // Create inventory items
        $inventoryItems = \App\Models\InventoryItem::factory(20)->create();
        
        // Create stock records for each item in each warehouse
        foreach ($inventoryItems as $item) {
            foreach ($warehouses as $warehouse) {
                \App\Models\Stock::factory()->create([
                    'inventory_item_id' => $item->id,
                    'warehouse_id' => $warehouse->id,
                ]);
            }
        }
        
        // Create some stock transfers
        \App\Models\StockTransfer::factory(10)->create();
    }
}
