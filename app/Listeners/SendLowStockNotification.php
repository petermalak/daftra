<?php

namespace App\Listeners;

use App\Events\LowStockDetected;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendLowStockNotification
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(LowStockDetected $event): void
    {
        $stock = $event->stock;
        $item = $event->inventoryItem;
        $warehouse = $event->warehouse;

        // Log the low stock event
        Log::warning('Low stock detected', [
            'item_id' => $item->id,
            'item_name' => $item->name,
            'sku' => $item->sku,
            'warehouse_id' => $warehouse->id,
            'warehouse_name' => $warehouse->name,
            'current_quantity' => $stock->quantity,
            'minimum_level' => $item->minimum_stock_level,
            'shortage' => $item->minimum_stock_level - $stock->quantity,
        ]);

        // Simulate sending email notification
        $this->sendLowStockEmail($item, $warehouse, $stock);
    }

    /**
     * Simulate sending email notification for low stock.
     */
    private function sendLowStockEmail($item, $warehouse, $stock): void
    {
        $subject = "Low Stock Alert: {$item->name}";
        $message = "
            Low stock detected for item: {$item->name}

            Details:
            - SKU: {$item->sku}
            - Warehouse: {$warehouse->name}
            - Current Quantity: {$stock->quantity}
            - Minimum Level: {$item->minimum_stock_level}
            - Shortage: " . ($item->minimum_stock_level - $stock->quantity) . " units

            Please restock this item as soon as possible.
        ";

        // In a real application, you would use Laravel's Mail facade
        // Mail::to('admin@company.com')->send(new LowStockNotification($item, $warehouse, $stock));

        // For now, just log the email content
        Log::info('Low stock email notification', [
            'subject' => $subject,
            'message' => $message,
            'to' => 'admin@company.com'
        ]);
    }
}
