<?php

namespace Sabbajohn\PulsePhp;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use Psr\Http\Message\ResponseInterface;
use Sabbajohn\PulsePhp\Exceptions\AuthenticationException;
use Sabbajohn\PulsePhp\Exceptions\ConflictException;
use Sabbajohn\PulsePhp\Exceptions\NotFoundException;
use Sabbajohn\PulsePhp\Exceptions\PulseException;
use Sabbajohn\PulsePhp\Exceptions\RateLimitException;
use Sabbajohn\PulsePhp\Exceptions\RemoteException;
use Sabbajohn\PulsePhp\Exceptions\RequestFailedException;
use Sabbajohn\PulsePhp\Exceptions\ValidationException;
use Sabbajohn\PulsePhp\Services\AudienceService;
use Sabbajohn\PulsePhp\Services\AutomationService;
use Sabbajohn\PulsePhp\Services\CalendarService;
use Sabbajohn\PulsePhp\Services\CampaignService;
use Sabbajohn\PulsePhp\Services\ComposerService;
use Sabbajohn\PulsePhp\Services\ContactService;
use Sabbajohn\PulsePhp\Services\EmailService;
use Sabbajohn\PulsePhp\Services\ProvisioningService;
use Sabbajohn\PulsePhp\Services\TemplateService;
use Sabbajohn\PulsePhp\Services\WhatsAppService;

class PulseClient
{
    private readonly ClientInterface $httpClient;

    private ?ProvisioningService $provisioningService = null;

    private ?EmailService $emailService = null;

    private ?TemplateService $templateService = null;

    private ?ComposerService $composerService = null;

    private ?CampaignService $campaignService = null;

    private ?AudienceService $audienceService = null;

    private ?ContactService $contactService = null;

    private ?AutomationService $automationService = null;

    private ?CalendarService $calendarService = null;

