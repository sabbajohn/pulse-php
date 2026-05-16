<?php

namespace Sabbajohn\PulsePhp\Services;

use Sabbajohn\PulsePhp\DTOs\PulsePayload;
use Sabbajohn\PulsePhp\PulseClient;

abstract class AbstractService
{
    public function __construct(protected readonly PulseClient $client)
    {
    }

    protected function payload(array|PulsePayload $payload): array
    {
        return $payload instanceof PulsePayload ? $payload->toArray() : $payload;
    }
}
