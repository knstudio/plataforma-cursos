<?php

namespace Database\Factories;

use App\Enums\FunilInscricaoStatus;
use App\Models\Funil;
use App\Models\FunilBloco;
use App\Models\FunilContato;
use App\Models\FunilInscricao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FunilInscricao>
 */
class FunilInscricaoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contato_id' => FunilContato::factory(),
            'funil_id' => Funil::factory(),
            'gatilho_id' => null,
            'status' => FunilInscricaoStatus::AguardandoConfirmacao,
            'bloco_atual_id' => null,
            'ultima_ordem_enviada' => 0,
            'proximo_envio_em' => null,
        ];
    }

    /**
     * Walking through the given block, due now.
     */
    public function noBloco(FunilBloco $bloco): static
    {
        return $this->state(fn (array $attributes) => [
            'funil_id' => $bloco->funil_id,
            'status' => FunilInscricaoStatus::NoFunil,
            'bloco_atual_id' => $bloco->id,
            'proximo_envio_em' => now()->subMinute(),
        ]);
    }
}
