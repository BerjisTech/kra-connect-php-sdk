<?php

declare(strict_types=1);

namespace KraConnect\Tests\Integration;

use KraConnect\Tests\TestCase;
use KraConnect\KraClient;
use KraConnect\Config\KraConfig;
use KraConnect\Config\RetryConfig;
use KraConnect\Exceptions\ApiAuthenticationException;
use KraConnect\Exceptions\ApiTimeoutException;
use KraConnect\Exceptions\RateLimitExceededException;
use KraConnect\Exceptions\ApiException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;

/**
 * Integration tests for error handling scenarios.
 */
class ErrorHandlingIntegrationTest extends TestCase
{
    private function createMockedClient(array $responses, ?RetryConfig $retryConfig = null): KraClient
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);

        $config = new KraConfig(
            apiKey: 'test-api-key',
            retryConfig: $retryConfig ?? RetryConfig::noRetry()
        );

        $client = new KraClient($config);

        $httpClient = $client->getHttpClient();
        $reflection = new \ReflectionClass($httpClient);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($httpClient, new \GuzzleHttp\Client(['handler' => $handlerStack]));

        return $client;
    }

    public function testAuthenticationError(): void
    {
        $this->expectException(ApiAuthenticationException::class);
        $this->expectExceptionMessage('Invalid API key');

        $client = $this->createMockedClient([
            new Response(401, [], json_encode(['error' => 'Unauthorized']))
        ]);

        $client->verifyPin('P051234567A');
    }

    public function testRateLimitError(): void
    {
        $this->expectException(RateLimitExceededException::class);

        $client = $this->createMockedClient([
            new Response(429, ['Retry-After' => '60'], json_encode(['error' => 'Rate limit exceeded']))
        ]);

        $client->verifyPin('P051234567A');
    }

    public function testServerError(): void
    {
        $this->expectException(ApiException::class);

        $client = $this->createMockedClient([
            new Response(500, [], json_encode(['error' => 'Internal server error']))
        ]);

        $client->verifyPin('P051234567A');
    }

    public function testRetryOnServerError(): void
    {
        $retryConfig = new RetryConfig(
            maxRetries: 2,
            initialDelay: 0.01, // Very short for testing
            maxDelay: 0.1
        );

        $client = $this->createMockedClient([
            new Response(503, [], json_encode(['error' => 'Service unavailable'])),
            new Response(503, [], json_encode(['error' => 'Service unavailable'])),
            new Response(200, [], json_encode([
                'pin_number' => 'P051234567A',
                'is_valid' => true,
                'taxpayer_name' => 'John Doe'
            ]))
        ], $retryConfig);

        $result = $client->verifyPin('P051234567A');

        $this->assertTrue($result->isValid);
    }

    public function testRetryExhausted(): void
    {
        $this->expectException(ApiException::class);

        $retryConfig = new RetryConfig(
            maxRetries: 2,
            initialDelay: 0.01
        );

        $client = $this->createMockedClient([
            new Response(503, [], json_encode(['error' => 'Service unavailable'])),
            new Response(503, [], json_encode(['error' => 'Service unavailable'])),
            new Response(503, [], json_encode(['error' => 'Service unavailable']))
        ], $retryConfig);

        $client->verifyPin('P051234567A');
    }

    public function testNetworkError(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessageMatches('/Network error/');

        $client = $this->createMockedClient([
            new ConnectException('Connection timeout', new Request('POST', '/verify-pin'))
        ]);

        $client->verifyPin('P051234567A');
    }

    public function testInvalidJsonResponse(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessageMatches('/not valid JSON/');

        $client = $this->createMockedClient([
            new Response(200, [], 'Invalid JSON{')
        ]);

        $client->verifyPin('P051234567A');
    }

    public function testEmptyResponse(): void
    {
        $client = $this->createMockedClient([
            new Response(200, [], '')
        ]);

        // Empty response should return default values
        $result = $client->verifyPin('P051234567A');

        $this->assertInstanceOf(\KraConnect\Models\PinVerificationResult::class, $result);
    }
}
