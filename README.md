# sabbajohn/pulse-php

Framework-agnostic PHP client for the public `v2` VoraPulse API.

## Contract

The SDK contract is defined by the Pulse public OpenAPI spec:

- `https://github.com/vora-sys/Pulse/blob/main/VoraPulse/docs/openapi/pulse-public-v2.openapi.json`

Administrative, billing, observability and other internal endpoints are intentionally out of scope.

## Compatibility

- PHP `^8.2`
- Public API base: `/api/v2`

## Installation

```bash
composer require sabbajohn/pulse-php
```

## Quickstart

```php
use Sabbajohn\PulsePhp\PulseClient;

$pulse = new PulseClient('https://pulse.example.com', 'api-token');

$pulse->get('health');

$pulse->emails()->sendSync([
    'to' => [['email' => 'cliente@example.com', 'name' => 'Cliente']],
    'subject' => 'Bem-vindo',
    'html' => '<p>Ola!</p>',
]);

$pulse->automations()->trigger('pedido.criado', ['pedido_id' => 123]);
```

The client automatically normalizes the base URL to `/api/v2`.

## Services

- `emails()`: send sync/async, list, status, cancel, retry, stats and SMTP test
- `templates()`: meta, CRUD and preview
- `composer()`: meta, render, validate, autosave and latest revision
- `campaigns()`: list, create, preview, show and send
- `audiences()`: audiences, channels, members, tags and segments
- `contacts()`: list, create, show, update, replace and delete
- `automations()`: CRUD, trigger, toggle and manual run
- `calendar()`: items, create event and delete event
- `whatsapp()`: config, test, instance status, QR, webhook, restart, list and send message

## Errors

The public exception mapping is stable across SDKs:

- `AuthenticationException`
- `NotFoundException`
- `ValidationException`
- `RateLimitException`
- `RemoteException`
- `RequestFailedException`

## Examples

- `examples/quickstart.php`
