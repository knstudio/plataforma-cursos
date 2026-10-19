<?php

namespace Database\Factories;

use App\Models\FunilBloco;
use App\Models\FunilMensagem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FunilMensagem>
 */
class FunilMensagemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bloco_id' => FunilBloco::factory(),
            'ordem' => 1,
            'espera_min' => 0,
            'product_offer_id' => null,
            'texto' => 'Oi, {primeiro_nome}! Conheça o {produto} por R$ {preco}: {link}',
            'ativo' => true,
        ];
    }
}
