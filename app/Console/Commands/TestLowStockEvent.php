<?php

namespace App\Console\Commands;

use App\Models\Stock;
use App\Models\InventoryItem;
use Illuminate\Console\Command;

class TestLowStockEvent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:low-stock-event {item_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the low stock event by reducing stock levels';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $itemId = $this->argument('item_id');

        if ($itemId) {
            $item = InventoryItem::find($itemId);
            if (!$item) {
                $this->error("Item with ID {$itemId} not found.");
                return 1;
            }
            $items = collect([$item]);
        } else {
            $items = InventoryItem::where('is_active', true)->take(5)->get();
        }

        $this->info('Testing low stock events...');

        foreach ($items as $item) {
            $stocks = $item->stocks;

            if ($stocks->isEmpty()) {
                $this->warn("No stock records found for item: {$item->name}");
                continue;
            }

            foreach ($stocks as $stock) {
                $this->info("Testing item: {$item->name} in warehouse: {$stock->warehouse->name}");
                $this->info("Current quantity: {$stock->quantity}, Minimum level: {$item->minimum_stock_level}");

                if ($stock->quantity > $item->minimum_stock_level) {
                    // Reduce stock to trigger low stock event
                    $newQuantity = max(0, $item->minimum_stock_level - 1);
                    $stock->quantity = $newQuantity;
                    $stock->save();

                    $this->info("Reduced stock to {$newQuantity} - Low stock event should be triggered!");
                } else {
                    $this->warn("Stock already below minimum level.");
                }
            }
        }

        $this->info('Low stock event test completed. Check logs for event details.');
        return 0;
    }
}
