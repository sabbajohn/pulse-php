<?php

namespace Sabbajohn\PulsePhp\DTOs;

use Sabbajohn\PulsePhp\DTOs\Concerns\FiltersPayload;

class EmailPayload implements PulsePayload
{
    use FiltersPayload;

    public function __construct(
        public readonly array $to,
        public readonly string $subject,
        public readonly ?string $html = null,
        public readonly ?string $text = null,
        public readonly ?string $body = null,
        public readonly ?int $smtpConfigId = null,
        public readonly ?string $scheduledAt = null,
        public readonly ?array $attachments = null,
        public readonly ?int $templateId = null,
        public readonly ?array $mergeData = null,
        public readonly ?array $recipients = null,
        public readonly string $templateSourceType = 'html',
        public readonly ?string $templateSource = null,
        public readonly ?string $compiledHtml = null,
        public readonly ?string $compiledText = null,
        public readonly ?string $priority = null,
        public readonly ?array $metadata = null,
    ) {
    }

    public function toArray(): array
    {
        return $this->filterPayload([
            'to' => $this->to,
            'subject' => $this->subject,
            'html' => $this->html,
            'text' => $this->text,
            'body' => $this->body,
            'smtp_config_id' => $this->smtpConfigId,
            'scheduled_at' => $this->scheduledAt,
            'attachments' => $this->attachments,
            'template_id' => $this->templateId,
            'merge_data' => $this->mergeData,
            'recipients' => $this->recipients,
            'template_source_type' => $this->templateSourceType,
            'template_source' => $this->templateSource,
            'compiled_html' => $this->compiledHtml,
            'compiled_text' => $this->compiledText,
            'priority' => $this->priority,
            'metadata' => $this->metadata,
        ]);
    }
}
