<?php

namespace Sabbajohn\PulsePhp\Services;

use Sabbajohn\PulsePhp\DTOs\WhatsAppMessagePayload;

class WhatsAppService extends AbstractService
{
    public function config(): array
    {
        return $this->client->get('whatsapp/config');
    }

    public function updateConfig(array $payload): array
    {
        return $this->client->patch('whatsapp/config', $payload);
    }

    public function testConfig(array $payload = []): array
    {
        return $this->client->post('whatsapp/config/test', $payload);
    }

    public function instanceStatus(): array
    {
        return $this->client->get('whatsapp/instance/status');
    }

    public function generateQr(array $payload = []): array
    {
        return $this->client->post('whatsapp/instance/qr', $payload);
    }

    public function configureWebhook(array $payload = []): array
    {
        return $this->client->post('whatsapp/instance/webhook', $payload);
    }

    public function restartInstance(array $payload = []): array
    {
        return $this->client->post('whatsapp/instance/restart', $payload);
    }

    public function messages(array $query = []): array
    {
        return $this->client->get('whatsapp/messages', $query);
    }

    public function sendMessage(array|WhatsAppMessagePayload $payload): array
    {
        return $this->client->post('whatsapp/messages/send', $this->payload($payload));
    }
}
