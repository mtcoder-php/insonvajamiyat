<?php

namespace Database\Factories;

use App\Models\ArticleType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ArticleType>
 */
class ArticleTypeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ['uz' => 'Ilmiy maqola', 'ru' => 'Научная статья', 'en' => 'Research article'],
            'slug' => 'scientific_article_'.fake()->unique()->numberBetween(1, 99999),
            'price' => 0,
            'currency' => 'UZS',
            'review_days' => 30,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
