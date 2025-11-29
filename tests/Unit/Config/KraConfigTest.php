<?php

declare(strict_types=1);

namespace KraConnect\Tests\Unit\Config;

use KraConnect\Tests\TestCase;
use KraConnect\Config\KraConfig;
use KraConnect\Config\RetryConfig;
use KraConnect\Config\CacheConfig;
use KraConnect\Config\RateLimitConfig;

class KraConfigTest extends TestCase
{
    public function testConstructorWithApiKey(): void
    {
        $config = new KraConfig(apiKey: 'test-api-key');

        $this->assertSame('test-api-key', $config->apiKey);
        $this->assertSame('https://api.kra.go.ke/gavaconnect/v1', $config->baseUrl);
        $this->assertSame(30.0, $config->timeout);
        $this->assertFalse($config->debug);
    }

    public function testConstructorWithEmptyApiKeyThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('API key is required');

        new KraConfig(apiKey: '');
    }

    public function testConstructorWithInvalidTimeoutThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new KraConfig(apiKey: 'test', timeout: 0);
    }

    public function testConstructorWithCustomValues(): void
    {
        $retryConfig = new RetryConfig(maxRetries: 5);
        $cacheConfig = CacheConfig::disabled();
        $rateLimitConfig = RateLimitConfig::strict();

        $config = new KraConfig(
            apiKey: 'test-key',
            baseUrl: 'https://custom.api.com',
            timeout: 60.0,
            retryConfig: $retryConfig,
            cacheConfig: $cacheConfig,
            rateLimitConfig: $rateLimitConfig,
            debug: true
        );

        $this->assertSame('https://custom.api.com', $config->baseUrl);
        $this->assertSame(60.0, $config->timeout);
        $this->assertTrue($config->debug);
        $this->assertSame($retryConfig, $config->retryConfig);
    }

    public function testFromEnvWithEnvironmentVariable(): void
    {
        $_ENV['KRA_API_KEY'] = 'env-api-key';

        $config = KraConfig::fromEnv();

        $this->assertSame('env-api-key', $config->apiKey);

        unset($_ENV['KRA_API_KEY']);
    }

    public function testFromEnvWithOverrides(): void
    {
        $config = KraConfig::fromEnv([
            'apiKey' => 'override-key',
            'timeout' => 45.0
        ]);

        $this->assertSame('override-key', $config->apiKey);
        $this->assertSame(45.0, $config->timeout);
    }

    public function testFromEnvWithMissingApiKeyThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        KraConfig::fromEnv();
    }

    public function testGetHeaders(): void
    {
        $config = new KraConfig(apiKey: 'test-key');

        $headers = $config->getHeaders();

        $this->assertIsArray($headers);
        $this->assertArrayHasKey('Authorization', $headers);
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertArrayHasKey('Accept', $headers);
        $this->assertArrayHasKey('User-Agent', $headers);
        $this->assertSame('Bearer test-key', $headers['Authorization']);
        $this->assertSame('application/json', $headers['Content-Type']);
    }

    public function testGetHeadersWithAdditionalHeaders(): void
    {
        $config = new KraConfig(
            apiKey: 'test-key',
            additionalHeaders: ['X-Custom' => 'value']
        );

        $headers = $config->getHeaders();

        $this->assertArrayHasKey('X-Custom', $headers);
        $this->assertSame('value', $headers['X-Custom']);
    }

    public function testGetUserAgentDefault(): void
    {
        $config = new KraConfig(apiKey: 'test-key');

        $userAgent = $config->getUserAgent();

        $this->assertStringContainsString('kra-connect-php', $userAgent);
        $this->assertStringContainsString('PHP', $userAgent);
    }

    public function testGetUserAgentCustom(): void
    {
        $config = new KraConfig(
            apiKey: 'test-key',
            userAgent: 'Custom-Agent/1.0'
        );

        $this->assertSame('Custom-Agent/1.0', $config->getUserAgent());
    }

    public function testIsDebugEnabled(): void
    {
        $debugConfig = new KraConfig(apiKey: 'test', debug: true);
        $normalConfig = new KraConfig(apiKey: 'test', debug: false);

        $this->assertTrue($debugConfig->isDebugEnabled());
        $this->assertFalse($normalConfig->isDebugEnabled());
    }

    public function testWith(): void
    {
        $config = new KraConfig(apiKey: 'test-key');

        $newConfig = $config->with(['timeout' => 60.0, 'debug' => true]);

        $this->assertSame('test-key', $newConfig->apiKey);
        $this->assertSame(60.0, $newConfig->timeout);
        $this->assertTrue($newConfig->debug);

        // Original config unchanged
        $this->assertSame(30.0, $config->timeout);
        $this->assertFalse($config->debug);
    }

    public function testToArray(): void
    {
        $config = new KraConfig(apiKey: 'test-key');

        $array = $config->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('api_key', $array);
        $this->assertArrayHasKey('base_url', $array);
        $this->assertArrayHasKey('timeout', $array);
        $this->assertSame('***REDACTED***', $array['api_key']); // API key should be masked
    }
}
