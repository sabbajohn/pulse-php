<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Sabbajohn\PulsePhp\Exceptions\ConflictException;
use Sabbajohn\PulsePhp\PulseClient;

class ProvisioningTest extends TestCase
{
    public function test_partner_request_header_payload_and_conflict(): void
    {
        $history = [];
        $stack = HandlerStack::create(new MockHandler([new Response(201, [], '{"status":"active"}'), new Response(409, [], '{}')]));
        $stack->push(Middleware::history($history));
        $client = new PulseClient('https://pulse.test', 'partner-token', [], new Client([
            'base_uri' => 'https://pulse.test/api/v2/', 'handler' => $stack, 'http_errors' => false,
        ]));
        $payload = ['external_customer_id' => 'client-1', 'name' => 'Client'];
        $this->assertSame('active', $client->provisioning()->createClient($payload, 'stable-key')['status']);
        $this->assertSame('/api/v2/provisioning/clients', $history[0]['request']->getUri()->getPath());
        $this->assertSame('Bearer partner-token', $history[0]['request']->getHeaderLine('Authorization'));
        $this->assertSame('stable-key', $history[0]['request']->getHeaderLine('Idempotency-Key'));
        $this->assertSame($payload, json_decode((string) $history[0]['request']->getBody(), true));
        $this->expectException(ConflictException::class);
        $client->provisioning()->createClient($payload, 'stable-key');
    }

    public function test_missing_stable_key_fails_before_network(): void
    {
        $client = new PulseClient('https://pulse.test', 'partner-token');
        $this->expectException(InvalidArgumentException::class);
        $client->provisioning()->createClient([], '');
    }
}
