<?php

namespace Database\Factories;

use App\Enums\IssueStatus;
use App\Models\JournalIssue;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JournalIssue>
 */
class JournalIssueFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = (int) now()->year;
        $number = fake()->unique()->numberBetween(1, 60000);

        return [
            'year' => $year,
            'volume' => 1,
            'number' => $number,
            'slug' => "{$year}-{$number}",
            'title' => ['uz' => '"Inson va Jamiyat" ilmiy jurnali'],
            'description' => ['uz' => fake()->paragraph()],
            'status' => IssueStatus::Draft,
        ];
    }

    public function published(?DateTimeInterface $at = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => IssueStatus::Published,
            'published_at' => $at ?? now()->subDays(fake()->numberBetween(1, 90)),
        ]);
    }
}
