<?php

namespace Database\Factories;

use App\Models\RecommendedBook;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<RecommendedBook>
 */
class RecommendedBookFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = Str::ucfirst(fake()->word().' '.fake()->word().' '.fake()->word());

        return [
            'title' => ['uz' => $title],
            'author' => Str::upper(fake()->randomLetter()).'. '.fake()->lastName(),
            'year' => fake()->numberBetween(2015, 2026),
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}
