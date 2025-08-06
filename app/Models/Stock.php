<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Events\LowStockDetected;

class Stock extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_item_id',
        'warehouse_id',
        'quantity',
        'reserved_quantity',
        'available_quantity',
        'last_updated_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'reserved_quantity' => 'integer',
        'available_quantity' => 'integer',
        'last_updated_at' => 'datetime',
    ];

    /**
     * Get the inventory item for this stock record.
     */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /**
     * Get the warehouse for this stock record.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Calculate available quantity (total - reserved).
     */
    public function getAvailableQuantityAttribute(): int
    {
        return $this->quantity - $this->reserved_quantity;
    }

    /**
     * Boot method to add model events.
     */
    protected static function boot()
    {
        parent::boot();

        static::updated(function ($stock) {
            // Check if stock level is now below minimum
            if ($stock->quantity < $stock->inventoryItem->minimum_stock_level) {
                event(new LowStockDetected($stock, $stock->inventoryItem));
            }
        });
    }
}
