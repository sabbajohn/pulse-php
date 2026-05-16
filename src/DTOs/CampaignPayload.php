<?php

namespace Sabbajohn\PulsePhp\DTOs;

use Sabbajohn\PulsePhp\DTOs\Concerns\FiltersPayload;

class CampaignPayload implements PulsePayload
{
    use FiltersPayload;

    public function __construct(
        public readonly string $name,
        public readonly string $channel = 'email',
        public readonly ?int $audienceId = null,
        public readonly ?string $subject = null,
        public readonly ?string $scheduledAt = null,
        public readonly ?int $templateId = null,
        public readonly ?array $builderSchema = null,
        public readonly ?string $templateSourceType = null,
        public readonly ?string $templateSource = null,
        public readonly ?string $compiledHtml = null,
        public readonly ?string $compiledText = null,
        public readonly ?array $settings = null,
    ) {
    }

    public function toArray(): array
    {
        return $this->filterPayload([
            'name' => $this->name,
            'channel' => $this->channel,
            'audience_id' => $this->audienceId,
            'subject' => $this->subject,
            'scheduled_at' => $this->scheduledAt,
            'template_id' => $this->templateId,
            'builder_schema' => $this->builderSchema,
            'template_source_type' => $this->templateSourceType,
            'template_source' => $this->templateSource,
            'compiled_html' => $this->compiledHtml,
            'compiled_text' => $this->compiledText,
            'settings' => $this->settings,
        ]);
    }
}
