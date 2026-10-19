<?php

namespace App\Enums;

/**
 * Unit of the funnel waiting times. They are stored in minutes; the admin
 * shows them in the largest unit that divides them exactly.
 */
enum EsperaUnidade: string
{
    case Minutos = 'min';
    case Horas = 'horas';
    case Dias = 'dias';

    public function minutos(): int
    {
        return match ($this) {
            self::Minutos => 1,
            self::Horas => 60,
            self::Dias => 1440,
        };
    }

    public function paraMinutos(int $valor): int
    {
        return $valor * $this->minutos();
    }

    /**
     * @return array{0: int, 1: self}
     */
    public static function separar(int $minutos): array
    {
        foreach ([self::Dias, self::Horas] as $unidade) {
            if ($minutos > 0 && $minutos % $unidade->minutos() === 0) {
                return [intdiv($minutos, $unidade->minutos()), $unidade];
            }
        }

        return [$minutos, self::Minutos];
    }

    /**
     * "3 min", "1 hora", "2 dias".
     */
    public static function descrever(int $minutos): string
    {
        [$valor, $unidade] = self::separar($minutos);

        return $valor.' '.match ($unidade) {
            self::Minutos => 'min',
            self::Horas => $valor === 1 ? 'hora' : 'horas',
            self::Dias => $valor === 1 ? 'dia' : 'dias',
        };
    }
}