    private ?WhatsAppService $whatsAppService = null;

    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiToken,
        private readonly array $options = [],
        ?ClientInterface $httpClient = null,
    ) {
        $this->httpClient = $httpClient ?? new GuzzleClient(array_merge($this->options, [
            'base_uri' => $this->normalizeBaseUrl($this->baseUrl),
            'timeout' => $this->options['timeout'] ?? 30,
            'http_errors' => false,
        ]));
    }

    public function provisioning(): ProvisioningService
    {
        return $this->provisioningService ??= new ProvisioningService($this);
    }

    public function emails(): EmailService
    {
        return $this->emailService ??= new EmailService($this);
    }

    public function templates(): TemplateService
    {
        return $this->templateService ??= new TemplateService($this);
    }

    public function composer(): ComposerService
    {
        return $this->composerService ??= new ComposerService($this);
    }

    public function campaigns(): CampaignService
    {
        return $this->campaignService ??= new CampaignService($this);
    }

    public function audiences(): AudienceService
    {
        return $this->audienceService ??= new AudienceService($this);
    }

    public function contacts(): ContactService
    {
        return $this->contactService ??= new ContactService($this);
    }

    public function automations(): AutomationService
    {
        return $this->automationService ??= new AutomationService($this);
    }

    public function calendar(): CalendarService
    {
        return $this->calendarService ??= new CalendarService($this);
    }

    public function whatsapp(): WhatsAppService
    {
        return $this->whatsAppService ??= new WhatsAppService($this);
    }

    public function get(string $endpoint, array $query = []): array
    {
        return $this->request('GET', $endpoint, ['query' => $query]);
    }

    public function post(string $endpoint, array $payload = []): array
    {
        return $this->request('POST', $endpoint, ['json' => $payload]);
    }

    public function put(string $endpoint, array $payload = []): array
    {
        return $this->request('PUT', $endpoint, ['json' => $payload]);
    }

    public function patch(string $endpoint, array $payload = []): array
    {
        return $this->request('PATCH', $endpoint, ['json' => $payload]);
    }

    public function delete(string $endpoint, array $payload = []): array
    {
        return $this->request('DELETE', $endpoint, $payload === [] ? [] : ['json' => $payload]);
    }

    public function request(string $method, string $endpoint, array $options = []): array
    {
        // Normalize before URI parsing/classification. In particular, leading
        // slashes must never be parsed as authority and then stripped at send.
        $target = $this->normalizeRequestTarget($endpoint);
        $path = (string) parse_url($target, PHP_URL_PATH);
        $provisioning = preg_match('#(?:^/?provisioning|(?:^|/)api/v2/provisioning)(?:/|$)#i', $path) === 1;
        if ($provisioning) {
            $target = $this->secureProvisioningUrl($target);
            $options = array_replace($options, ['allow_redirects' => false, 'verify' => true, 'http_errors' => false]);
        } else {
            // Resolve once before sending, so base_uri/options cannot hide a
            // provisioning route behind an ordinary-looking alias like clients.
            $base = $options['base_uri'] ?? $this->httpClient->getConfig('base_uri');
            $resolved = \GuzzleHttp\Psr7\UriResolver::resolve(
                \GuzzleHttp\Psr7\Utils::uriFor($base ?? ''),
                \GuzzleHttp\Psr7\Utils::uriFor($target),
            );
            $effectivePath = $resolved->getPath();
            if (preg_match('/%(?:25|2f|5c|3f|23|0[0-9a-f]|1[0-9a-f]|20|7f)/i', $effectivePath)) {
                throw new \InvalidArgumentException('Ambiguous effective request target.');
            }
            if (preg_match('#(?:^|/)api/v2/provisioning(?:/|$)#i',
                preg_replace('#/+#', '/', rawurldecode($effectivePath)))) {
                throw new \InvalidArgumentException('Use a canonical provisioning endpoint with a valid HTTPS base URL.');
            }
            $target = (string) $resolved;
        }
        $requestOptions = array_merge_recursive([
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$this->apiToken,
                'User-Agent' => 'sabbajohn/pulse-php',
            ],
        ], $options);

        try {
            $response = $this->httpClient->request($method, $target, $requestOptions);
        } catch (GuzzleRequestException $exception) {
            if ($exception->hasResponse()) {
                $response = $exception->getResponse();
            } else {
                throw new RequestFailedException($exception->getMessage(), (int) $exception->getCode(), $exception);
            }
        } catch (GuzzleException $exception) {
            throw new PulseException($exception->getMessage(), (int) $exception->getCode(), $exception);
        }

        if ($provisioning && $response->getStatusCode() >= 300 && $response->getStatusCode() < 400) {
            throw new RequestFailedException('Pulse provisioning redirects are not allowed.', $response->getStatusCode());
        }

        return $this->handleResponse($response);
    }

    private function handleResponse(ResponseInterface $response): array
    {
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();
        $payload = $body !== '' ? json_decode($body, true) : [];

        $validJson = is_array($payload);
        $payload = $validJson ? $payload : [];
        $message = $payload['message'] ?? $payload['error'] ?? null;
        $message = is_string($message) ? $message : "Pulse request failed with status {$statusCode}.";
        $retryAfter = $response->getHeaderLine('Retry-After');
        $retryAfter = $retryAfter !== '' ? $retryAfter : null;

        if ($statusCode === 401 || $statusCode === 403) {
            throw new AuthenticationException($message, $statusCode);
        }

        if ($statusCode === 404) {
            throw new NotFoundException($message, $statusCode);
        }

        if ($statusCode === 409) {
            throw new ConflictException($message, $statusCode);
        }

        if ($statusCode === 422) {
            throw new ValidationException($message, is_array($payload['errors'] ?? null) ? $payload['errors'] : [], $statusCode);
        }

        if ($statusCode === 429) {
            throw new RateLimitException($message, $statusCode, null, $retryAfter);
        }

        if ($statusCode >= 500) {
            throw new RemoteException($message, $statusCode, null, $retryAfter);
        }

        if ($statusCode >= 400) {
            throw new RequestFailedException($message, $statusCode);
        }

        if ($body !== '' && ! $validJson) {
            throw new RemoteException('Pulse returned a non-JSON response.', $statusCode, null, $retryAfter);
        }

        return $payload;
    }

    private function normalizeRequestTarget(string $endpoint): string
    {
        $target = ltrim($endpoint, '/');
        if (preg_match('/[\x00-\x20\x7f\\\\]/', $target)) {
            throw new \InvalidArgumentException('Ambiguous request target.');
        }
        $uri = new \GuzzleHttp\Psr7\Uri($target);
        $path = $uri->getPath();
        // Nested escapes and encoded routing delimiters can be interpreted
        // differently by a proxy/router. Reject them instead of guessing.
        if (preg_match('/%(?:25|2f|5c|3f|23|0[0-9a-f]|1[0-9a-f]|20|7f)/i', $path)) {
            throw new \InvalidArgumentException('Encoded routing delimiters are not supported.');
        }
        // RFC unreserved escapes have exactly the same routing meaning. Decode
        // them once and send that same canonical representation (e.g. %70 -> p).
        $path = preg_replace_callback('/%([0-9a-f]{2})/i', static function (array $match): string {
            $character = chr(hexdec($match[1]));

            return preg_match('/^[A-Za-z0-9._~-]$/D', $character) ? $character : strtoupper($match[0]);
        }, $path);
        if (str_contains($path, '//') || preg_match('#(?:^|/)\.{1,2}(?:/|$)#', $path)) {
            throw new \InvalidArgumentException('Relative dot segments and repeated path separators are not supported.');
        }

        return (string) $uri->withPath($path);
    }

    private function secureProvisioningUrl(string $endpoint): string
    {
        $url = $this->baseUrl;
        $parts = parse_url($url);
        if (! filter_var($url, FILTER_VALIDATE_URL) || ! is_array($parts)
            || strtolower($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])
            || isset($parts['user']) || isset($parts['pass'])
            || isset($parts['query']) || isset($parts['fragment'])
            || preg_match('/[\x00-\x20\x7f\\\\]/', $url)
            || ! preg_match('#^provisioning/[A-Za-z0-9/_-]+$#D', $endpoint)) {
            throw new \InvalidArgumentException('Provisioning requires a valid HTTPS base URL and a relative provisioning endpoint.');
        }

        // Absolute target overrides an injected HTTP client's base_uri. Per-call
        // options above override unsafe client defaults only for provisioning.
        return $this->normalizeBaseUrl($url).$endpoint;
    }

    private function normalizeBaseUrl(string $baseUrl): string
    {
        $baseUrl = rtrim($baseUrl, '/');

        if (! str_ends_with($baseUrl, '/api/v2')) {
            $baseUrl .= '/api/v2';
        }

        return $baseUrl.'/';
    }
}
