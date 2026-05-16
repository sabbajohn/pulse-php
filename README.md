# sabbajohn/pulse-php

Cliente PHP framework-agnostic para a API `v2` do VoraPulse.

## Instalacao

```bash
composer require sabbajohn/pulse-php
```

## Uso

```php
use Sabbajohn\PulsePhp\PulseClient;

$pulse = new PulseClient('https://pulse.example.com', 'api-token');

$pulse->emails()->sendSync([
    'to' => [['email' => 'cliente@example.com']],
    'subject' => 'Bem-vindo',
    'html' => '<p>Ola!</p>',
]);

$pulse->automations()->trigger('pedido.criado', ['id' => 123]);
```

## Servicos

- `emails()`
- `templates()`
- `composer()`
- `campaigns()`
- `audiences()`
- `automations()`
- `calendar()`
- `whatsapp()`
