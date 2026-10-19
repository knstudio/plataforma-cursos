<?php

namespace App\Services\Funil;

use App\Models\FunilBloco;
use App\Models\FunilMensagem;

/**
 * Fills the {variables} of the funnel texts. The admin preview and the real
 * messages go through the same code.
 */
class FunilTexto
{
    /**
     * @param  array<string, string>  $variaveis
     */
    public static function montar(string $texto, array $variaveis): string
    {
        $pares = collect($variaveis)->mapWithKeys(fn (string $valor, string $chave) => ['{'.$chave.'}' => $valor])->all();

        // "Oi, {primeiro_nome}!" without a name would read "Oi, !".
        return str_replace(', !', '!', strtr($texto, $pares));
    }

    /**
     * Text of a block message for a contact. $inscricaoId goes in the link so the
     * checkout knows which enrollment sold.
     */
    public static function mensagem(FunilMensagem $mensagem, FunilBloco $bloco, string $primeiroNome, int|string $inscricaoId): string
    {
        $product = $bloco->product;
        $offer = $mensagem->offer;

        $link = $offer ? $offer->checkoutUrl() : route('checkout.show', $product);

        return self::montar($mensagem->texto, [
            'primeiro_nome' => $primeiroNome,
            'produto' => (string) $product?->name,
            'preco' => self::preco($offer?->price ?? $product?->price),
            'link' => $link.(str_contains($link, '?') ? '&' : '?').'fi='.$inscricaoId,
        ]);
    }

    public static function preco(string|float|null $valor): string
    {
        return number_format((float) $valor, 2, ',', '.');
    }
}
