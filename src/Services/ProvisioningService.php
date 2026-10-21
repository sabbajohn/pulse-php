<?php

namespace Sabbajohn\PulsePhp\Services;

use InvalidArgumentException;

class ProvisioningService extends AbstractService
{
    /** Use a separate PulseClient holding the partner credential, on the server only. */
    public function createClient(array $payload, string $idempotencyKey): array
    {
        if (! preg_match('/^[A-Za-z0-9][A-Za-z0-9._:-]{7,190}$/D', $idempotencyKey)) {
            throw new InvalidArgumentException('Idempotency-Key must contain 8-191 ASCII identifier characters.');
        }

        return $this->client->request('POST', 'provisioning/clients', [
            'json' => $payload,
            'headers' => ['Idempotency-Key' => $idempotencyKey],
        ]);
    }
}
