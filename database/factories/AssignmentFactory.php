<?php

namespace Database\Factories;

use App\Models\PickupRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Assignment>
 */
class AssignmentFactory extends Factory
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
            'pickup_boy_id' => User::factory(), // Should be a pickup boy type user
            'status' => $this->faker->randomElement(['assigned', 'accepted', 'rejected', 'completed']),
            'notes' => $this->faker->sentence(),
        ];
    }
}
