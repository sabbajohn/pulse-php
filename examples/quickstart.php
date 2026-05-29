<?php

declare(strict_types=1);

use Sabbajohn\PulsePhp\PulseClient;

require __DIR__.'/../vendor/autoload.php';

$pulse = new PulseClient(
    getenv('PULSE_BASE_URL') ?: 'https://pulse.example.com',
    getenv('PULSE_API_TOKEN') ?: 'api-token',
);

$health = $pulse->get('health');

echo "Health status: ".($health['status'] ?? 'unknown').PHP_EOL;
