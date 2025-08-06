# Laravel Features Implementation

This document describes the Laravel features implemented in the Inventory Management System.

## 1. Eloquent Models

### Models Created
- **Warehouse**: Manages warehouse information and relationships
- **InventoryItem**: Handles item metadata and specifications
- **Stock**: Links items to warehouses with quantity tracking
- **StockTransfer**: Manages stock movements between warehouses

### Key Features
- **Relationships**: Properly defined relationships between models
- **Accessors/Mutators**: Custom attributes like `total_quantity` and `available_quantity`
- **Scopes**: Query scopes for filtering (search, byCategory, byBrand, etc.)
- **Events**: Model events for low stock detection

## 2. Validation

### Form Request Classes

#### StockTransferRequest
```php
// Validation Rules
- inventory_item_id: required, exists in inventory_items (active only)
- from_warehouse_id: required, exists in warehouses (active only), different from destination
- to_warehouse_id: required, exists in warehouses (active only), different from source
- quantity: required, integer, min:1, max:999999
- notes: nullable, string, max:1000
- transferred_by: nullable, exists in users

// Custom Validation
- Checks if sufficient stock is available in source warehouse
- Validates that warehouses and items are active
- Ensures source and destination warehouses are different
```

#### InventoryItemRequest
```php
// Validation Rules
- name: required, string, max:255
- sku: required, string, max:255, unique (ignoring current item for updates)
- description: nullable, string, max:1000
- category: nullable, string, max:255
- brand: nullable, string, max:255
- unit_of_measure: required, string, max:50
- minimum_stock_level: required, integer, min:0
- maximum_stock_level: nullable, integer, min:0, must be greater than minimum
- is_active: boolean

// Custom Validation
- Ensures maximum stock level is greater than minimum stock level
```

### Usage in Controllers
```php
// StockTransferController
public function store(StockTransferRequest $request): JsonResponse
{
    $validated = $request->validated();
    // Process validated data...
}

// InventoryItemController
public function store(InventoryItemRequest $request): JsonResponse
{
    $validated = $request->validated();
    // Process validated data...
}
```

## 3. Caching

### Warehouse Inventory Caching
```php
// Cache Key Generation
$cacheKey = "warehouse_inventory_{$id}_" . md5(serialize([
    'per_page' => $perPage,
    'search' => $search,
    'min_quantity' => $minQuantity,
    'max_quantity' => $maxQuantity,
    'page' => $request->get('page', 1)
]));

// Cache Duration: 5 minutes (300 seconds)
$result = Cache::remember($cacheKey, 300, function () {
    // Database query logic...
});
```

### Cache Management
```php
// Clear specific warehouse cache
Route::delete('/warehouses/{id}/inventory/cache', [WarehouseController::class, 'clearInventoryCache']);

// Cache clearing method
public function clearInventoryCache($id): JsonResponse
{
    $keys = Cache::get('warehouse_inventory_keys_' . $id, []);
    foreach ($keys as $key) {
        Cache::forget($key);
    }
    return response()->json(['message' => 'Cache cleared successfully']);
}
```

## 4. Events and Listeners

### LowStockDetected Event
```php
// Event Class: app/Events/LowStockDetected.php
class LowStockDetected
{
    public $stock;
    public $inventoryItem;
    public $warehouse;

    public function __construct(Stock $stock, InventoryItem $inventoryItem)
    {
        $this->stock = $stock;
        $this->inventoryItem = $inventoryItem;
        $this->warehouse = $stock->warehouse;
    }
}
```

### SendLowStockNotification Listener
```php
// Listener Class: app/Listeners/SendLowStockNotification.php
class SendLowStockNotification
{
    public function handle(LowStockDetected $event): void
    {
        // Log the low stock event
        Log::warning('Low stock detected', [
            'item_id' => $event->inventoryItem->id,
            'item_name' => $event->inventoryItem->name,
            'warehouse_name' => $event->warehouse->name,
            'current_quantity' => $event->stock->quantity,
            'minimum_level' => $event->inventoryItem->minimum_stock_level,
            'shortage' => $event->inventoryItem->minimum_stock_level - $event->stock->quantity,
        ]);

        // Simulate email notification
        $this->sendLowStockEmail($event->inventoryItem, $event->warehouse, $event->stock);
    }
}
```

