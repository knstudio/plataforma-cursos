<?php

namespace App\Enums;

/**
 * Unit of the funnel waiting times. They are stored in seconds; the admin
 * shows them in the largest unit that divides them exactly.
 */
enum EsperaUnidade: string
{
    case Segundos = 'seg';
    case Minutos = 'min';
    case Horas = 'horas';
    case Dias = 'dias';

    public function segundos(): int
    {
        return match ($this) {
            self::Segundos => 1,
            self::Minutos => 60,
            self::Horas => 3600,
            self::Dias => 86400,
        };
    }

    public function paraSegundos(int $valor): int
    {
        return $valor * $this->segundos();
    }

    /**
     * @return array{0: int, 1: self}
     */
    public static function separar(int $segundos): array
    {
        if ($segundos === 0) {
            return [0, self::Minutos];
        }

        foreach ([self::Dias, self::Horas, self::Minutos] as $unidade) {
            if ($segundos % $unidade->segundos() === 0) {
                return [intdiv($segundos, $unidade->segundos()), $unidade];
            }
        }

        return [$segundos, self::Segundos];
    }

    /**
     * "30 seg", "3 min", "1 hora", "2 dias".
     */
    public static function descrever(int $segundos): string
    {
        [$valor, $unidade] = self::separar($segundos);

        return $valor.' '.match ($unidade) {
            self::Segundos => 'seg',
            self::Minutos => 'min',
            self::Horas => $valor === 1 ? 'hora' : 'horas',
            self::Dias => $valor === 1 ? 'dia' : 'dias',
        };
    }
}
