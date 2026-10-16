<?php

namespace App\Enums;

enum PaymentGateway: string
{
    case MercadoPago = 'mercadopago';
    case Hotmart = 'hotmart';

    public function label(): string
    {
        return match ($this) {
            self::MercadoPago => 'Mercado Pago',
            self::Hotmart => 'Hotmart',
        };
    }
}