### Event Triggering
```php
// In Stock Model: app/Models/Stock.php
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
```

## 5. API Endpoints with Validation

### Stock Transfer Endpoint
```http
POST /api/stock-transfers
Content-Type: application/json

{
    "inventory_item_id": 1,
    "from_warehouse_id": 1,
    "to_warehouse_id": 2,
    "quantity": 10,
    "notes": "Transfer for distribution",
    "transferred_by": 1
}

// Response (Success - 201)
{
    "message": "Stock transferred successfully.",
    "data": {
        "id": 1,
        "inventory_item_id": 1,
        "from_warehouse_id": 1,
        "to_warehouse_id": 2,
        "quantity": 10,
        "status": "completed",
        "inventory_item": {...},
        "from_warehouse": {...},
        "to_warehouse": {...}
    }
}

// Response (Validation Error - 422)
{
    "message": "The given data was invalid.",
    "errors": {
        "quantity": ["Insufficient stock. Only 5 units available in source warehouse."]
    }
}
```

### Warehouse Inventory Endpoint (Cached)
```http
GET /api/warehouses/1/inventory?search=laptop&min_quantity=10&per_page=15

// Response (200)
{
    "warehouse": {
        "id": 1,
        "name": "Main Warehouse",
        "location": "New York, NY"
    },
    "data": [...],
    "pagination": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 15,
        "total": 75
    }
}
```

## 6. Testing Commands

### Test Low Stock Event
```bash
# Test low stock event for all items
php artisan test:low-stock-event

# Test low stock event for specific item
php artisan test:low-stock-event 1
```

### Test Inventory System
```bash
# Test the complete inventory system
php artisan app:test-inventory-system
```

## 7. Database Transactions

### Stock Transfer Transaction
```php
DB::beginTransaction();
try {
    // Check available stock
    $sourceStock = Stock::where('inventory_item_id', $validated['inventory_item_id'])
        ->where('warehouse_id', $validated['from_warehouse_id'])
        ->lockForUpdate()
        ->first();

    // Decrement source stock
    $sourceStock->quantity -= $validated['quantity'];
    $sourceStock->save();

    // Increment destination stock
    $destStock = Stock::firstOrCreate([...]);
    $destStock->quantity += $validated['quantity'];
    $destStock->save();

    // Create transfer record
    $transfer = StockTransfer::create([...]);

    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
    throw $e;
}
```

## 8. Performance Optimizations

### Eager Loading
```php
// Load relationships to avoid N+1 queries
$stocks = Stock::with(['inventoryItem', 'warehouse'])->paginate($perPage);
```

### Query Optimization
```php
// Use database indexes for frequently queried fields
// Add indexes to migration files:
$table->index(['inventory_item_id', 'warehouse_id']);
$table->index(['quantity']);
$table->index(['is_active']);
```

### Cache Strategy
```php
// Cache frequently accessed data
Cache::remember('inventory_categories', 3600, function () {
    return InventoryItem::distinct()->pluck('category')->filter()->toArray();
});
```

## 9. Error Handling

### Validation Errors
- Custom error messages for better user experience
- Comprehensive validation rules
- Business logic validation (stock availability, active status)

### Database Errors
- Transaction rollback on errors
- Proper error logging
- User-friendly error messages

### Cache Errors
- Graceful fallback when cache is unavailable
- Cache clearing mechanisms
- Cache key management

## 10. Security Features

### Input Validation
- SQL injection prevention through Eloquent ORM
- XSS prevention through proper output encoding
- CSRF protection (Laravel built-in)

### Authorization
- Form request authorization checks
- User authentication for sensitive operations
- Role-based access control (can be extended)

This implementation provides a robust, scalable inventory management system with proper validation, caching, event handling, and error management. 
