<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\KycDocument>
 */
class KycDocumentFactory extends Factory
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
            'document_type' => $this->faker->randomElement(['aadhaar_front', 'aadhaar_back', 'pan_card', 'driving_license']),
            'document_number' => $this->faker->numerify('ABC#####'),
            'image_path' => 'kyc/sample.jpg',
            'status' => $this->faker->randomElement(['pending', 'verified', 'rejected']),
            'rejection_reason' => null,
        ];
    }
}
