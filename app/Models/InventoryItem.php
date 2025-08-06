<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sku',
        'description',
        'category',
        'brand',
        'unit_of_measure',
        'minimum_stock_level',
        'maximum_stock_level',
        'is_active',
    ];

    protected $casts = [
        'minimum_stock_level' => 'integer',
        'maximum_stock_level' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the stock records for this item.
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    /**
     * Get the stock transfers for this item.
     */
    public function stockTransfers(): HasMany
    {
        return $this->hasMany(StockTransfer::class);
    }

    /**
     * Get the total quantity across all warehouses.
     */
    public function getTotalQuantityAttribute(): int
    {
        return $this->stocks()->sum('quantity');
    }

    /**
     * Scope to search items by name, SKU, or description.
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('sku', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('brand', 'like', "%{$search}%")
              ->orWhere('category', 'like', "%{$search}%");
        });
    }

    /**
     * Scope to filter by category.
     */
    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to filter by brand.
     */
    public function scopeByBrand(Builder $query, string $brand): Builder
    {
        return $query->where('brand', $brand);
    }

    /**
     * Scope to filter by stock level range.
     */
    public function scopeByStockLevel(Builder $query, int $minQuantity = null, int $maxQuantity = null): Builder
    {
        if ($minQuantity !== null) {
            $query->whereHas('stocks', function ($q) use ($minQuantity) {
                $q->where('quantity', '>=', $minQuantity);
            });
        }

        if ($maxQuantity !== null) {
            $query->whereHas('stocks', function ($q) use ($maxQuantity) {
                $q->where('quantity', '<=', $maxQuantity);
            });
        }

        return $query;
    }

    /**
     * Scope to filter by active status.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by inactive status.
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope to filter items with low stock (below minimum level).
     */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereHas('stocks', function ($q) {
            $q->whereRaw('quantity < minimum_stock_level');
        });
    }

    /**
     * Scope to filter items with no stock.
     */
    public function scopeOutOfStock(Builder $query): Builder
    {
        return $query->whereDoesntHave('stocks', function ($q) {
            $q->where('quantity', '>', 0);
        });
    }

    /**
     * Get all unique categories.
     */
    public static function getCategories(): array
    {
        return static::distinct()->pluck('category')->filter()->toArray();
    }

    /**
     * Get all unique brands.
     */
    public static function getBrands(): array
    {
        return static::distinct()->pluck('brand')->filter()->toArray();
    }

    /**
     * Search and filter items with pagination.
     */
    public static function searchAndPaginate(array $filters = [], int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = static::query();

        // Apply search
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Apply category filter
        if (!empty($filters['category'])) {
            $query->byCategory($filters['category']);
        }

        // Apply brand filter
        if (!empty($filters['brand'])) {
            $query->byBrand($filters['brand']);
        }

        // Apply stock level filters
        if (isset($filters['min_stock'])) {
            $query->byStockLevel($filters['min_stock'], $filters['max_stock'] ?? null);
        } elseif (isset($filters['max_stock'])) {
            $query->byStockLevel(null, $filters['max_stock']);
        }

        // Apply status filter
        if (isset($filters['status'])) {
            if ($filters['status'] === 'active') {
                $query->active();
            } elseif ($filters['status'] === 'inactive') {
                $query->inactive();
            }
        }

        // Apply stock status filters
        if (!empty($filters['stock_status'])) {
            if ($filters['stock_status'] === 'low_stock') {
                $query->lowStock();
            } elseif ($filters['stock_status'] === 'out_of_stock') {
                $query->outOfStock();
            }
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'name';
        $sortOrder = $filters['sort_order'] ?? 'asc';

        if (in_array($sortBy, ['name', 'sku', 'category', 'brand', 'created_at'])) {
            $query->orderBy($sortBy, $sortOrder);
        } elseif ($sortBy === 'total_quantity') {
            $query->withSum('stocks', 'quantity')->orderBy('stocks_sum_quantity', $sortOrder);
        }

        return $query->with(['stocks.warehouse'])->paginate($perPage);
    }
}
