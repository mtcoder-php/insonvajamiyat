<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\ArticleAuthor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ArticleAuthor>
 */
class ArticleAuthorFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'article_id' => Article::factory(),
            'last_name' => fake()->lastName(),
            'first_name' => fake()->firstName(),
            'email' => fake()->safeEmail(),
            'organization' => fake()->company(),
            'country' => 'UZ',
            'is_corresponding' => false,
            'sort_order' => 0,
        ];
    }
}
