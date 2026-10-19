<?php

namespace Database\Factories;

use App\Models\OrderBump;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderBump>
 */
class OrderBumpFactory extends Factory
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
            'bump_product_id' => Product::factory(),
            'price' => fake()->randomFloat(2, 5, 30),
            'headline' => null,
            'sort_order' => 0,
        ];
    }
}
