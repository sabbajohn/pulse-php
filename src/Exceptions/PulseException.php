<?php

namespace Sabbajohn\PulsePhp\Exceptions;

class PulseException extends \RuntimeException
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null,
        private readonly ?string $retryAfter = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function retryAfter(): ?string
    {
        return $this->retryAfter;
    }
}
