<?php

namespace App\Services\Funil;

/**
 * Brazilian mobile numbers as WhatsApp uses them: 55 + DDD + 9 + 8 digits.
 */
class Telefone
{
    /**
     * Accepts "(82) 99999-9999", "82 9999-9999", "5582999999999", WhatsApp JIDs
     * without the 9 ("558299999999")... Returns null when it can't be a mobile number.
     */
    public static function normalizar(?string $valor): ?string
    {
        $numero = ltrim((string) preg_replace('/\D/', '', (string) $valor), '0');

        if (in_array(strlen($numero), [12, 13], true) && str_starts_with($numero, '55')) {
            $numero = substr($numero, 2);
        }

        if (strlen($numero) === 10) {
            $numero = substr($numero, 0, 2).'9'.substr($numero, 2);
        }

        if (strlen($numero) === 11) {
            $numero = '55'.$numero;
        }

        return preg_match('/^55[1-9][1-9]9\d{8}$/', $numero) ? $numero : null;
    }
}
