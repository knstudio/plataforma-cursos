<?php

namespace App\Support;

/**
 * Converts between "mm:ss" / "h:mm:ss" strings and seconds.
 */
class Duration
{
    /**
     * Returns null for empty input and false when the text can't be understood.
     */
    public static function toSeconds(?string $input): int|false|null
    {
        $input = trim((string) $input);

        if ($input === '') {
            return null;
        }

        if (! preg_match('/^(?:(\d+):)?(\d{1,3}):([0-5]\d)$/', $input, $matches)) {
            return false;
        }

        return ((int) $matches[1]) * 3600 + ((int) $matches[2]) * 60 + (int) $matches[3];
    }

    public static function format(?int $seconds): ?string
    {
        if (! $seconds) {
            return null;
        }

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $rest = $seconds % 60;

        return $hours > 0
            ? sprintf('%d:%02d:%02d', $hours, $minutes, $rest)
            : sprintf('%02d:%02d', $minutes, $rest);
    }
}
