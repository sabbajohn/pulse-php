<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sabbajohn\PulsePhp\Exceptions\RequestFailedException;
use Sabbajohn\PulsePhp\PulseClient;

class ProvisioningTargetNormalizationTest extends TestCase
{
    #[DataProvider('canonicalTargets')]
    public function test_direct_calls_normalize_before_classification_and_never_send_over_http(string $input, string $canonical, string $method): void
    {
        $history = [];
        $client = $this->client('http://configured.test', $history);
        try {
            $this->call($client, $method, $input);
            $this->fail('Plain HTTP must fail before request, including multiple leading slashes.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('HTTPS', $e->getMessage());
        }
        $this->assertCount(0, $history);
    }

    #[DataProvider('canonicalTargets')]
    public function test_direct_https_calls_send_the_canonical_target_once_and_refuse_downgrade_redirect(string $input, string $canonical, string $method): void
    {
        $history = [];
        $client = $this->client('https://configured.test', $history);
        try {
            $this->call($client, $method, $input);
            $this->fail('307 must not forward the credential/payload.');
        } catch (RequestFailedException $e) {
            $this->assertSame(307, $e->getCode());
        }
        $this->assertCount(1, $history);
        $this->assertSame('https://configured.test/api/v2/'.$canonical, (string) $history[0]['request']->getUri());
        $this->assertSame($method === 'request' ? 'POST' : strtoupper($method), $history[0]['request']->getMethod());
        $this->assertSame('Bearer fake-partner', $history[0]['request']->getHeaderLine('Authorization'));
        $this->assertSame(['private' => 'fake-payload'], json_decode((string) $history[0]['request']->getBody(), true));
        $this->assertFalse($history[0]['options']['allow_redirects']);
        $this->assertTrue($history[0]['options']['verify']);
    }

    public static function canonicalTargets(): array
    {
        $cases = [];
        foreach (['post', 'put', 'patch', 'delete', 'request'] as $method) {
            foreach (['provisioning/clients', 'provisioning/activations'] as $path) {
                foreach (['', '/', '//', '///', '////'] as $prefix) {
                    $cases[] = [$prefix.$path, $path, $method];
                }
            }
        }
        $cases[] = ['//%70rovisioning/%63lients', 'provisioning/clients', 'post'];
        $cases[] = ['///prov%69sioning/activations', 'provisioning/activations', 'request'];

        return $cases;
    }

    #[DataProvider('ambiguousTargets')]
    public function test_ambiguous_encoded_relative_or_query_targets_are_rejected_before_any_transport(string $input): void
    {
        foreach (['http://configured.test', 'https://configured.test'] as $base) {
            $history = [];
            $client = $this->client($base, $history);
            try {
                $client->post($input, ['private' => 'fake-payload']);
                $this->fail('Ambiguous target must be rejected before transport.');
            } catch (InvalidArgumentException $e) {
                $this->assertCount(0, $history);
            }
        }
    }

    public static function ambiguousTargets(): array
    {
        $paths = [];
        foreach (['provisioning/clients', 'provisioning/activations'] as $path) {
            foreach (['', '/', '//', '///'] as $prefix) {
                $paths[] = [$prefix.$path.'?next=http://other.test'];
                $paths[] = [$prefix.$path.'#fragment'];
            }
            foreach (['./', '../', 'x/../', '%2e/', 'x/%2e%2e/', '%2f', '%2F%2f', '%252f', '%2570refix/'] as $prefix) {
                $paths[] = [$prefix.$path];
            }
        }
        foreach (['provisioning%2fclients', 'provisioning%252Fclients', 'provisioning//clients',
            'provisioning/%2e%2e/provisioning/clients', 'provisioning/clients%3fnext=other',
            'provisioning%5cclients', 'prov%2569sioning/clients', 'provisioning/%00clients',
            'provisioning/clients%23fragment', '//other.test/api/v2/provisioning/clients',
            '///api/v2/provisioning/activations', 'https://other.test/api/v2/provisioning/clients'] as $path) {
            $paths[] = [$path];
        }

        return $paths;
    }

    public function test_normal_services_keep_query_options_and_non_routing_encoded_identifiers(): void
    {
        $history = [];
        $stack = HandlerStack::create(new MockHandler([new Response(200, [], '{}')]));
        $stack->push(Middleware::history($history));
        $client = new PulseClient('http://configured.test', 'fake', [], new Client([
            'base_uri' => 'http://configured.test/api/v2/', 'handler' => $stack, 'http_errors' => false,
        ]));
        $client->get('//contacts/a%40b', ['search' => 'provisioning/clients?next=http://other.test']);
        $this->assertCount(1, $history);
        $this->assertSame('/api/v2/contacts/a%40b', $history[0]['request']->getUri()->getPath());
        $this->assertSame('http', $history[0]['request']->getUri()->getScheme());
        parse_str($history[0]['request']->getUri()->getQuery(), $query);
        $this->assertSame('provisioning/clients?next=http://other.test', $query['search']);
    }

    public function test_client_or_request_base_uri_cannot_hide_provisioning_behind_a_relative_alias(): void
    {
        foreach (['http://configured.test', 'https://configured.test'] as $configured) {
            foreach (['clients', 'activations'] as $alias) {
                foreach ([false, true] as $override) {
                    $history = [];
                    $client = $this->client($configured, $history,
                        $override ? 'http://injected.test/api/v2/' : 'http://injected.test/api/v2/provisioning/');
                    try {
                        $client->request('POST', $alias, ['json' => ['private' => 'fake-payload']]
                            + ($override ? ['base_uri' => 'http://injected.test/api/v2//%70rovisioning/'] : []));
                        $this->fail('Indirect provisioning target must fail before HTTP.');
                    } catch (InvalidArgumentException $e) {
                        $this->assertCount(0, $history);
                    }
                }
            }
        }
    }

    private function call(PulseClient $client, string $method, string $input): void
    {
        if ($method === 'request') {
            $client->request('POST', $input, ['json' => ['private' => 'fake-payload'],
                'allow_redirects' => true, 'verify' => false]);
        } else {
            $client->{$method}($input, ['private' => 'fake-payload']);
        }
    }

    private function client(string $base, array &$history, string $injectedBase = 'http://injected.test/api/v2/'): PulseClient
    {
        $stack = HandlerStack::create(new MockHandler([
            new Response(307, ['Location' => 'http://downgrade.test/received'], '{}'),
            new Response(201, [], '{}'),
        ]));
        $stack->push(Middleware::history($history));

        return new PulseClient($base, 'fake-partner', [], new Client([
            'base_uri' => $injectedBase, 'handler' => $stack, 'http_errors' => false,
            'allow_redirects' => true, 'verify' => false,
        ]));
    }
}
