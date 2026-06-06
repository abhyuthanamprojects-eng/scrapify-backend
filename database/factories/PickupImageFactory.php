<?php

namespace Database\Factories;

use App\Models\PickupRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PickupImage>
 */
class PickupImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pickup_request_id' => PickupRequest::factory(),
            'image_path' => 'pickups/default.jpg',
            'type' => $this->faker->randomElement(['item', 'proof', 'signature']),
        ];
    }
}
