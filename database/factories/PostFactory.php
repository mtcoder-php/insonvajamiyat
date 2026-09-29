<?php

namespace Database\Factories;

use App\Enums\PostType;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = Str::ucfirst(fake()->sentence(6, false));

        return [
            'type' => PostType::News,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(6)),
            'title' => ['uz' => $title],
            'excerpt' => ['uz' => fake()->sentence(14)],
            'body' => ['uz' => fake()->paragraphs(3, true)],
            'is_published' => true,
            'is_pinned' => false,
            'published_at' => now()->subDays(fake()->numberBetween(1, 60)),
        ];
    }

    public function announcement(): static
    {
        return $this->state(fn (array $attributes) => ['type' => PostType::Announcement]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => ['is_published' => false]);
    }
}
