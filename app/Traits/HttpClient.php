<?php

namespace App\Traits;

use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait HttpClient
{
    protected ?string $baseUrl = null;
    protected int $timeout = 30;
    protected int $retryTimes = 0;
    protected int $retryDelay = 2000; // milliseconds
    protected array $defaultHeaders = [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ];

    /**
     * Configure the HTTP client
     */
    protected function configureHttpClient(array $config = []): void
    {
        $this->baseUrl = $config['base_url'] ?? $this->baseUrl;
        $this->timeout = $config['timeout'] ?? $this->timeout;
        $this->retryTimes = $config['retry_count'] ?? $this->retryTimes;
        $this->retryDelay = $config['retry_delay'] ?? $this->retryDelay;

        if (isset($config['default_headers'])) {
            $this->defaultHeaders = array_merge($this->defaultHeaders, $config['default_headers']);
        }
    }

    /**
     * Create a configured HTTP client instance
     */
    protected function createHttpClient(array $options = []): PendingRequest
    {
        $client = Http::withHeaders($this->defaultHeaders)
            ->timeout($options['timeout'] ?? $this->timeout);

        if ($this->baseUrl) {
            $client->baseUrl($this->baseUrl);
        }

        if (isset($options['headers'])) {
            $client->withHeaders($options['headers']);
        }

        if (isset($options['token'])) {
            $client->withToken($options['token']);
        }

        if (isset($options['bearer_token'])) {
            $client->withToken($options['bearer_token']);
        }

        if ($this->retryTimes > 0) {
            $client->retry($this->retryTimes, $this->retryDelay);
        }

        return $client;
    }

    /**
     * Perform GET request
     * @throws Exception
     */
    protected function httpGet(string $url, array $options = []): array
    {
        try {
            $client = $this->createHttpClient($options);

            $this->logRequest('GET', $url, $options);

            $response = $client->get($url, $options['query'] ?? []);

            return $this->processResponse($response, 'GET', $url);
        } catch (Exception $e) {
            $this->logError('GET', $url, $e);
            throw $e;
        }
    }

    /**
     * Perform POST request
     * @throws Exception
     */
    protected function httpPost(string $url, array $body = [], array $options = []): array
    {
        try {
            $client = $this->createHttpClient($options);

            $this->logRequest('POST', $url, $options);

            $response = $client->post($url, $body);

            return $this->processResponse($response, 'POST', $url);
        } catch (Exception $e) {
            $this->logError('POST', $url, $e);
            throw $e;
        }
    }

    /**
     * Perform PUT request
     * @throws Exception
     */
    protected function httpPut(string $url, array $body = [], array $options = []): array
    {
        try {
            $client = $this->createHttpClient($options);

            $this->logRequest('PUT', $url, $options);

            $response = $client->put($url, $body);

            return $this->processResponse($response, 'PUT', $url);
        } catch (Exception $e) {
            $this->logError('PUT', $url, $e);
            throw $e;
        }
    }

    /**
     * Perform PATCH request
     * @throws Exception
     */
    protected function httpPatch(string $url, array $body = [], array $options = []): array
    {
        try {
            $client = $this->createHttpClient($options);

            $this->logRequest('PATCH', $url, $options);

            $response = $client->patch($url, $body);

            return $this->processResponse($response, 'PATCH', $url);
        } catch (Exception $e) {
            $this->logError('PATCH', $url, $e);
            throw $e;
        }
    }

    /**
     * Perform DELETE request
     * @throws Exception
     */
    protected function httpDelete(string $url, array $options = []): array
    {
        try {
            $client = $this->createHttpClient($options);

            $this->logRequest('DELETE', $url, $options);

            $response = $client->delete($url);

            return $this->processResponse($response, 'DELETE', $url);
        } catch (Exception $e) {
            $this->logError('DELETE', $url, $e);
            throw $e;
        }
    }

    /**
     * Process HTTP response
     * @throws Exception
     */
    protected function processResponse(Response $response, string $method, string $url): array
    {
        $this->logResponse($method, $url, $response);

        $result = [
            'success' => $response->successful(),
            'status_code' => $response->status(),
            'headers' => $response->headers(),
            'body' => $response->body(),
            'data' => null,
            'response' => $response,
        ];

        // Try to parse JSON
        try {
            $result['data'] = $response->json();
        } catch (Exception $e) {
            $result['data'] = $response->body();
        }

        // Handle error responses
        if (!$response->successful()) {
            $message = $this->extractErrorMessage($result['data'], $response->status());

            throw new Exception($message, $response->status());
        }

        return $result;
    }

    /**
     * Extract error message from response
     */
    protected function extractErrorMessage($data, int $statusCode): string
    {
        if (is_array($data)) {
            $errorFields = ['message', 'error', 'responseMessage', 'errorMessage', 'description'];

            foreach ($errorFields as $field) {
                if (!empty($data[$field])) {
                    return $data[$field];
                }
            }
        }

        return $this->getDefaultErrorMessage($statusCode);
    }

    /**
     * Get the default error message based on status code
     */
    protected function getDefaultErrorMessage(int $statusCode): string
    {
        return match ($statusCode) {
            400 => 'Bad Request',
            401 => 'Unauthorized access',
            403 => 'Forbidden access',
            404 => 'Resource not found',
            500 => 'Internal server error',
            default => "Unexpected response: {$statusCode}",
        };
    }

    /**
     * Log HTTP request
     */
    protected function logRequest(string $method, string $url, array $options): void
    {
        Log::info("HTTP Request: {$method} {$url}", [
            'method' => $method,
            'url' => $url,
            'options' => $options,
        ]);
    }

    /**
     * Log HTTP response
     */
    protected function logResponse(string $method, string $url, Response $response): void
    {
        Log::info("HTTP Response: {$method} {$url}", [
            'method' => $method,
            'url' => $url,
            'status_code' => $response->status(),
            'duration' => $response->transferStats?->getTransferTime() ?? 'N/A',
        ]);

        Log::debug('Response details', [
            'headers' => $response->headers(),
            'body' => $response->body(),
        ]);
    }

    /**
     * Log HTTP error
     */
    protected function logError(string $method, string $url, Exception $e): void
    {
        Log::error("{$method} request failed: {$url}", [
            'method' => $method,
            'url' => $url,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
}
