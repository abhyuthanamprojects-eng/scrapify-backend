<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
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
            'name' => ['en' => ucfirst($name), 'hi' => ucfirst($name) . ' (HI)'],
            'slug' => Str::slug($name),
            'image_path' => 'categories/default.png',
            'parent_id' => null,
            'status' => true,
        ];
    }
}
