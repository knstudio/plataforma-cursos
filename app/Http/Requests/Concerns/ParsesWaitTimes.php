<?php

namespace App\Http\Requests\Concerns;

use App\Enums\EsperaUnidade;
use Illuminate\Validation\Rule;

/**
 * Funnel waits typed as a number plus a unit ({field}_valor + {field}_unidade),
 * stored in minutes.
 */
trait ParsesWaitTimes
{
    /**
     * @return array<string, array<mixed>>
     */
    protected function waitRules(string $field, bool $required = true): array
    {
        return [
            $field.'_valor' => [$required ? 'required' : 'nullable', 'integer', 'min:0', 'max:100000'],
            $field.'_unidade' => [$required ? 'required' : 'nullable', Rule::enum(EsperaUnidade::class)],
        ];
    }

    protected function waitMinutes(string $field): int
    {
        $unidade = EsperaUnidade::tryFrom((string) $this->validated($field.'_unidade')) ?? EsperaUnidade::Minutos;

        return $unidade->paraMinutos((int) $this->validated($field.'_valor'));
    }
}
