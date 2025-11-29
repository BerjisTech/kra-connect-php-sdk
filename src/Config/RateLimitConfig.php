<?php

declare(strict_types=1);

namespace KraConnect\Config;

/**
 * Rate Limit Configuration
 *
 * Configuration for rate limiting API requests using token bucket algorithm.
 *
 * @package KraConnect\Config
 */
class RateLimitConfig
{
    /**
     * Create a new rate limit configuration.
     *
     * @param bool $enabled Whether rate limiting is enabled
     * @param int $maxRequests Maximum number of requests allowed
     * @param int $windowSeconds Time window in seconds
     * @param bool $blockOnLimit Whether to block (wait) when limit is reached
     * @param float $refillRate Token refill rate per second
     * @param int $burstSize Maximum burst size (max tokens)
     */
    public function __construct(
        public readonly bool $enabled = true,
        public readonly int $maxRequests = 100,
        public readonly int $windowSeconds = 60,
        public readonly bool $blockOnLimit = true,
        public readonly ?float $refillRate = null,
        public readonly ?int $burstSize = null
    ) {
        if ($maxRequests <= 0) {
            throw new \InvalidArgumentException('maxRequests must be > 0');
        }

        if ($windowSeconds <= 0) {
            throw new \InvalidArgumentException('windowSeconds must be > 0');
        }

        if ($refillRate !== null && $refillRate <= 0) {
            throw new \InvalidArgumentException('refillRate must be > 0');
        }

        if ($burstSize !== null && $burstSize <= 0) {
            throw new \InvalidArgumentException('burstSize must be > 0');
        }
    }

    /**
     * Create a configuration with rate limiting disabled.
     *
     * @return self
     */
    public static function disabled(): self
    {
        return new self(enabled: false);
    }

    /**
     * Create a configuration with strict rate limiting.
     *
     * @return self
     */
    public static function strict(): self
    {
        return new self(
            enabled: true,
            maxRequests: 50,
            windowSeconds: 60,
            blockOnLimit: true
        );
    }

    /**
     * Create a configuration with lenient rate limiting.
     *
     * @return self
     */
    public static function lenient(): self
    {
        return new self(
            enabled: true,
            maxRequests: 200,
            windowSeconds: 60,
            blockOnLimit: true
        );
    }

    /**
     * Get the token refill rate per second.
     *
     * If not explicitly set, calculate from maxRequests and windowSeconds.
     *
     * @return float Tokens per second
     */
    public function getRefillRate(): float
    {
        if ($this->refillRate !== null) {
            return $this->refillRate;
        }

        return $this->maxRequests / $this->windowSeconds;
    }

    /**
     * Get the burst size (maximum tokens).
     *
     * If not explicitly set, use maxRequests.
     *
     * @return int Maximum tokens
     */
    public function getBurstSize(): int
    {
        return $this->burstSize ?? $this->maxRequests;
    }

    /**
     * Calculate the time to wait before next request is allowed.
     *
     * @param int $currentTokens Current number of tokens
     * @param int $tokensNeeded Number of tokens needed
     * @return float Wait time in seconds
     */
    public function calculateWaitTime(int $currentTokens, int $tokensNeeded = 1): float
    {
        if ($currentTokens >= $tokensNeeded) {
            return 0.0;
        }

        $tokensShort = $tokensNeeded - $currentTokens;
        $refillRate = $this->getRefillRate();

        return $tokensShort / $refillRate;
    }

    /**
     * Check if rate limiting is enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Check if requests should block when limit is reached.
     *
     * @return bool
     */
    public function shouldBlockOnLimit(): bool
    {
        return $this->blockOnLimit;
    }

    /**
     * Get the average requests per second allowed.
     *
     * @return float Requests per second
     */
    public function getAverageRequestsPerSecond(): float
    {
        return $this->maxRequests / $this->windowSeconds;
    }

    /**
     * Create a new configuration with updated values.
     *
     * @param array<string, mixed> $updates Configuration updates
     * @return self
     */
    public function with(array $updates): self
    {
        return new self(
            enabled: $updates['enabled'] ?? $this->enabled,
            maxRequests: $updates['maxRequests'] ?? $this->maxRequests,
            windowSeconds: $updates['windowSeconds'] ?? $this->windowSeconds,
            blockOnLimit: $updates['blockOnLimit'] ?? $this->blockOnLimit,
            refillRate: $updates['refillRate'] ?? $this->refillRate,
            burstSize: $updates['burstSize'] ?? $this->burstSize
        );
    }

    /**
     * Convert configuration to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'max_requests' => $this->maxRequests,
            'window_seconds' => $this->windowSeconds,
            'block_on_limit' => $this->blockOnLimit,
            'refill_rate' => $this->getRefillRate(),
            'burst_size' => $this->getBurstSize(),
            'avg_requests_per_second' => $this->getAverageRequestsPerSecond()
        ];
    }
}
