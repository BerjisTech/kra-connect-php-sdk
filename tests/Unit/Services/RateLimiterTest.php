<?php

declare(strict_types=1);

namespace KraConnect\Tests\Unit\Services;

use KraConnect\Tests\TestCase;
use KraConnect\Services\RateLimiter;
use KraConnect\Config\RateLimitConfig;
use KraConnect\Exceptions\RateLimitExceededException;

class RateLimiterTest extends TestCase
{
    public function testAcquireWithAvailableTokens(): void
    {
        $config = new RateLimitConfig(
            enabled: true,
            maxRequests: 10,
            windowSeconds: 10
        );
        $limiter = new RateLimiter($config);

        $result = $limiter->acquire();

        $this->assertTrue($result);
        $this->assertSame(9, $limiter->getAvailableTokens());
    }

    public function testAcquireMultipleTokens(): void
    {
        $config = new RateLimitConfig(
            enabled: true,
            maxRequests: 10,
            windowSeconds: 10
        );
        $limiter = new RateLimiter($config);

        $result = $limiter->acquire(3);

        $this->assertTrue($result);
        $this->assertSame(7, $limiter->getAvailableTokens());
    }

    public function testTryAcquireSuccess(): void
    {
        $config = new RateLimitConfig(enabled: true, maxRequests: 10);
        $limiter = new RateLimiter($config);

        $result = $limiter->tryAcquire();

        $this->assertTrue($result);
    }

    public function testTryAcquireFailureWhenNoTokens(): void
    {
        $config = new RateLimitConfig(
            enabled: true,
            maxRequests: 1,
            windowSeconds: 10,
            blockOnLimit: false
        );
        $limiter = new RateLimiter($config);

        // Use the only token
        $limiter->acquire(1, false);

        // Try to acquire when no tokens available
        $result = $limiter->tryAcquire();

        $this->assertFalse($result);
    }

    public function testHasTokens(): void
    {
        $config = new RateLimitConfig(enabled: true, maxRequests: 5);
        $limiter = new RateLimiter($config);

        $this->assertTrue($limiter->hasTokens());
        $this->assertTrue($limiter->hasTokens(5));
        $this->assertFalse($limiter->hasTokens(6));
    }

    public function testGetWaitTime(): void
    {
        $config = new RateLimitConfig(
            enabled: true,
            maxRequests: 10,
            windowSeconds: 10
        );
        $limiter = new RateLimiter($config);

        // With available tokens
        $this->assertSame(0.0, $limiter->getWaitTime(1));

        // Use all tokens
        $limiter->acquire(10, false);

        // Should need to wait
        $waitTime = $limiter->getWaitTime(1);
        $this->assertGreaterThan(0, $waitTime);
    }

    public function testReset(): void
    {
        $config = new RateLimitConfig(enabled: true, maxRequests: 10);
        $limiter = new RateLimiter($config);

        // Use some tokens
        $limiter->acquire(5);
        $this->assertSame(5, $limiter->getAvailableTokens());

        // Reset
        $limiter->reset();
        $this->assertSame(10, $limiter->getAvailableTokens());
    }

    public function testGetStats(): void
    {
        $config = new RateLimitConfig(
            enabled: true,
            maxRequests: 100,
            windowSeconds: 60
        );
        $limiter = new RateLimiter($config);

        $limiter->acquire(10);

        $stats = $limiter->getStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('enabled', $stats);
        $this->assertArrayHasKey('available_tokens', $stats);
        $this->assertArrayHasKey('burst_size', $stats);
        $this->assertArrayHasKey('refill_rate', $stats);
        $this->assertTrue($stats['enabled']);
        $this->assertSame(90, $stats['available_tokens']);
    }

    public function testExecute(): void
    {
        $config = new RateLimitConfig(enabled: true, maxRequests: 10);
        $limiter = new RateLimiter($config);

        $result = $limiter->execute(function () {
            return 'test-value';
        });

        $this->assertSame('test-value', $result);
        $this->assertSame(9, $limiter->getAvailableTokens());
    }

    public function testExecuteBatch(): void
    {
        $config = new RateLimitConfig(enabled: true, maxRequests: 10);
        $limiter = new RateLimiter($config);

        $callables = [
            fn() => 'value1',
            fn() => 'value2',
            fn() => 'value3',
        ];

        $results = $limiter->executeBatch($callables);

        $this->assertCount(3, $results);
        $this->assertSame(['value1', 'value2', 'value3'], $results);
        $this->assertSame(7, $limiter->getAvailableTokens());
    }

    public function testDisabledRateLimiter(): void
    {
        $config = new RateLimitConfig(enabled: false);
        $limiter = new RateLimiter($config);

        // Should always succeed when disabled
        for ($i = 0; $i < 1000; $i++) {
            $result = $limiter->acquire();
            $this->assertTrue($result);
        }
    }

    public function testTokenRefill(): void
    {
        $config = new RateLimitConfig(
            enabled: true,
            maxRequests: 10,
            windowSeconds: 1 // 10 tokens per second
        );
        $limiter = new RateLimiter($config);

        // Use all tokens
        $limiter->acquire(10, false);
        $this->assertSame(0, $limiter->getAvailableTokens());

        // Wait for refill (0.5 seconds should add 5 tokens)
        usleep(500000);

        $available = $limiter->getAvailableTokens();
        $this->assertGreaterThanOrEqual(4, $available);
        $this->assertLessThanOrEqual(6, $available);
    }
}
