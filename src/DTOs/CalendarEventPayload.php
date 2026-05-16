<?php

namespace Sabbajohn\PulsePhp\DTOs;

use Sabbajohn\PulsePhp\DTOs\Concerns\FiltersPayload;

class CalendarEventPayload implements PulsePayload
{
    use FiltersPayload;

    public function __construct(
        public readonly string $title,
        public readonly string $startsAt,
        public readonly string $endsAt,
        public readonly ?string $description = null,
        public readonly ?string $location = null,
        public readonly ?string $meetingUrl = null,
        public readonly ?string $timezone = null,
        public readonly ?string $organizerName = null,
        public readonly ?string $organizerEmail = null,
        public readonly ?int $smtpConfigId = null,
        public readonly ?bool $sendInvites = null,
        public readonly ?array $attendees = null,
        public readonly ?string $inviteMessage = null,
        public readonly ?string $filename = null,
        public readonly ?int $reminderMinutes = null,
    ) {
    }

    public function toArray(): array
    {
        return $this->filterPayload([
            'title' => $this->title,
            'description' => $this->description,
            'location' => $this->location,
            'meeting_url' => $this->meetingUrl,
            'timezone' => $this->timezone,
            'starts_at' => $this->startsAt,
            'ends_at' => $this->endsAt,
            'organizer_name' => $this->organizerName,
            'organizer_email' => $this->organizerEmail,
            'smtp_config_id' => $this->smtpConfigId,
            'send_invites' => $this->sendInvites,
            'attendees' => $this->attendees,
            'invite_message' => $this->inviteMessage,
            'filename' => $this->filename,
            'reminder_minutes' => $this->reminderMinutes,
        ]);
    }
}
