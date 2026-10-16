<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Refunded = 'refunded';
    case Cancelled = 'cancelled';
    case Chargeback = 'chargeback';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Approved => 'Aprovado',
            self::Refunded => 'Reembolsado',
            self::Cancelled => 'Cancelado',
            self::Chargeback => 'Chargeback',
        };
    }

    /**
     * Whether this status should keep the buyer's access to the purchased courses.
     */
    public function grantsAccess(): bool
    {
        return $this === self::Approved;
    }
}
