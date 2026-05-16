<?php

namespace Sabbajohn\PulsePhp\Services;

use Sabbajohn\PulsePhp\DTOs\TemplatePayload;

class TemplateService extends AbstractService
{
    public function meta(): array
    {
        return $this->client->get('templates/meta');
    }

    public function list(array $query = []): array
    {
        return $this->client->get('templates', $query);
    }

    public function create(array|TemplatePayload $payload): array
    {
        return $this->client->post('templates', $this->payload($payload));
    }

    public function show(int|string $templateId): array
    {
        return $this->client->get("templates/{$templateId}");
    }

    public function update(int|string $templateId, array|TemplatePayload $payload): array
    {
        return $this->client->patch("templates/{$templateId}", $this->payload($payload));
    }

    public function delete(int|string $templateId): array
    {
        return $this->client->delete("templates/{$templateId}");
    }

    public function preview(array $payload): array
    {
        return $this->client->post('templates/preview', $payload);
    }
}
