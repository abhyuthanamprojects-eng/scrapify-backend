<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Warehouse>
 */
class WarehouseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $lat = 19.0760 + $this->faker->randomFloat(6, -0.05, 0.05);
        $lng = 72.8777 + $this->faker->randomFloat(6, -0.05, 0.05);

        return [
            'city_id' => \App\Models\City::factory(),
            'name' => $this->faker->city() . ' Warehouse',
            'code' => 'WH-' . strtoupper($this->faker->unique()->bothify('??###')),
            'address' => $this->faker->address(),
            'capacity' => $this->faker->numberBetween(100, 1000),
            'latitude' => $lat,
            'longitude' => $lng,
            'manager_id' => User::factory(),
            'status' => true,
        ];
    }
}
