<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sabbajohn\PulsePhp\Exceptions\AuthenticationException;
use Sabbajohn\PulsePhp\Exceptions\ConflictException;
use Sabbajohn\PulsePhp\Exceptions\NotFoundException;
use Sabbajohn\PulsePhp\Exceptions\RateLimitException;
use Sabbajohn\PulsePhp\Exceptions\RemoteException;
use Sabbajohn\PulsePhp\Exceptions\RequestFailedException;
use Sabbajohn\PulsePhp\Exceptions\ValidationException;
use Sabbajohn\PulsePhp\PulseClient;

class ProvisioningSecurityTest extends TestCase
{
    #[DataProvider('invalidUrls')]
    public function test_invalid_or_plain_http_urls_never_send_payload_or_credential(string $url): void
    {
        $history = [];
        $client = $this->client($url, [new Response(201, [], '{}')], $history);
        try {
            $client->provisioning()->createClient(['external_customer_id' => 'fake-customer'], 'stable-key');
            $this->fail('Unsafe URL must fail before HTTP.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('HTTPS', $e->getMessage());
        }
        $this->assertCount(0, $history);
    }

    public static function invalidUrls(): array
    {
        return array_map(fn ($url) => [$url], ['http://pulse.test', '//pulse.test', 'https:///pulse.test',
            'https://user:password@pulse.test', 'https://pulse.test?next=http://other.test',
            'https://pulse.test/#fragment', 'https://pulse.test\\@other.test', "https://pulse.test/\n"]);
    }

    #[DataProvider('redirects')]
    public function test_redirects_never_forward_payload_even_to_another_https_host(int $status, string $target): void
    {
        $history = [];
        $client = $this->client('https://pulse.test', [new Response($status, ['Location' => $target], '{"status":"active"}'),
            new Response(201, [], '{}')], $history);
        try {
            $client->provisioning()->createClient(['external_customer_id' => 'fake-customer'], 'stable-key');
            $this->fail('Redirect must not be success.');
        } catch (RequestFailedException $e) {
            $this->assertSame($status, $e->getCode());
        }
        $this->assertCount(1, $history);
        $this->assertSame('https', $history[0]['request']->getUri()->getScheme());
        $this->assertFalse($history[0]['options']['allow_redirects']);
        $this->assertTrue($history[0]['options']['verify']);
    }

    public static function redirects(): array
    {
        $cases = [];
        foreach ([301, 302, 303, 307, 308] as $status) {
            foreach (['http://pulse.test/unsafe', 'https://other.test/unsafe'] as $target) {
                $cases[] = [$status, $target];
            }
        }

        return $cases;
    }

    public function test_secure_absolute_target_overrides_injected_http_base_without_changing_other_services(): void
    {
        $history = [];
        $client = $this->client('https://pulse.test', [new Response(201, [], '{}'), new Response(200, [], '{}'), new Response(200, [], '{}')], $history);
        $client->provisioning()->createClient([], 'stable-key');
        $client->get('health');
        $client->get('templates/provisioning');
        $this->assertSame('https://pulse.test/api/v2/provisioning/clients', (string) $history[0]['request']->getUri());
        $this->assertFalse($history[0]['options']['allow_redirects']);
        $this->assertTrue($history[0]['options']['verify']);
        $this->assertSame('http', $history[1]['request']->getUri()->getScheme());
        $this->assertIsArray($history[1]['options']['allow_redirects']);
        $this->assertFalse($history[1]['options']['verify']);
        $this->assertSame('http', $history[2]['request']->getUri()->getScheme());
        $this->assertIsArray($history[2]['options']['allow_redirects']);
    }

    public function test_raw_provisioning_and_activation_paths_cannot_bypass_transport_policy(): void
    {
        foreach (['provisioning/clients', 'provisioning/activations', 'x/../provisioning/clients',
            'provisioning%2Fclients', 'https://other.test/api/v2/provisioning/clients'] as $endpoint) {
            $history = [];
            $client = $this->client('http://pulse.test', [new Response(201, [], '{}')], $history);
            try {
                $client->request('POST', $endpoint, ['json' => ['private' => 'fake']]);
                $this->fail('Provisioning path must require HTTPS.');
            } catch (InvalidArgumentException $e) {
                $this->assertCount(0, $history);
            }
        }
    }

    #[DataProvider('errorBodies')]
    public function test_http_status_classification_survives_html_and_malformed_json(int $status, string $body, string $exception): void
    {
        $history = [];
        $client = $this->client('https://pulse.test', [new Response($status, ['Retry-After' => '120'], $body)], $history);
        try {
            $client->provisioning()->createClient([], 'stable-key');
            $this->fail('Error status must throw.');
        } catch (\Sabbajohn\PulsePhp\Exceptions\PulseException $e) {
            $this->assertInstanceOf($exception, $e);
            $this->assertSame($status, $e->getCode());
            if (in_array($status, [429, 503], true)) {
                $this->assertSame('120', $e->retryAfter());
            }
        }
    }

    public static function errorBodies(): array
    {
        $cases = [];
        foreach ([401 => AuthenticationException::class, 403 => AuthenticationException::class,
            404 => NotFoundException::class, 409 => ConflictException::class, 422 => ValidationException::class,
            429 => RateLimitException::class, 503 => RemoteException::class] as $status => $exception) {
            foreach (['<html>gateway error</html>', '{malformed'] as $body) {
                $cases[] = [$status, $body, $exception];
            }
        }

        return $cases;
    }

    public function test_zero_retry_after_header_is_preserved(): void
    {
        $history = [];
        $client = $this->client('https://pulse.test', [new Response(429, ['Retry-After' => '0'], '<html>limit</html>')], $history);
        try {
            $client->provisioning()->createClient([], 'stable-key');
            $this->fail('Expected rate limit.');
        } catch (RateLimitException $e) {
            $this->assertSame('0', $e->retryAfter());
        }
    }

    private function client(string $url, array $responses, array &$history): PulseClient
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($history));
        $http = new Client(['base_uri' => 'http://injected.test/api/v2/', 'handler' => $stack,
            'http_errors' => false, 'allow_redirects' => true, 'verify' => false]);

        return new PulseClient($url, 'fake-partner-secret', [], $http);
    }
}
