<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\PaymentGateway;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'product_id' => Product::factory(),
            'gateway' => PaymentGateway::MercadoPago,
            'external_id' => (string) fake()->unique()->numberBetween(1_000_000, 9_999_999),
            'status' => OrderStatus::Approved,
            'amount' => fake()->randomFloat(2, 47, 497),
            'paid_at' => now(),
            'payload' => null,
        ];
    }

    /**
     * Indicate that the order is still waiting for payment.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Pending,
            'paid_at' => null,
        ]);
    }
}
