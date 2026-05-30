<?php

namespace Sabbajohn\PulsePhp\Services;

class ContactService extends AbstractService
{
    public function list(array $query = []): array
    {
        return $this->client->get('contacts', $query);
    }

    public function create(array $payload): array
    {
        return $this->client->post('contacts', $payload);
    }

    public function show(int|string $contactId): array
    {
        return $this->client->get("contacts/{$contactId}");
    }

    public function update(int|string $contactId, array $payload): array
    {
        return $this->client->patch("contacts/{$contactId}", $payload);
    }

    public function replace(int|string $contactId, array $payload): array
    {
        return $this->client->put("contacts/{$contactId}", $payload);
    }

    public function delete(int|string $contactId): array
    {
        return $this->client->delete("contacts/{$contactId}");
    }
}
