<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'amount' => $this->faker->randomFloat(2, 1, 1000),
            'currency' => $this->faker->currencyCode,
            'provider' => $this->faker->randomElement(['paypal', 'stripe']),
            'user_id' => User::factory(),
            'status' => $this->faker->randomElement(['new', 'processing', 'completed', 'failed']),
            'payment_link' => $this->faker->url,
        ];
    }
}

