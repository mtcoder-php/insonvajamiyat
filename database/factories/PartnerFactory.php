<?php

namespace Database\Factories;

use App\Enums\PartnerType;
use App\Models\Partner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Partner>
 */
class PartnerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'type' => PartnerType::Partner,
            'name' => ['uz' => $name],
            'url' => fake()->url(),
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    public function indexing(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PartnerType::Indexing,
            'subtitle' => ['uz' => 'Indekslangan'],
        ]);
    }
}
