<?php

declare(strict_types=1);

namespace KraConnect\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use KraConnect\Config\KraConfig;
use KraConnect\Exceptions\ApiAuthenticationException;
use KraConnect\Exceptions\ApiException;
use KraConnect\Exceptions\ApiTimeoutException;
use KraConnect\Exceptions\RateLimitExceededException;

/**
 * HTTP Client Service
 *
 * Handles HTTP communication with the KRA GavaConnect API using Guzzle.
 * Includes retry logic with exponential backoff.
 *
 * @package KraConnect\Services
 */
class HttpClient
{
    private Client $client;
    private RetryHandler $retryHandler;

    /**
     * Create a new HTTP client.
     *
     * @param KraConfig $config The SDK configuration
     * @param RetryHandler $retryHandler The retry handler
     */
    public function __construct(
        private readonly KraConfig $config,
        RetryHandler $retryHandler
    ) {
        $this->retryHandler = $retryHandler;
        $this->client = $this->createClient();
    }

    /**
     * Make a GET request.
     *
     * @param string $endpoint The API endpoint
     * @param array<string, mixed> $params Query parameters
     * @return array<string, mixed> The response data
     * @throws ApiException
     */
    public function get(string $endpoint, array $params = []): array
    {
        return $this->request('GET', $endpoint, [
            'query' => $params
        ]);
    }

    /**
     * Make a POST request.
     *
     * @param string $endpoint The API endpoint
     * @param array<string, mixed> $data Request body data
     * @return array<string, mixed> The response data
     * @throws ApiException
     */
    public function post(string $endpoint, array $data = []): array
    {
        return $this->request('POST', $endpoint, [
            'json' => $data
        ]);
    }

    /**
     * Make a PUT request.
     *
     * @param string $endpoint The API endpoint
     * @param array<string, mixed> $data Request body data
     * @return array<string, mixed> The response data
     * @throws ApiException
     */
    public function put(string $endpoint, array $data = []): array
    {
        return $this->request('PUT', $endpoint, [
            'json' => $data
        ]);
    }

    /**
     * Make a DELETE request.
     *
     * @param string $endpoint The API endpoint
     * @return array<string, mixed> The response data
     * @throws ApiException
     */
    public function delete(string $endpoint): array
    {
        return $this->request('DELETE', $endpoint);
    }

    /**
     * Make an HTTP request with retry logic.
     *
     * @param string $method The HTTP method
     * @param string $endpoint The API endpoint
     * @param array<string, mixed> $options Request options
     * @return array<string, mixed> The response data
     * @throws ApiException
     */
    private function request(string $method, string $endpoint, array $options = []): array
    {
        return $this->retryHandler->execute(
            function () use ($method, $endpoint, $options) {
                return $this->executeRequest($method, $endpoint, $options);
            },
            $endpoint
        );
    }

    /**
     * Execute a single HTTP request.
     *
     * @param string $method The HTTP method
     * @param string $endpoint The API endpoint
     * @param array<string, mixed> $options Request options
     * @return array<string, mixed> The response data
     * @throws ApiException
     */
    private function executeRequest(string $method, string $endpoint, array $options): array
    {
        try {
            if ($this->config->isDebugEnabled()) {
                error_log(sprintf('[KRA-Connect] %s %s', $method, $endpoint));
            }

            $response = $this->client->request($method, $endpoint, $options);

            return $this->parseResponse($response, $endpoint);
        } catch (RequestException $e) {
            $this->handleRequestException($e, $endpoint);
            throw $e;
        } catch (GuzzleException $e) {
            throw ApiException::networkError($endpoint, $e->getMessage());
        }
    }

    /**
     * Parse the HTTP response.
     *
     * @param ResponseInterface $response The HTTP response
     * @param string $endpoint The endpoint that was called
     * @return array<string, mixed> The parsed response data
     * @throws ApiException
     */
    private function parseResponse(ResponseInterface $response, string $endpoint): array
    {
        $body = (string) $response->getBody();

        if (empty($body)) {
            return [];
        }

        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw ApiException::invalidResponse(
                $endpoint,
                'Response is not valid JSON: ' . json_last_error_msg()
            );
        }

        return $data;
    }

    /**
     * Handle request exceptions.
     *
     * @param RequestException $exception The request exception
     * @param string $endpoint The endpoint that was called
     * @throws ApiException
     * @throws ApiAuthenticationException
     * @throws ApiTimeoutException
     * @throws RateLimitExceededException
     */
    private function handleRequestException(RequestException $exception, string $endpoint): void
    {
        // Check for timeout
        $errno = $exception->getHandlerContext()['errno'] ?? null;
        if (
            $errno !== null
            && defined('CURLE_OPERATION_TIMEDOUT')
            && $errno === CURLE_OPERATION_TIMEDOUT
        ) {
            throw new ApiTimeoutException($endpoint, $this->config->timeout);
        }

        $response = $exception->getResponse();

        if ($response === null) {
            throw ApiException::networkError($endpoint, $exception->getMessage());
        }

        $statusCode = $response->getStatusCode();
        $responseBody = (string) $response->getBody();

        // Handle specific status codes
        switch ($statusCode) {
            case 401:
            case 403:
                throw ApiAuthenticationException::invalidApiKey();

            case 429:
                $retryAfter = (int) ($response->getHeader('Retry-After')[0] ?? 60);
                throw new RateLimitExceededException($retryAfter);

            case 408:
                throw new ApiTimeoutException($endpoint, $this->config->timeout);

            default:
                throw ApiException::fromResponse($statusCode, $responseBody, $endpoint);
        }
    }

    /**
     * Create the Guzzle HTTP client with configured options.
     *
     * @return Client
     */
    private function createClient(): Client
    {
        $stack = HandlerStack::create();

        // Add logging middleware in debug mode
        if ($this->config->isDebugEnabled()) {
            $stack->push($this->createLoggingMiddleware());
        }

        return new Client([
            'base_uri' => $this->config->baseUrl,
            'timeout' => $this->config->timeout,
            'headers' => $this->config->getHeaders(),
            'handler' => $stack,
            'http_errors' => true,
            'verify' => true // Enable SSL verification
        ]);
    }

    /**
     * Create middleware for request/response logging.
     *
     * @return callable
     */
    private function createLoggingMiddleware(): callable
    {
        return Middleware::tap(
            function (RequestInterface $request) {
                error_log(sprintf(
                    '[KRA-Connect Request] %s %s',
                    $request->getMethod(),
                    $request->getUri()
                ));

                if ($this->config->debug) {
                    error_log('[KRA-Connect Request Headers] ' . json_encode($request->getHeaders()));
                }
            },
            function (ResponseInterface $response) {
                error_log(sprintf(
                    '[KRA-Connect Response] Status: %d',
                    $response->getStatusCode()
                ));

                if ($this->config->debug) {
                    error_log('[KRA-Connect Response Body] ' . (string) $response->getBody());
                }
            }
        );
    }

    /**
     * Get the underlying Guzzle client.
     *
     * @return Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }
}
