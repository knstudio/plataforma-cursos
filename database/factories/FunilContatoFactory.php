<?php

namespace Database\Factories;

use App\Models\FunilContato;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FunilContato>
 */
class FunilContatoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'telefone' => '55829'.fake()->unique()->numerify('########'),
            'nome' => fake()->firstName().' '.fake()->lastName(),
            'email' => null,
            'user_id' => null,
            'ultima_interacao_em' => null,
            'descadastrado_em' => null,
        ];
    }
}
