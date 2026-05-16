<?php

namespace Sabbajohn\PulsePhp\Services;

use Sabbajohn\PulsePhp\DTOs\AutomationTriggerPayload;

class AutomationService extends AbstractService
{
    public function list(array $query = []): array
    {
        return $this->client->get('automations', $query);
    }

    public function create(array $payload): array
    {
        return $this->client->post('automations', $payload);
    }

    public function trigger(string|AutomationTriggerPayload $event, array $payload = [], ?array $contact = null, ?array $context = null): array
    {
        $body = $event instanceof AutomationTriggerPayload
            ? $event->toArray()
            : (new AutomationTriggerPayload($event, $payload, $contact, $context))->toArray();

        return $this->client->post('automations/trigger', $body);
    }

    public function show(int|string $automationId): array
    {
        return $this->client->get("automations/{$automationId}");
    }

    public function update(int|string $automationId, array $payload): array
    {
        return $this->client->patch("automations/{$automationId}", $payload);
    }

    public function delete(int|string $automationId): array
    {
        return $this->client->delete("automations/{$automationId}");
    }

    public function toggle(int|string $automationId): array
    {
        return $this->client->post("automations/{$automationId}/toggle");
    }

    public function run(int|string $automationId, array $payload = []): array
    {
        return $this->client->post("automations/{$automationId}/run", $payload);
    }
}
