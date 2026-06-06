<?php

namespace Database\Factories;

use App\Models\PickupRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'pickup_request_id' => PickupRequest::factory(),
            'amount' => $this->faker->randomFloat(2, 50, 5000),
            'transaction_id' => $this->faker->uuid(),
            'status' => $this->faker->randomElement(['pending', 'processing', 'completed', 'failed', 'approved']),
            'type' => $this->faker->randomElement(['bank_transfer', 'upi', 'cash', 'wallet']),
            'proof_image_path' => null,
            'remarks' => $this->faker->sentence(),
        ];
    }
}
