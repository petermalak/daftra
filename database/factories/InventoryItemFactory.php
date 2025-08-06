<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = ['Electronics', 'Clothing', 'Books', 'Home & Garden', 'Sports', 'Automotive', 'Health & Beauty'];
        $brands = ['Apple', 'Samsung', 'Nike', 'Adidas', 'Sony', 'LG', 'Dell', 'HP'];
        $units = ['pcs', 'kg', 'liters', 'boxes', 'pairs'];
        
        return [
            'name' => fake()->words(2, true),
            'sku' => strtoupper(fake()->bothify('??-####-??')),
            'description' => fake()->sentence(),
            'category' => fake()->randomElement($categories),
            'brand' => fake()->randomElement($brands),
            'unit_of_measure' => fake()->randomElement($units),
            'minimum_stock_level' => fake()->numberBetween(0, 10),
            'maximum_stock_level' => fake()->numberBetween(50, 200),
            'is_active' => true,
        ];
    }
}
