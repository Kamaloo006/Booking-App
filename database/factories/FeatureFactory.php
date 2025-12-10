<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Feature>
 */
class FeatureFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'area' => $this->faker->numberBetween(60, 400),
            'rooms' => $this->faker->numberBetween(1, 10),
            'kitchens' => $this->faker->numberBetween(1, 2),
            'bathrooms' => $this->faker->numberBetween(1, 4),
        ];
    }
}
