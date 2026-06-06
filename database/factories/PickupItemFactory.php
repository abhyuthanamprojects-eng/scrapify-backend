<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\PickupRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PickupItem>
 */
class PickupItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $weight = $this->faker->randomFloat(2, 0.5, 20);
        $price = $this->faker->randomFloat(2, 10, 500);

        return [
            'pickup_request_id' => PickupRequest::factory(),
            'category_id' => Category::factory(),
            'weight' => $weight,
            'quantity' => $this->faker->numberBetween(1, 5),
            'price_per_unit' => $price,
            'total_price' => $weight * $price,
            'image_path' => null,
        ];
    }
}
