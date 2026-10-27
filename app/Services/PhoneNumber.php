<?php

namespace App\Services;

/**
 * Brazilian phone numbers as typed in the checkout ("82999991234") or sent by
 * WhatsApp tools ("5582999991234", "(82) 9999-1234").
 */
class PhoneNumber
{
    /**
     * DDD + number (10 or 11 digits), without country code. Null when it can't be a Brazilian number.
     */
    public static function national(?string $value): ?string
    {
        $digits = ltrim((string) preg_replace('/\D/', '', (string) $value), '0');

        if (in_array(strlen($digits), [12, 13], true) && str_starts_with($digits, '55')) {
            $digits = substr($digits, 2);
        }

        return preg_match('/^[1-9][1-9]\d{8,9}$/', $digits) ? $digits : null;
    }

    /**
     * 55 + DDD + number, the format WhatsApp uses.
     */
    public static function whatsapp(?string $value): ?string
    {
        $national = self::national($value);

        return $national ? '55'.$national : null;
    }

    /**
     * Ways the same mobile may be stored: with or without the extra 9, with or without 55.
     *
     * @return list<string>
     */
    public static function variants(?string $value): array
    {
        $national = self::national($value);

        if (! $national) {
            return [];
        }

        $ddd = substr($national, 0, 2);
        $number = substr($national, 2);

        $forms = [$national];

        if (strlen($number) === 9 && $number[0] === '9') {
            $forms[] = $ddd.substr($number, 1);
        } elseif (strlen($number) === 8) {
            $forms[] = $ddd.'9'.$number;
        }

        return array_values(array_unique([...$forms, ...array_map(fn (string $form) => '55'.$form, $forms)]));
    }
}
