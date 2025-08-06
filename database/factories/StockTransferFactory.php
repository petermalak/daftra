<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StockTransfer>
 */
class StockTransferFactory extends Factory
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
            'from_warehouse_id' => \App\Models\Warehouse::factory(),
            'to_warehouse_id' => \App\Models\Warehouse::factory(),
            'quantity' => fake()->numberBetween(1, 50),
            'transfer_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'status' => fake()->randomElement(['pending', 'approved', 'completed', 'cancelled']),
            'notes' => fake()->optional()->sentence(),
            'transferred_by' => \App\Models\User::factory(),
            'approved_by' => fake()->optional()->randomElement([\App\Models\User::factory()]),
            'approved_at' => fake()->optional()->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
