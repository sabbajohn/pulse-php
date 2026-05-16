<?php

namespace Sabbajohn\PulsePhp\Services;

class ComposerService extends AbstractService
{
    public function meta(): array
    {
        return $this->client->get('composer/meta');
    }

    public function render(array $payload): array
    {
        return $this->client->post('composer/render', $payload);
    }

    public function validate(array $payload): array
    {
        return $this->client->post('composer/validate', $payload);
    }

    public function autosave(array $payload): array
    {
        return $this->client->post('composer/autosave', $payload);
    }

    public function latestRevision(array $query): array
    {
        return $this->client->get('composer/revision', $query);
    }
}
