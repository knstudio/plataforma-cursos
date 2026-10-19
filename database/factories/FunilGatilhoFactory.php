<?php

namespace Database\Factories;

use App\Enums\FunilGatilhoTipo;
use App\Models\Funil;
use App\Models\FunilGatilho;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FunilGatilho>
 */
class FunilGatilhoFactory extends Factory
{
    /**
     * Define the model's default state: the landing form of the ebook funnel.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'funil_id' => Funil::factory(),
            'tipo' => FunilGatilhoTipo::Formulario,
            'product_id' => null,
            'config' => [
                'pagina_obrigado' => 'https://julianabaloes.com.br/obrigado-01/',
                'link_ebook' => 'https://drive.google.com/ebook',
                'msg_confirmacao' => 'Oi, {primeiro_nome}! Posso te enviar o ebook?',
                'msg_ebook' => 'Aqui está, {primeiro_nome}: {link_ebook}',
            ],
            'ativo' => true,
        ];
    }

    /**
     * Post-sale entry: buying the product starts the funnel.
     */
    public function compra(?Product $product = null): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => FunilGatilhoTipo::CompraProduto,
            'product_id' => $product?->id ?? Product::factory(),
            'config' => null,
        ]);
    }
}
