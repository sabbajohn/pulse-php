<?php

namespace Sabbajohn\PulsePhp\DTOs;

use Sabbajohn\PulsePhp\DTOs\Concerns\FiltersPayload;

class WhatsAppMessagePayload implements PulsePayload
{
    use FiltersPayload;

    public function __construct(
        public readonly string $number,
        public readonly string $text,
        public readonly ?int $whatsappConfigId = null,
        public readonly ?array $media = null,
        public readonly ?array $metadata = null,
    ) {
    }

    public function toArray(): array
    {
        return $this->filterPayload([
            'number' => $this->number,
            'text' => $this->text,
            'whatsapp_config_id' => $this->whatsappConfigId,
            'media' => $this->media,
            'metadata' => $this->metadata,
        ]);
    }
}
