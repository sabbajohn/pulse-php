<?php

namespace Sabbajohn\PulsePhp\Services;

use Sabbajohn\PulsePhp\DTOs\AudienceMemberPayload;

class AudienceService extends AbstractService
{
    public function list(array $query = []): array
    {
        return $this->client->get('audiences', $query);
    }

    public function create(array $payload): array
    {
        return $this->client->post('audiences', $payload);
    }

    public function show(int|string $audienceId): array
    {
        return $this->client->get("audiences/{$audienceId}");
    }

    public function update(int|string $audienceId, array $payload): array
    {
        return $this->client->patch("audiences/{$audienceId}", $payload);
    }

    public function delete(int|string $audienceId): array
    {
        return $this->client->delete("audiences/{$audienceId}");
    }

    public function channels(array $query = []): array
    {
        return $this->client->get('audience/channels', $query);
    }

    public function members(array $query = []): array
    {
        return $this->client->get('audience/members', $query);
    }

    public function createMember(array|AudienceMemberPayload $payload): array
    {
        return $this->client->post('audience/members', $this->payload($payload));
    }

    public function member(int|string $memberId): array
    {
        return $this->client->get("audience/members/{$memberId}");
    }

    public function updateMember(int|string $memberId, array|AudienceMemberPayload $payload): array
    {
        return $this->client->patch("audience/members/{$memberId}", $this->payload($payload));
    }

    public function deleteMember(int|string $memberId): array
    {
        return $this->client->delete("audience/members/{$memberId}");
    }

    public function transitionMember(int|string $memberId, array $payload): array
    {
        return $this->client->post("audience/members/{$memberId}/transition", $payload);
    }

    public function tags(array $query = []): array
    {
        return $this->client->get('audience/tags', $query);
    }

    public function segments(array $query = []): array
    {
        return $this->client->get('audience/segments', $query);
    }
}
