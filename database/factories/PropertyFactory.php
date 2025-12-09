<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Property>
 */
class PropertyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::inRandomOrder()->first()->id ?? User::factory(),
            'name' => $this->faker->title(),
            'city' => $this->faker->city(),
            'governorate' => $this->faker->state(),
            'description' => $this->faker->paragraph(),
            'category' => $this->faker->randomElement(['house', 'villa', 'apartment']),
            'price_per_day' => $this->faker->numberBetween(50, 500),
            'is_available' => $this->faker->boolean(),
        ];
    }
}
