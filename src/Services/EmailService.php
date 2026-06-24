<?php

namespace Sabbajohn\PulsePhp\Services;

use Sabbajohn\PulsePhp\DTOs\EmailPayload;

class EmailService extends AbstractService
{
    public function sendSync(array|EmailPayload $payload): array
    {
        return $this->client->post('emails/send-sync', $this->payload($payload));
    }

    public function sendAsync(array|EmailPayload $payload): array
    {
        return $this->client->post('emails/send-async', $this->payload($payload));
    }

    public function send(array|EmailPayload $payload): array
    {
        return $this->client->post('emails', $this->payload($payload));
    }

    public function list(array $query = []): array
    {
        return $this->client->get('emails', $query);
    }

    public function status(int|string $emailId): array
    {
        return $this->client->get("emails/{$emailId}");
    }

    public function cancel(int|string $emailId): array
    {
        return $this->client->delete("emails/{$emailId}");
    }

    public function retry(int|string $emailId): array
    {
        return $this->client->post("emails/{$emailId}/retry");
    }

    public function ingestBounce(array $payload): array
    {
        return $this->client->post('emails/bounces', $payload);
    }

    public function stats(): array
    {
        return $this->client->get('stats');
    }

    public function testSmtp(array $payload): array
    {
        return $this->client->post('smtp/test', $payload);
    }
}
