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
            'soil_temperature' => fake()->randomFloat(1, 24, 30),
            'water_raw' => null,
            'temperature' => fake()->randomFloat(1, 24, 34),
            'humidity' => fake()->randomFloat(1, 50, 90),
            'is_water_empty' => false,
            'is_soil_dry' => false,
            'pump_status' => false,
            'pump_command' => 'auto',
            'lampu1_d25' => false,
            'lampu2_d14' => false,
            'buzzer_alarm' => false,
        ];
    }

    /**
     * Indicate that the DHT sensor failed to read, so no air temperature or humidity was stored.
     */
    public function withoutDht(): static
    {
        return $this->state(fn (array $attributes) => [
            'temperature' => null,
            'humidity' => null,
        ]);
    }

    /**
     * Indicate that the DS18B20 soil temperature probe could not be read.
     */
    public function withoutSoilTemperature(): static
    {
        return $this->state(fn (array $attributes) => [
            'soil_temperature' => null,
        ]);
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
            'buzzer_alarm' => true,
        ]);
    }

    /**
     * Indicate that the water tank is empty.
     */
    public function waterEmpty(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_water_empty' => true,
            'pump_status' => false,
            'lampu2_d14' => true,
            'buzzer_alarm' => true,
        ]);
    }
}
