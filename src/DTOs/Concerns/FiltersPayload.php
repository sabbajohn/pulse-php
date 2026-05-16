<?php

namespace Sabbajohn\PulsePhp\DTOs\Concerns;

trait FiltersPayload
{
    private function filterPayload(array $payload): array
    {
        return array_filter($payload, fn (mixed $value) => $value !== null);
    }
}
