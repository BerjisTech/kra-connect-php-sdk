<?php

declare(strict_types=1);

namespace KraConnect\Services;

use KraConnect\Config\RateLimitConfig;
use KraConnect\Exceptions\RateLimitExceededException;

/**
 * Rate Limiter Service
 *
 * Implements token bucket algorithm for rate limiting API requests.
 *
 * @package KraConnect\Services
 */
class RateLimiter
{
    private float $tokens;
    private float $lastRefillTime;

    /**
     * Create a new rate limiter.
     *
     * @param RateLimitConfig $config The rate limit configuration
     */
    public function __construct(
        private readonly RateLimitConfig $config
    ) {
        $this->tokens = (float) $this->config->getBurstSize();
        $this->lastRefillTime = microtime(true);
    }

    /**
     * Acquire tokens for a request.
     *
     * @param int $tokensNeeded Number of tokens needed (default: 1)
     * @param bool $block Whether to block (wait) if tokens are not available
     * @return bool True if tokens were acquired
     * @throws RateLimitExceededException If tokens not available and not blocking
     */
    public function acquire(int $tokensNeeded = 1, bool $block = true): bool
    {
        if (!$this->config->isEnabled()) {
            return true;
        }

        if ($tokensNeeded <= 0) {
            throw new \InvalidArgumentException('tokensNeeded must be > 0');
        }

        while (true) {
            $this->refillTokens();

            // Check if we have enough tokens
            if ($this->tokens >= $tokensNeeded) {
                $this->tokens -= $tokensNeeded;
                return true;
            }

            // If not blocking, throw exception
            if (!$block && !$this->config->shouldBlockOnLimit()) {
                $waitTime = $this->config->calculateWaitTime((int) $this->tokens, $tokensNeeded);
                throw new RateLimitExceededException(
                    (int) ceil($waitTime),
                    $this->config->maxRequests,
                    $this->config->windowSeconds
                );
            }

            // Calculate wait time
            $waitTime = $this->config->calculateWaitTime((int) $this->tokens, $tokensNeeded);

            // Sleep and retry
            $this->sleep($waitTime);
        }
    }

    /**
     * Try to acquire tokens without blocking.
     *
     * @param int $tokensNeeded Number of tokens needed
     * @return bool True if tokens were acquired, false otherwise
     */
    public function tryAcquire(int $tokensNeeded = 1): bool
    {
        try {
            return $this->acquire($tokensNeeded, false);
        } catch (RateLimitExceededException $e) {
            return false;
        }
    }

    /**
     * Refill tokens based on elapsed time.
     *
     * @return void
     */
    private function refillTokens(): void
    {
        $now = microtime(true);
        $elapsedTime = $now - $this->lastRefillTime;

        // Calculate tokens to add based on elapsed time
        $tokensToAdd = $elapsedTime * $this->config->getRefillRate();

        // Add tokens, but don't exceed burst size
        $this->tokens = min(
            $this->tokens + $tokensToAdd,
            (float) $this->config->getBurstSize()
        );

        $this->lastRefillTime = $now;
    }

    /**
     * Get the current number of available tokens.
     *
     * @return int Number of available tokens
     */
    public function getAvailableTokens(): int
    {
        $this->refillTokens();
        return (int) floor($this->tokens);
    }

    /**
     * Check if tokens are available for a request.
     *
     * @param int $tokensNeeded Number of tokens needed
     * @return bool True if tokens are available
     */
    public function hasTokens(int $tokensNeeded = 1): bool
    {
        $this->refillTokens();
        return $this->tokens >= $tokensNeeded;
    }

    /**
     * Get the wait time until tokens are available.
     *
     * @param int $tokensNeeded Number of tokens needed
     * @return float Wait time in seconds (0 if tokens are available)
     */
    public function getWaitTime(int $tokensNeeded = 1): float
    {
        $this->refillTokens();

        if ($this->tokens >= $tokensNeeded) {
            return 0.0;
        }

        return $this->config->calculateWaitTime((int) $this->tokens, $tokensNeeded);
    }

    /**
     * Reset the rate limiter to initial state.
     *
     * @return void
     */
    public function reset(): void
    {
        $this->tokens = (float) $this->config->getBurstSize();
        $this->lastRefillTime = microtime(true);
    }

    /**
     * Get rate limiter statistics.
     *
     * @return array<string, mixed> Rate limiter statistics
     */
    public function getStats(): array
    {
        $this->refillTokens();

        return [
            'enabled' => $this->config->isEnabled(),
            'available_tokens' => (int) floor($this->tokens),
            'burst_size' => $this->config->getBurstSize(),
            'refill_rate' => $this->config->getRefillRate(),
            'max_requests' => $this->config->maxRequests,
            'window_seconds' => $this->config->windowSeconds,
            'avg_requests_per_second' => $this->config->getAverageRequestsPerSecond(),
            'utilization_percentage' => round((1 - ($this->tokens / $this->config->getBurstSize())) * 100, 2)
        ];
    }

    /**
     * Sleep for the specified duration.
     *
     * @param float $seconds Number of seconds to sleep
     * @return void
     */
    private function sleep(float $seconds): void
    {
        if ($seconds > 0) {
            usleep((int) ($seconds * 1000000));
        }
    }

    /**
     * Get the rate limit configuration.
     *
     * @return RateLimitConfig
     */
    public function getConfig(): RateLimitConfig
    {
        return $this->config;
    }

    /**
     * Execute a callable with rate limiting.
     *
     * @template T
     * @param callable(): T $callable The function to execute
     * @param int $tokensNeeded Number of tokens needed (default: 1)
     * @return T The result from the callable
     * @throws RateLimitExceededException If rate limit is exceeded
     */
    public function execute(callable $callable, int $tokensNeeded = 1)
    {
        $this->acquire($tokensNeeded);
        return $callable();
    }

    /**
     * Batch execute multiple callables with rate limiting.
     *
     * @template T
     * @param array<callable(): T> $callables The functions to execute
     * @param int $tokensPerCall Tokens needed per call (default: 1)
     * @return array<T> Results from all callables
     * @throws RateLimitExceededException If rate limit is exceeded
     */
    public function executeBatch(array $callables, int $tokensPerCall = 1): array
    {
        $results = [];

        foreach ($callables as $callable) {
            $results[] = $this->execute($callable, $tokensPerCall);
        }

        return $results;
    }
}
