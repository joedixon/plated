<?php

namespace Database\Factories;

use App\Enums\DishStatus;
use App\Models\Dish;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dish>
 */
class DishFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucwords(fake()->words(3, true)),
            'description' => fake()->sentence(10),
            'pairing' => ucfirst(fake()->words(2, true)),
            'glyph' => fake()->randomElement(['🍽️', '🥘', '🍲', '🍛', '🥗', '🍤', '🧆', '🫕']),
            'sequence' => fake()->unique()->numberBetween(200, 999),
            'status' => DishStatus::Plated,
        ];
    }

    /**
     * Indicate that the dish is still being plated on the pass.
     */
    public function firing(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => DishStatus::Firing,
        ]);
    }
}
