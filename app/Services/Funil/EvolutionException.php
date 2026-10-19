<?php

namespace App\Services\Funil;

use RuntimeException;

class EvolutionException extends RuntimeException
{
    /**
     * $doContato: Evolution refused this number (e.g. not on WhatsApp). Otherwise the
     * problem is the connection or the configuration and no message can go out now.
     */
    public function __construct(string $message, public readonly bool $doContato = false)
    {
        parent::__construct($message);
    }
}
