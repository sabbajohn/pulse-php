<?php

namespace Sabbajohn\PulsePhp\Exceptions;

class ValidationException extends RequestFailedException
{
    public function __construct(string $message, private readonly array $errors = [], int $code = 422)
    {
        parent::__construct($message, $code);
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
