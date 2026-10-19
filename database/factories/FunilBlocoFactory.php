<?php

namespace Database\Factories;

use App\Models\Funil;
use App\Models\FunilBloco;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FunilBloco>
 */
class FunilBlocoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'funil_id' => Funil::factory(),
            'posicao' => 1,
            'product_id' => Product::factory(),
            'espera_inicio_seg' => 0,
            'espera_se_comprou_seg' => null,
            'ativo' => true,
        ];
    }
}
