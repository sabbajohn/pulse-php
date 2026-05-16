<?php

namespace Sabbajohn\PulsePhp\DTOs;

use Sabbajohn\PulsePhp\DTOs\Concerns\FiltersPayload;

class AudienceMemberPayload implements PulsePayload
{
    use FiltersPayload;

    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $email = null,
        public readonly ?string $whatsappNumber = null,
        public readonly ?array $tags = null,
        public readonly ?array $customFields = null,
        public readonly ?array $metadata = null,
    ) {
    }

    public function toArray(): array
    {
        return $this->filterPayload([
            'name' => $this->name,
            'email' => $this->email,
            'whatsapp_number' => $this->whatsappNumber,
            'tags' => $this->tags,
            'custom_fields' => $this->customFields,
            'metadata' => $this->metadata,
        ]);
    }
}
