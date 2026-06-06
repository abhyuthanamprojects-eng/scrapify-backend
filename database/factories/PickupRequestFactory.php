<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PickupRequest>
 */
class PickupRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Lat/copy from a real city like Mumbai
        $lat = 19.0760 + $this->faker->randomFloat(6, -0.05, 0.05);
        $lng = 72.8777 + $this->faker->randomFloat(6, -0.05, 0.05);

        return [
            'customer_id' => User::factory(), // Or existing user
            'address' => $this->faker->address(),
            'latitude' => $lat,
            'longitude' => $lng,
            'scheduled_at' => $this->faker->dateTimeBetween('now', '+1 week'),
            'status' => $this->faker->randomElement(['pending', 'assigned', 'on_the_way', 'picked_up', 'completed', 'cancelled']),
            'estimated_amount' => $this->faker->randomFloat(2, 100, 2000),
            'final_amount' => null,
            'cancellation_reason' => null,
            'metadata' => null,
        ];
    }
}
