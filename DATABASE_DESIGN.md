# Inventory System Database Design

## Overview
This document describes the database design for the Inventory Management System, including models, migrations, and relationships.

## Database Schema

### 1. Warehouses Table
**Purpose**: Stores warehouse information and locations

**Fields**:
- `id` (Primary Key)
- `name` (String) - Warehouse name
- `location` (String) - General location (city, state)
- `address` (Text, nullable) - Detailed address
- `contact_person` (String, nullable) - Contact person name
- `phone` (String, nullable) - Contact phone number
- `email` (String, nullable) - Contact email
- `is_active` (Boolean) - Whether warehouse is active
- `created_at`, `updated_at` (Timestamps)

### 2. Inventory Items Table
**Purpose**: Stores item metadata and specifications

**Fields**:
- `id` (Primary Key)
- `name` (String) - Item name
- `sku` (String, unique) - Stock Keeping Unit
- `description` (Text, nullable) - Item description
- `category` (String, nullable) - Item category
- `brand` (String, nullable) - Brand name
- `unit_of_measure` (String) - Unit of measurement (pcs, kg, etc.)
- `minimum_stock_level` (Integer) - Minimum stock threshold
- `maximum_stock_level` (Integer, nullable) - Maximum stock threshold
- `is_active` (Boolean) - Whether item is active
- `created_at`, `updated_at` (Timestamps)

### 3. Stocks Table
**Purpose**: Links inventory items to warehouses with quantity information

**Fields**:
- `id` (Primary Key)
- `inventory_item_id` (Foreign Key) - References inventory_items
- `warehouse_id` (Foreign Key) - References warehouses
- `quantity` (Integer) - Total quantity in stock
- `reserved_quantity` (Integer) - Quantity reserved/allocated
- `last_updated_at` (Timestamp, nullable) - Last stock update
- `created_at`, `updated_at` (Timestamps)

**Constraints**:
- Unique combination of `inventory_item_id` and `warehouse_id`

### 4. Stock Transfers Table
**Purpose**: Logs transfers of stock between warehouses

**Fields**:
- `id` (Primary Key)
- `inventory_item_id` (Foreign Key) - References inventory_items
- `from_warehouse_id` (Foreign Key) - Source warehouse
- `to_warehouse_id` (Foreign Key) - Destination warehouse
- `quantity` (Integer) - Quantity being transferred
- `transfer_date` (Timestamp) - Date of transfer
- `status` (Enum) - pending, approved, completed, cancelled
- `notes` (Text, nullable) - Transfer notes
- `transferred_by` (Foreign Key, nullable) - User who initiated transfer
- `approved_by` (Foreign Key, nullable) - User who approved transfer
- `approved_at` (Timestamp, nullable) - Approval timestamp
- `created_at`, `updated_at` (Timestamps)

## Model Relationships

### Warehouse Model
- `hasMany(Stock)` - One warehouse can have many stock records
- `hasMany(StockTransfer, 'from_warehouse_id')` - Outgoing transfers
- `hasMany(StockTransfer, 'to_warehouse_id')` - Incoming transfers

### InventoryItem Model
- `hasMany(Stock)` - One item can have stock in multiple warehouses
- `hasMany(StockTransfer)` - One item can have multiple transfers
- `getTotalQuantityAttribute()` - Calculates total quantity across all warehouses

### Stock Model
- `belongsTo(InventoryItem)` - Each stock record belongs to an item
- `belongsTo(Warehouse)` - Each stock record belongs to a warehouse
- `getAvailableQuantityAttribute()` - Calculates available quantity (total - reserved)

### StockTransfer Model
- `belongsTo(InventoryItem)` - Transfer is for a specific item
- `belongsTo(Warehouse, 'from_warehouse_id')` - Source warehouse
- `belongsTo(Warehouse, 'to_warehouse_id')` - Destination warehouse
- `belongsTo(User, 'transferred_by')` - User who initiated transfer
- `belongsTo(User, 'approved_by')` - User who approved transfer

## Key Features

1. **Multi-warehouse Support**: Items can be stored in multiple warehouses
2. **Stock Tracking**: Tracks total quantity, reserved quantity, and available quantity
3. **Transfer Management**: Complete audit trail of stock movements between warehouses
4. **User Accountability**: Tracks who initiated and approved transfers
5. **Status Management**: Transfer status tracking (pending, approved, completed, cancelled)
6. **Data Integrity**: Foreign key constraints and unique constraints ensure data consistency

## Usage Examples

### Creating a Warehouse
```php
$warehouse = Warehouse::create([
    'name' => 'Main Distribution Center',
    'location' => 'New York, NY',
    'address' => '123 Main St, New York, NY 10001',
    'contact_person' => 'John Doe',
    'phone' => '+1-555-123-4567',
    'email' => 'warehouse@company.com',
]);
```

### Creating an Inventory Item
```php
$item = InventoryItem::create([
    'name' => 'Laptop Computer',
    'sku' => 'LAP-2024-001',
    'description' => 'High-performance laptop for business use',
    'category' => 'Electronics',
    'brand' => 'Dell',
    'unit_of_measure' => 'pcs',
    'minimum_stock_level' => 5,
    'maximum_stock_level' => 100,
]);
```

### Adding Stock to a Warehouse
```php
$stock = Stock::create([
    'inventory_item_id' => $item->id,
    'warehouse_id' => $warehouse->id,
    'quantity' => 50,
    'reserved_quantity' => 5,
]);
```

### Creating a Stock Transfer
```php
$transfer = StockTransfer::create([
    'inventory_item_id' => $item->id,
    'from_warehouse_id' => $sourceWarehouse->id,
    'to_warehouse_id' => $destinationWarehouse->id,
    'quantity' => 10,
    'transfer_date' => now(),
    'status' => 'pending',
    'transferred_by' => auth()->id(),
]);
```

## Testing

Run the following commands to test the system:

```bash
# Run migrations
php artisan migrate

# Seed the database with sample data
php artisan db:seed

# Test the system
php artisan app:test-inventory-system
```

This will create sample warehouses, inventory items, stock records, and transfers for testing purposes. 
