<?php

namespace Sabbajohn\PulsePhp\Services;

use Sabbajohn\PulsePhp\DTOs\CalendarEventPayload;

class CalendarService extends AbstractService
{
    public function items(array $query = []): array
    {
        return $this->client->get('calendar/items', $query);
    }

    public function createEvent(array|CalendarEventPayload $payload): array
    {
        return $this->client->post('calendar/events', $this->payload($payload));
    }

    public function deleteEvent(int|string $eventId): array
    {
        return $this->client->delete("calendar/events/{$eventId}");
    }
}
