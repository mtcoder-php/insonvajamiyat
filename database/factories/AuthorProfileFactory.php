<?php

namespace Database\Factories;

use App\Models\AuthorProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuthorProfile>
 */
class AuthorProfileFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'last_name' => fake()->lastName(),
            'first_name' => fake()->firstName(),
            'middle_name' => null,
            'position' => fake()->randomElement(['Dotsent', 'Katta o\'qituvchi', 'Tayanch doktorant', 'Professor']),
            'organization' => fake()->randomElement([
                'Yangi Asr universiteti',
                'O\'zbekiston Milliy universiteti',
                'Toshkent davlat pedagogika universiteti',
            ]),
            'academic_degree' => fake()->randomElement([null, 'PhD', 'DSc']),
            'orcid' => null,
            'country' => 'UZ',
            'is_public' => true,
        ];
    }

    public function withOrcid(): static
    {
        return $this->state(fn (array $attributes) => [
            'orcid' => sprintf(
                '%04d-%04d-%04d-%04d',
                fake()->numberBetween(0, 9999),
                fake()->numberBetween(0, 9999),
                fake()->numberBetween(0, 9999),
                fake()->numberBetween(0, 9999),
            ),
        ]);
    }
}
