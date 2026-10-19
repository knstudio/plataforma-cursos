<?php

namespace App\Enums;

/**
 * How a contact enters a funnel. New entry types (WhatsApp keyword, manual)
 * are new cases here plus their handling; the tables don't change.
 */
enum FunilGatilhoTipo: string
{
    case Formulario = 'formulario';
    case CompraProduto = 'compra_produto';

    public function label(): string
    {
        return match ($this) {
            self::Formulario => 'Formulário (isca)',
            self::CompraProduto => 'Compra de produto (pós-venda)',
        };
    }
}
