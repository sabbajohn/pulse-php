<?php

namespace Sabbajohn\PulsePhp\Services;

use Sabbajohn\PulsePhp\DTOs\CampaignPayload;

class CampaignService extends AbstractService
{
    public function list(array $query = []): array
    {
        return $this->client->get('campaigns', $query);
    }

    public function create(array|CampaignPayload $payload): array
    {
        return $this->client->post('campaigns', $this->payload($payload));
    }

    public function preview(array|CampaignPayload $payload): array
    {
        return $this->client->post('campaigns/preview', $this->payload($payload));
    }

    public function show(int|string $campaignId): array
    {
        return $this->client->get("campaigns/{$campaignId}");
    }

    public function send(int|string $campaignId, array $payload = []): array
    {
        return $this->client->post("campaigns/{$campaignId}/send", $payload);
    }
}
