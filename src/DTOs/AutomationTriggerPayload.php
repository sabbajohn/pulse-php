<?php

namespace Sabbajohn\PulsePhp\DTOs;

use Sabbajohn\PulsePhp\DTOs\Concerns\FiltersPayload;

class AutomationTriggerPayload implements PulsePayload
{
    use FiltersPayload;

    public function __construct(
        public readonly string $eventName,
        public readonly array $payload = [],
        public readonly ?array $contact = null,
        public readonly ?array $context = null,
    ) {
    }

    public function toArray(): array
    {
        return $this->filterPayload([
            'event_name' => $this->eventName,
            'payload' => $this->payload,
            'contact' => $this->contact,
            'context' => $this->context,
        ]);
    }
}
