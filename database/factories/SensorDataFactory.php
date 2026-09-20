<?php

namespace Database\Factories;

use App\Models\SensorData;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SensorData>
 */
class SensorDataFactory extends Factory
{
    /**
     * Define the model's default state (safe condition).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'soil_percent' => fake()->numberBetween(40, 90),
            'soil_raw' => fake()->numberBetween(1200, 2400),
            'water_raw' => fake()->numberBetween(1500, 3500),
            'is_water_empty' => false,
            'is_soil_dry' => false,
            'pump_status' => false,
            'lampu1_d25' => false,
            'lampu2_d14' => false,
        ];
    }

    /**
     * Indicate that the soil is dry and the pump is watering.
     */
    public function soilDry(): static
    {
        return $this->state(fn (array $attributes) => [
            'soil_percent' => fake()->numberBetween(5, 29),
            'soil_raw' => fake()->numberBetween(2800, 3400),
            'is_soil_dry' => true,
            'pump_status' => true,
            'lampu1_d25' => true,
        ]);
    }

    /**
     * Indicate that the water tank is empty.
     */
    public function waterEmpty(): static
    {
        return $this->state(fn (array $attributes) => [
            'water_raw' => fake()->numberBetween(0, 300),
            'is_water_empty' => true,
            'pump_status' => false,
            'lampu2_d14' => true,
        ]);
    }
}
