<?php

namespace Sabbajohn\PulsePhp\DTOs;

use Sabbajohn\PulsePhp\DTOs\Concerns\FiltersPayload;

class TemplatePayload implements PulsePayload
{
    use FiltersPayload;

    public function __construct(
        public readonly string $name,
        public readonly string $templateSourceType,
        public readonly ?string $channel = null,
        public readonly ?string $category = null,
        public readonly ?string $description = null,
        public readonly ?string $subject = null,
        public readonly ?array $builderSchema = null,
        public readonly ?string $templateSource = null,
        public readonly ?string $compiledHtml = null,
        public readonly ?string $compiledText = null,
        public readonly ?array $mergeTags = null,
        public readonly ?array $settings = null,
        public readonly ?bool $isActive = null,
    ) {
    }

    public function toArray(): array
    {
        return $this->filterPayload([
            'name' => $this->name,
            'channel' => $this->channel,
            'category' => $this->category,
            'description' => $this->description,
            'subject' => $this->subject,
            'template_source_type' => $this->templateSourceType,
            'builder_schema' => $this->builderSchema,
            'template_source' => $this->templateSource,
            'compiled_html' => $this->compiledHtml,
            'compiled_text' => $this->compiledText,
            'merge_tags' => $this->mergeTags,
            'settings' => $this->settings,
            'is_active' => $this->isActive,
        ]);
    }
}
