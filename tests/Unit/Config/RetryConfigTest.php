<?php

declare(strict_types=1);

namespace KraConnect\Tests\Unit\Config;

use KraConnect\Tests\TestCase;
use KraConnect\Config\RetryConfig;

class RetryConfigTest extends TestCase
{
    public function testConstructorWithDefaults(): void
    {
        $config = new RetryConfig();

        $this->assertSame(3, $config->maxRetries);
        $this->assertSame(1.0, $config->initialDelay);
        $this->assertSame(32.0, $config->maxDelay);
        $this->assertSame(2.0, $config->backoffMultiplier);
        $this->assertTrue($config->retryOnTimeout);
        $this->assertTrue($config->retryOnServerError);
        $this->assertTrue($config->retryOnRateLimit);
    }

    public function testConstructorWithCustomValues(): void
    {
        $config = new RetryConfig(
            maxRetries: 5,
            initialDelay: 2.0,
            maxDelay: 60.0,
            backoffMultiplier: 3.0,
            retryOnTimeout: false
        );

        $this->assertSame(5, $config->maxRetries);
        $this->assertSame(2.0, $config->initialDelay);
        $this->assertSame(60.0, $config->maxDelay);
        $this->assertSame(3.0, $config->backoffMultiplier);
        $this->assertFalse($config->retryOnTimeout);
    }

    public function testConstructorValidatesMaxRetries(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new RetryConfig(maxRetries: -1);
    }

    public function testConstructorValidatesInitialDelay(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new RetryConfig(initialDelay: 0);
    }

    public function testConstructorValidatesMaxDelay(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new RetryConfig(initialDelay: 10.0, maxDelay: 5.0);
    }

    public function testNoRetry(): void
    {
        $config = RetryConfig::noRetry();

        $this->assertSame(0, $config->maxRetries);
    }

    public function testAggressive(): void
    {
        $config = RetryConfig::aggressive();

        $this->assertSame(5, $config->maxRetries);
        $this->assertSame(0.5, $config->initialDelay);
        $this->assertSame(60.0, $config->maxDelay);
    }

    public function testConservative(): void
    {
        $config = RetryConfig::conservative();

        $this->assertSame(2, $config->maxRetries);
        $this->assertSame(2.0, $config->initialDelay);
    }

    public function testCalculateDelay(): void
    {
        $config = new RetryConfig(
            initialDelay: 1.0,
            backoffMultiplier: 2.0,
            maxDelay: 32.0
        );

        $this->assertSame(1.0, $config->calculateDelay(1)); // 1.0 * 2^0
        $this->assertSame(2.0, $config->calculateDelay(2)); // 1.0 * 2^1
        $this->assertSame(4.0, $config->calculateDelay(3)); // 1.0 * 2^2
        $this->assertSame(8.0, $config->calculateDelay(4)); // 1.0 * 2^3
        $this->assertSame(32.0, $config->calculateDelay(10)); // Capped at maxDelay
    }

    public function testShouldRetryStatusCode(): void
    {
        $config = new RetryConfig();

        // Should retry default retryable codes
        $this->assertTrue($config->shouldRetryStatusCode(408));
        $this->assertTrue($config->shouldRetryStatusCode(429));
        $this->assertTrue($config->shouldRetryStatusCode(500));
        $this->assertTrue($config->shouldRetryStatusCode(502));
        $this->assertTrue($config->shouldRetryStatusCode(503));
        $this->assertTrue($config->shouldRetryStatusCode(504));

        // Should not retry client errors
        $this->assertFalse($config->shouldRetryStatusCode(400));
        $this->assertFalse($config->shouldRetryStatusCode(404));
    }

    public function testHasReachedMaxRetries(): void
    {
        $config = new RetryConfig(maxRetries: 3);

        $this->assertFalse($config->hasReachedMaxRetries(1));
        $this->assertFalse($config->hasReachedMaxRetries(2));
        $this->assertFalse($config->hasReachedMaxRetries(3));
        $this->assertTrue($config->hasReachedMaxRetries(4));
        $this->assertTrue($config->hasReachedMaxRetries(5));
    }

    public function testWith(): void
    {
        $config = new RetryConfig(maxRetries: 3);

        $newConfig = $config->with(['maxRetries' => 5, 'initialDelay' => 2.0]);

        $this->assertSame(5, $newConfig->maxRetries);
        $this->assertSame(2.0, $newConfig->initialDelay);

        // Original unchanged
        $this->assertSame(3, $config->maxRetries);
        $this->assertSame(1.0, $config->initialDelay);
    }

    public function testToArray(): void
    {
        $config = new RetryConfig();

        $array = $config->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('max_retries', $array);
        $this->assertArrayHasKey('initial_delay', $array);
        $this->assertArrayHasKey('max_delay', $array);
        $this->assertArrayHasKey('backoff_multiplier', $array);
    }
}
