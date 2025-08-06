<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestInventorySystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-inventory-system';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the inventory system database setup';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Inventory System Database Setup...');
        
        // Test warehouses
        $warehouseCount = \App\Models\Warehouse::count();
        $this->info("Warehouses: {$warehouseCount}");
        
        // Test inventory items
        $itemCount = \App\Models\InventoryItem::count();
        $this->info("Inventory Items: {$itemCount}");
        
        // Test stocks
        $stockCount = \App\Models\Stock::count();
        $this->info("Stock Records: {$stockCount}");
        
        // Test stock transfers
        $transferCount = \App\Models\StockTransfer::count();
        $this->info("Stock Transfers: {$transferCount}");
        
        // Show some sample data
        $this->info("\nSample Data:");
        $this->info("First Warehouse: " . \App\Models\Warehouse::first()?->name);
        $this->info("First Item: " . \App\Models\InventoryItem::first()?->name);
        $this->info("Total Stock Quantity: " . \App\Models\Stock::sum('quantity'));
        
        $this->info("\nDatabase setup completed successfully!");
    }
}
