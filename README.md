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

$pulse->emails()->ingestBounce([
    'email_id' => 154,
    'bounce_type' => 'hard',
    'recipient' => 'cliente@example.com',
    'reason' => 'Mailbox unavailable',
]);
```

The client automatically normalizes the base URL to `/api/v2`.

## Services

- `emails()`: send sync/async, list, status, cancel, retry, bounce ingestion, stats and SMTP test
- `templates()`: meta, CRUD and preview
- `composer()`: meta, render, validate, autosave and latest revision
- `campaigns()`: list, create, preview, show, update, delete and send
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

## Partner provisioning (0.1.5)

Use a separate server-side client with a partner credential. Provisioning requires a valid HTTPS base URL, always verifies TLS and refuses redirects (including HTTPS to HTTP). Unsafe injected base_uri/redirect/verify defaults cannot override this policy; ordinary services retain their own transport settings:

```php
$partner = new \Sabbajohn\PulsePhp\PulseClient($httpsUrl, $partnerToken);
$result = $partner->provisioning()->createClient([
    'external_customer_id' => 'customer-123',
    'name' => 'Customer',
], $stableIdempotencyKey);
```

Persist the key and payload before sending. After a timeout/5xx/429, retry exactly the same key and snapshot. HTTP error status is classified even when the body is HTML or malformed JSON. Retry-After is available via PulseException::retryAfter() for 429/5xx. A 409 throws `ConflictException`; stop and review the conflict. Never derive a new identity to bypass it. Store the returned workspace token encrypted and use it for ordinary operations. Partner credentials must never enter browsers, logs or client-facing proxies. This helper has no automatic retries or local persistence; use the Laravel durable integration when available.

Provisioning is disabled in the backend by default and requires operator-approved plan, scopes and activation policy. No payload field grants permissions. Replay is the original historical response; it does not reactivate or update an account. Existing HTTP clients in a consuming app require explicit integration.

Contract and integration guide belong to the Pulse backend: `docs/integracao/partner-provisioning.md`, `docs/openapi/pulse-partner-v2.openapi.json`. Local tests: `composer test` after dev dependencies install, or use the backend PHPUnit bootstrap as described in the composition guide.

### R3: direct request targets

The same transport policy applies to post/put/patch/delete/request calls for provisioning, not only createClient(). Leading slashes are normalized once before URI parsing and classification; the resulting target is used for sending without a later ltrim or rewrite. Unreserved escapes are canonicalized once (e.g. %70 to p). Provisioning is bound to the validated HTTPS base with TLS verification and redirects disabled.

Ambiguous path forms are rejected before HTTP: dot segments, repeated internal separators, encoded routing delimiters/control characters or nested percent escapes. Provisioning URLs with query/fragment or an absolute target are rejected; use the relative canonical endpoint. The effective base_uri is resolved before transport too: an injected client or per-request base_uri cannot hide a provisioning route behind an ordinary alias such as clients. Normal query options and non-routing encoded identifiers of other services remain supported. No public activation endpoint is added by this policy.
