<?php

namespace Database\Factories;

use App\Models\AttributeOption;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PricingRule>
 */
class PricingRuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'attribute_option_id' => null, // Can be overridden
            'base_price' => $this->faker->randomFloat(2, 10, 5000),
            'currency' => 'INR',
            'status' => true,
        ];
    }
}
