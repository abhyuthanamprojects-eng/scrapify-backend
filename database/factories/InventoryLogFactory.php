<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InventoryLog>
 */
class InventoryLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'warehouse_id' => Warehouse::factory(),
            'category_id' => Category::factory(),
            'weight' => $this->faker->randomFloat(2, 10, 500),
            'quantity' => $this->faker->numberBetween(10, 100),
            'type' => $this->faker->randomElement(['in', 'out', 'adjustment']),
            'reference_id' => $this->faker->uuid(),
            'notes' => $this->faker->sentence(),
        ];
    }
}
