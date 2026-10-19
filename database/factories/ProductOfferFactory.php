<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductOffer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductOffer>
 */
class ProductOfferFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'name' => 'Campanha '.fake()->monthName(),
            'code' => fake()->unique()->slug(2),
            'price' => fake()->randomFloat(2, 5, 50),
            'without_order_bump' => false,
            'expires_at' => null,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the offer's deadline has passed.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDay(),
        ]);
    }
}
