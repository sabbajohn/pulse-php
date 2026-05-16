<?php

namespace Sabbajohn\PulsePhp;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use Psr\Http\Message\ResponseInterface;
use Sabbajohn\PulsePhp\Exceptions\AuthenticationException;
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
use Sabbajohn\PulsePhp\Services\EmailService;
use Sabbajohn\PulsePhp\Services\TemplateService;
use Sabbajohn\PulsePhp\Services\WhatsAppService;

class PulseClient
{
    private readonly ClientInterface $httpClient;
    private ?EmailService $emailService = null;
    private ?TemplateService $templateService = null;
    private ?ComposerService $composerService = null;
    private ?CampaignService $campaignService = null;
    private ?AudienceService $audienceService = null;
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
        $requestOptions = array_merge_recursive([
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$this->apiToken,
                'User-Agent' => 'sabbajohn/pulse-php',
            ],
        ], $options);

        try {
            $response = $this->httpClient->request($method, ltrim($endpoint, '/'), $requestOptions);
        } catch (GuzzleRequestException $exception) {
            if ($exception->hasResponse()) {
                return $this->handleResponse($exception->getResponse());
            }

            throw new RequestFailedException($exception->getMessage(), (int) $exception->getCode(), $exception);
        } catch (GuzzleException $exception) {
            throw new PulseException($exception->getMessage(), (int) $exception->getCode(), $exception);
        }

        return $this->handleResponse($response);
    }

    private function handleResponse(ResponseInterface $response): array
    {
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();
        $payload = $body !== '' ? json_decode($body, true) : [];

        if ($body !== '' && ! is_array($payload)) {
            throw new RemoteException('Pulse returned a non-JSON response.', $statusCode);
        }

        $message = $payload['message'] ?? $payload['error'] ?? "Pulse request failed with status {$statusCode}.";

        if ($statusCode === 401 || $statusCode === 403) {
            throw new AuthenticationException($message, $statusCode);
        }

        if ($statusCode === 404) {
            throw new NotFoundException($message, $statusCode);
        }

        if ($statusCode === 422) {
            throw new ValidationException($message, $payload['errors'] ?? [], $statusCode);
        }

        if ($statusCode === 429) {
            throw new RateLimitException($message, $statusCode);
        }

        if ($statusCode >= 500) {
            throw new RemoteException($message, $statusCode);
        }

        if ($statusCode >= 400) {
            throw new RequestFailedException($message, $statusCode);
        }

        return $payload;
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
