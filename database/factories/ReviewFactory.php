<?php

namespace Database\Factories;

use App\Enums\ReviewStatus;
use App\Models\Article;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'article_id' => Article::factory(),
            'reviewer_id' => User::factory(),
            'round' => 1,
            'status' => ReviewStatus::Invited,
            'due_at' => now()->addDays(14),
        ];
    }

    public function status(ReviewStatus $status): static
    {
        return $this->state(fn (array $attributes) => ['status' => $status]);
    }
}
