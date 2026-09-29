<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = Str::ucfirst(fake()->sentence(5, false));

        return [
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(6)),
            'title' => ['uz' => $title],
            'description' => ['uz' => fake()->paragraph()],
            'location' => ['uz' => 'Toshkent'],
            'starts_at' => now()->addDays(fake()->numberBetween(3, 120))->setTime(10, 0),
            'is_published' => true,
        ];
    }

    public function past(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => now()->subDays(fake()->numberBetween(5, 90)),
            'ends_at' => null,
        ]);
    }
}
