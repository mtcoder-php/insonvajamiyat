<?php

namespace Database\Factories;

use App\Enums\AiRequestStatus;
use App\Enums\AiRequestType;
use App\Models\AiRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiRequest>
 */
class AiRequestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => fake()->randomElement(AiRequestType::cases()),
            'status' => AiRequestStatus::Completed,
            'model' => 'claude',
            'source_language' => 'uz',
            'input_text' => fake()->paragraph(),
            'input_tokens' => fake()->numberBetween(200, 4000),
            'output_tokens' => fake()->numberBetween(100, 3000),
        ];
    }
}
