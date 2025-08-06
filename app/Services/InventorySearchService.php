<?php

namespace App\Services;

use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class InventorySearchService
{
    /**
     * Perform advanced search with multiple algorithms.
     */
    public function advancedSearch(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $cacheKey = $this->generateCacheKey($filters, $perPage);
        
        return Cache::remember($cacheKey, 300, function () use ($filters, $perPage) {
            return $this->executeSearch($filters, $perPage);
        });
    }

    /**
     * Execute the search query with all filters.
     */
    private function executeSearch(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = InventoryItem::query();

        // Apply text search with multiple algorithms
        if (!empty($filters['search'])) {
            $this->applyTextSearch($query, $filters['search']);
        }

        // Apply filters
        $this->applyFilters($query, $filters);

        // Apply sorting
        $this->applySorting($query, $filters);

        return $query->with(['stocks.warehouse'])->paginate($perPage);
    }

    /**
     * Apply text search using multiple algorithms.
     */
    private function applyTextSearch(Builder $query, string $search): void
    {
        $searchTerms = $this->tokenizeSearch($search);
        
        $query->where(function ($q) use ($searchTerms, $search) {
            // Exact match (highest priority)
            $q->where('name', $search)
              ->orWhere('sku', $search)
              ->orWhere('brand', $search);

            // Partial match with wildcards
            foreach ($searchTerms as $term) {
                $q->orWhere('name', 'like', "%{$term}%")
                  ->orWhere('sku', 'like', "%{$term}%")
                  ->orWhere('description', 'like', "%{$term}%")
                  ->orWhere('brand', 'like', "%{$term}%")
                  ->orWhere('category', 'like', "%{$term}%");
            }

            // Fuzzy search for similar terms
            $this->applyFuzzySearch($q, $search);
        });
    }

    /**
     * Tokenize search string for better matching.
     */
    private function tokenizeSearch(string $search): array
    {
        $terms = preg_split('/\s+/', trim($search));
        return array_filter($terms, function ($term) {
            return strlen($term) >= 2;
        });
    }

    /**
     * Apply fuzzy search for similar terms.
     */
    private function applyFuzzySearch(Builder $query, string $search): void
    {
        // Simple fuzzy search using SOUNDEX or similar
        $query->orWhereRaw('SOUNDEX(name) = SOUNDEX(?)', [$search])
              ->orWhereRaw('SOUNDEX(sku) = SOUNDEX(?)', [$search]);
    }

    /**
     * Apply various filters to the query.
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        // Category filter
        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        // Brand filter
        if (!empty($filters['brand'])) {
            $query->where('brand', $filters['brand']);
        }

        // Stock level filters
        if (isset($filters['min_stock']) || isset($filters['max_stock'])) {
            $this->applyStockLevelFilters($query, $filters);
        }

        // Status filter
        if (isset($filters['status'])) {
            if ($filters['status'] === 'active') {
                $query->where('is_active', true);
            } elseif ($filters['status'] === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Stock status filters
        if (!empty($filters['stock_status'])) {
            $this->applyStockStatusFilters($query, $filters['stock_status']);
        }
    }

    /**
     * Apply stock level range filters.
     */
    private function applyStockLevelFilters(Builder $query, array $filters): void
    {
        $minStock = $filters['min_stock'] ?? null;
        $maxStock = $filters['max_stock'] ?? null;

        if ($minStock !== null) {
            $query->whereHas('stocks', function ($q) use ($minStock) {
                $q->where('quantity', '>=', $minStock);
            });
        }

        if ($maxStock !== null) {
            $query->whereHas('stocks', function ($q) use ($maxStock) {
                $q->where('quantity', '<=', $maxStock);
            });
        }
    }

    /**
     * Apply stock status filters.
     */
    private function applyStockStatusFilters(Builder $query, string $status): void
    {
        switch ($status) {
            case 'low_stock':
                $query->whereHas('stocks', function ($q) {
                    $q->whereRaw('quantity < minimum_stock_level');
                });
                break;
            case 'out_of_stock':
                $query->whereDoesntHave('stocks', function ($q) {
                    $q->where('quantity', '>', 0);
                });
                break;
            case 'in_stock':
                $query->whereHas('stocks', function ($q) {
                    $q->where('quantity', '>', 0);
                });
                break;
        }
    }

    /**
     * Apply sorting to the query.
     */
    private function applySorting(Builder $query, array $filters): void
    {
        $sortBy = $filters['sort_by'] ?? 'name';
        $sortOrder = $filters['sort_order'] ?? 'asc';

        $allowedSortFields = ['name', 'sku', 'category', 'brand', 'created_at'];
        
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } elseif ($sortBy === 'total_quantity') {
            $query->withSum('stocks', 'quantity')
                  ->orderBy('stocks_sum_quantity', $sortOrder);
        } elseif ($sortBy === 'available_quantity') {
            $query->withSum('stocks', 'quantity')
                  ->withSum('stocks', 'reserved_quantity')
                  ->orderByRaw('(stocks_sum_quantity - stocks_sum_reserved_quantity) ' . $sortOrder);
        }
    }

    /**
     * Generate cache key for search results.
     */
    private function generateCacheKey(array $filters, int $perPage): string
    {
        $filterString = json_encode($filters);
        return 'inventory_search_' . md5($filterString . $perPage);
    }

    /**
     * Get search suggestions for autocomplete.
     */
    public function getSearchSuggestions(string $query, int $limit = 10): array
    {
        $cacheKey = 'search_suggestions_' . md5($query . $limit);
        
        return Cache::remember($cacheKey, 600, function () use ($query, $limit) {
            $suggestions = [];
            
            // Name suggestions
            $nameSuggestions = InventoryItem::where('name', 'like', "%{$query}%")
                ->select('name')
                ->distinct()
                ->limit($limit)
                ->pluck('name')
                ->toArray();
            
            $suggestions = array_merge($suggestions, $nameSuggestions);
            
            // SKU suggestions
            $skuSuggestions = InventoryItem::where('sku', 'like', "%{$query}%")
                ->select('sku')
                ->distinct()
                ->limit($limit)
                ->pluck('sku')
                ->toArray();
            
            $suggestions = array_merge($suggestions, $skuSuggestions);
            
            // Brand suggestions
            $brandSuggestions = InventoryItem::where('brand', 'like', "%{$query}%")
                ->select('brand')
                ->distinct()
                ->limit($limit)
                ->pluck('brand')
                ->toArray();
            
            $suggestions = array_merge($suggestions, $brandSuggestions);
            
            return array_unique(array_slice($suggestions, 0, $limit));
        });
    }

    /**
     * Get popular search terms.
     */
    public function getPopularSearchTerms(int $limit = 10): array
    {
        return Cache::remember('popular_search_terms', 3600, function () use ($limit) {
            // This could be enhanced with actual search analytics
            return [
                'laptop',
                'phone',
                'tablet',
                'monitor',
                'keyboard',
                'mouse',
                'headphones',
                'speakers',
                'camera',
                'printer'
            ];
        });
    }

    /**
     * Clear search cache.
     */
    public function clearSearchCache(): void
    {
        Cache::flush();
    }
} 