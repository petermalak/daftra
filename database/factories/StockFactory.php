<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Stock>
 */
class StockFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'inventory_item_id' => \App\Models\InventoryItem::factory(),
            'warehouse_id' => \App\Models\Warehouse::factory(),
            'quantity' => fake()->numberBetween(0, 100),
            'reserved_quantity' => fake()->numberBetween(0, 20),
            'last_updated_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
