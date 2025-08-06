<?php

namespace App\Http\Requests;

use App\Models\Stock;
use App\Models\Warehouse;
use App\Models\InventoryItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockTransferRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'inventory_item_id' => [
                'required',
                'integer',
                Rule::exists('inventory_items', 'id')->where('is_active', true),
            ],
            'from_warehouse_id' => [
                'required',
                'integer',
                Rule::exists('warehouses', 'id')->where('is_active', true),
                'different:to_warehouse_id',
            ],
            'to_warehouse_id' => [
                'required',
                'integer',
                Rule::exists('warehouses', 'id')->where('is_active', true),
                'different:from_warehouse_id',
            ],
            'quantity' => [
                'required',
                'integer',
                'min:1',
                'max:999999',
            ],
            'notes' => 'nullable|string|max:1000',
            'transferred_by' => 'nullable|integer|exists:users,id',
        ];
    }

    /**
     * Get custom error messages for the request validation.
     */
    public function messages(): array
    {
        return [
            'inventory_item_id.exists' => 'The selected inventory item does not exist or is inactive.',
            'from_warehouse_id.exists' => 'The source warehouse does not exist or is inactive.',
            'to_warehouse_id.exists' => 'The destination warehouse does not exist or is inactive.',
            'from_warehouse_id.different' => 'Source and destination warehouses must be different.',
            'to_warehouse_id.different' => 'Source and destination warehouses must be different.',
            'quantity.min' => 'Transfer quantity must be at least 1.',
            'quantity.max' => 'Transfer quantity cannot exceed 999,999.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateStockAvailability($validator);
        });
    }

    /**
     * Validate that sufficient stock is available for transfer.
     */
    private function validateStockAvailability($validator): void
    {
        if ($validator->errors()->count() > 0) {
            return;
        }

        $inventoryItemId = $this->input('inventory_item_id');
        $fromWarehouseId = $this->input('from_warehouse_id');
        $quantity = (int) $this->input('quantity');

        // Check if stock exists in source warehouse
        $stock = Stock::where('inventory_item_id', $inventoryItemId)
            ->where('warehouse_id', $fromWarehouseId)
            ->first();

        if (!$stock) {
            $validator->errors()->add('from_warehouse_id', 'No stock found for this item in the source warehouse.');
            return;
        }

        // Check if sufficient quantity is available
        if ($stock->quantity < $quantity) {
            $availableQuantity = $stock->quantity;
            $validator->errors()->add('quantity', "Insufficient stock. Only {$availableQuantity} units available in source warehouse.");
            return;
        }

        // Check if item is active
        $inventoryItem = InventoryItem::find($inventoryItemId);
        if (!$inventoryItem || !$inventoryItem->is_active) {
            $validator->errors()->add('inventory_item_id', 'The selected inventory item is not active.');
            return;
        }

        // Check if warehouses are active
        $fromWarehouse = Warehouse::find($fromWarehouseId);
        $toWarehouse = Warehouse::find($this->input('to_warehouse_id'));

        if (!$fromWarehouse || !$fromWarehouse->is_active) {
            $validator->errors()->add('from_warehouse_id', 'The source warehouse is not active.');
            return;
        }

        if (!$toWarehouse || !$toWarehouse->is_active) {
            $validator->errors()->add('to_warehouse_id', 'The destination warehouse is not active.');
            return;
        }
    }
}
