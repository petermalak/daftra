<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryItemRequest extends FormRequest
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
        $itemId = $this->route('id');

        return [
            'name' => 'required|string|max:255',
            'sku' => [
                'required',
                'string',
                'max:255',
                Rule::unique('inventory_items', 'sku')->ignore($itemId),
            ],
            'description' => 'nullable|string|max:1000',
            'category' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:255',
            'unit_of_measure' => 'required|string|max:50',
            'minimum_stock_level' => 'required|integer|min:0',
            'maximum_stock_level' => [
                'nullable',
                'integer',
                'min:0',
                function ($attribute, $value, $fail) {
                    $minLevel = $this->input('minimum_stock_level');
                    if ($value !== null && $value <= $minLevel) {
                        $fail('Maximum stock level must be greater than minimum stock level.');
                    }
                },
            ],
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get custom error messages for the request validation.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Item name is required.',
            'name.max' => 'Item name cannot exceed 255 characters.',
            'sku.required' => 'SKU is required.',
            'sku.unique' => 'This SKU is already in use.',
            'sku.max' => 'SKU cannot exceed 255 characters.',
            'description.max' => 'Description cannot exceed 1000 characters.',
            'category.max' => 'Category cannot exceed 255 characters.',
            'brand.max' => 'Brand cannot exceed 255 characters.',
            'unit_of_measure.required' => 'Unit of measure is required.',
            'unit_of_measure.max' => 'Unit of measure cannot exceed 50 characters.',
            'minimum_stock_level.required' => 'Minimum stock level is required.',
            'minimum_stock_level.integer' => 'Minimum stock level must be a whole number.',
            'minimum_stock_level.min' => 'Minimum stock level cannot be negative.',
            'maximum_stock_level.integer' => 'Maximum stock level must be a whole number.',
            'maximum_stock_level.min' => 'Maximum stock level cannot be negative.',
        ];
    }
}
