<?php

namespace Database\Factories;

use App\Models\Funil;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Funil>
 */
class FunilFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nome = 'Funil '.fake()->unique()->words(2, true);

        return [
            'slug' => Str::slug($nome),
            'nome' => $nome,
            'instancia' => 'bubbles',
            'prioridade' => 0,
            'hora_inicio_envio' => 0,
            'hora_fim_envio' => 24,
            'ativo' => true,
        ];
    }
}
