<?php

namespace App\Http\Requests\Concerns;

trait NormalizesPixelIds
{
    /**
     * "123, 456\n789" -> "123,456,789". Anything else is kept as typed so validation can reject it.
     */
    protected function normalizePixelIds(mixed $value): ?string
    {
        $ids = preg_split('/[\s,;]+/', (string) $value, -1, PREG_SPLIT_NO_EMPTY);

        return $ids === [] ? null : implode(',', array_unique($ids));
    }
}
