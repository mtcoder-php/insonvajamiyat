<?php

namespace Database\Factories;

use App\Enums\PaymentProvider;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'purpose' => PaymentPurpose::Service,
            'amount' => fake()->randomElement([80000, 150000, 200000, 250000]),
            'currency' => 'UZS',
            'provider' => fake()->randomElement([PaymentProvider::Click, PaymentProvider::Payme]),
            'status' => PaymentStatus::Pending,
        ];
    }

    public function paid(?DateTimeInterface $at = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Paid,
            'paid_at' => $at ?? now()->subDays(fake()->numberBetween(0, 60)),
        ]);
    }
}
