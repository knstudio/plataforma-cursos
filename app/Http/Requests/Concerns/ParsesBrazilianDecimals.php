<?php

namespace App\Http\Requests\Concerns;

trait ParsesBrazilianDecimals
{
    /**
     * Accept Brazilian money format: "1.297,90" -> "1297.90". Empty becomes null.
     */
    protected function decimal(mixed $value): mixed
    {
        if (is_string($value) && trim($value) === '') {
            return null;
        }

        if (is_string($value) && str_contains($value, ',')) {
            return str_replace(['.', ','], ['', '.'], trim($value));
        }

        return $value;
    }
}
