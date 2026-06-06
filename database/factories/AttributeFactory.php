<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attribute>
 */
class AttributeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->word();
        return [
            'name' => ['en' => ucfirst($name)],
            'slug' => Str::slug($name),
            'type' => $this->faker->randomElement(['select', 'radio', 'checkbox', 'text']),
            'unit' => null,
            'status' => true,
        ];
    }
}
