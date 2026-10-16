<?php

namespace Database\Factories;

use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = Str::ucfirst(fake()->word().' '.fake()->word().' '.fake()->word());

        return [
            'title' => ['uz' => $title],
            'description' => ['uz' => fake()->sentence(18)],
            'author' => Str::upper(fake()->randomLetter()).'. '.fake()->lastName(),
            'publisher' => fake()->company(),
            'year' => fake()->numberBetween(2015, 2026),
            'pages' => fake()->numberBetween(120, 480),
            'language' => 'uz',
            'sort_order' => 0,
            'is_featured' => false,
            'is_active' => true,
        ];
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => ['is_featured' => true]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}
