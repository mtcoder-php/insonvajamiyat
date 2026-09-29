<?php

namespace Database\Factories;

use App\Enums\ArticlePaymentStatus;
use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\ArticleAuthor;
use App\Models\ArticleType;
use App\Models\Subject;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = Str::ucfirst(fake()->sentence(7, false));

        return [
            'submitter_id' => User::factory()->author(),
            'article_type_id' => ArticleType::factory(),
            'subject_id' => Subject::factory(),
            'language' => 'uz',
            'title' => ['uz' => $title],
            'abstract' => ['uz' => fake()->paragraph(4)],
            'keywords' => ['uz' => fake()->words(5)],
            'status' => ArticleStatus::Draft,
            'payment_status' => ArticlePaymentStatus::Unpaid,
        ];
    }

    /**
     * Nashr etilgan maqola (web qismda ko'rinadi).
     */
    public function published(?DateTimeInterface $at = null): static
    {
        return $this->state(function (array $attributes) use ($at) {
            $publishedAt = Carbon::instance($at ?? fake()->dateTimeBetween('-6 months', 'now'));

            return [
                'status' => ArticleStatus::Published,
                'payment_status' => ArticlePaymentStatus::Paid,
                'submitted_at' => $publishedAt->copy()->subDays(60),
                'accepted_at' => $publishedAt->copy()->subDays(14),
                'published_at' => $publishedAt,
                'slug' => Str::slug((string) fake()->words(5, true)).'-'.Str::lower(Str::random(6)),
                'doi' => '10.5281/zenodo.'.fake()->unique()->numberBetween(1000000, 9999999),
                'views_count' => fake()->numberBetween(20, 900),
                'downloads_count' => fake()->numberBetween(5, 300),
                'pages_count' => fake()->numberBetween(6, 24),
            ];
        });
    }

    public function status(ArticleStatus $status): static
    {
        return $this->state(fn (array $attributes) => ['status' => $status]);
    }

    /**
     * Maqolaga mualliflar biriktiradi (birinchisi — aloqa uchun mas'ul).
     */
    public function withAuthors(int $count = 1): static
    {
        return $this->afterCreating(function (Article $article) use ($count): void {
            ArticleAuthor::factory()
                ->count($count)
                ->sequence(fn ($sequence) => [
                    'sort_order' => $sequence->index,
                    'is_corresponding' => $sequence->index === 0,
                ])
                ->for($article)
                ->create();
        });
    }
}
