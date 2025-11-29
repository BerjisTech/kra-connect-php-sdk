<?php

declare(strict_types=1);

namespace KraConnect\Config;

/**
 * Retry Configuration
 *
 * Configuration for retry behavior with exponential backoff.
 *
 * @package KraConnect\Config
 */
class RetryConfig
{
    /**
     * Create a new retry configuration.
     *
     * @param int $maxRetries Maximum number of retry attempts
     * @param float $initialDelay Initial delay in seconds before first retry
     * @param float $maxDelay Maximum delay in seconds between retries
     * @param float $backoffMultiplier Multiplier for exponential backoff
     * @param bool $retryOnTimeout Whether to retry on timeout errors
     * @param bool $retryOnServerError Whether to retry on server errors (5xx)
     * @param bool $retryOnRateLimit Whether to retry on rate limit errors
     * @param array<int> $retryableStatusCodes Additional HTTP status codes to retry
     */
    public function __construct(
        public readonly int $maxRetries = 3,
        public readonly float $initialDelay = 1.0,
        public readonly float $maxDelay = 32.0,
        public readonly float $backoffMultiplier = 2.0,
        public readonly bool $retryOnTimeout = true,
        public readonly bool $retryOnServerError = true,
        public readonly bool $retryOnRateLimit = true,
        public readonly array $retryableStatusCodes = [408, 429, 500, 502, 503, 504]
    ) {
        if ($maxRetries < 0) {
            throw new \InvalidArgumentException('maxRetries must be >= 0');
        }

        if ($initialDelay <= 0) {
            throw new \InvalidArgumentException('initialDelay must be > 0');
        }

        if ($maxDelay < $initialDelay) {
            throw new \InvalidArgumentException('maxDelay must be >= initialDelay');
        }

        if ($backoffMultiplier <= 1.0) {
            throw new \InvalidArgumentException('backoffMultiplier must be > 1.0');
        }
    }

    /**
     * Create a configuration with no retries.
     *
     * @return self
     */
    public static function noRetry(): self
    {
        return new self(maxRetries: 0);
    }

    /**
     * Create a configuration with aggressive retry strategy.
     *
     * @return self
     */
    public static function aggressive(): self
    {
        return new self(
            maxRetries: 5,
            initialDelay: 0.5,
            maxDelay: 60.0,
            backoffMultiplier: 2.0
        );
    }

    /**
     * Create a configuration with conservative retry strategy.
     *
     * @return self
     */
    public static function conservative(): self
    {
        return new self(
            maxRetries: 2,
            initialDelay: 2.0,
            maxDelay: 16.0,
            backoffMultiplier: 2.0
        );
    }

    /**
     * Calculate the delay for a specific retry attempt using exponential backoff.
     *
     * @param int $attemptNumber The attempt number (1-indexed)
     * @return float Delay in seconds
     */
    public function calculateDelay(int $attemptNumber): float
    {
        if ($attemptNumber <= 0) {
            return 0.0;
        }

        $delay = $this->initialDelay * pow($this->backoffMultiplier, $attemptNumber - 1);

        return min($delay, $this->maxDelay);
    }

    /**
     * Check if a specific HTTP status code should be retried.
     *
     * @param int $statusCode The HTTP status code
     * @return bool
     */
    public function shouldRetryStatusCode(int $statusCode): bool
    {
        // Always retry on configured retryable status codes
        if (in_array($statusCode, $this->retryableStatusCodes, true)) {
            return true;
        }

        // Retry on server errors if enabled
        if ($this->retryOnServerError && $statusCode >= 500 && $statusCode < 600) {
            return true;
        }

        // Retry on rate limit if enabled
        if ($this->retryOnRateLimit && $statusCode === 429) {
            return true;
        }

        return false;
    }

    /**
     * Check if the maximum retries have been reached.
     *
     * @param int $attemptNumber The current attempt number (1-indexed)
     * @return bool
     */
    public function hasReachedMaxRetries(int $attemptNumber): bool
    {
        return $attemptNumber > $this->maxRetries;
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
            maxRetries: $updates['maxRetries'] ?? $this->maxRetries,
            initialDelay: $updates['initialDelay'] ?? $this->initialDelay,
            maxDelay: $updates['maxDelay'] ?? $this->maxDelay,
            backoffMultiplier: $updates['backoffMultiplier'] ?? $this->backoffMultiplier,
            retryOnTimeout: $updates['retryOnTimeout'] ?? $this->retryOnTimeout,
            retryOnServerError: $updates['retryOnServerError'] ?? $this->retryOnServerError,
            retryOnRateLimit: $updates['retryOnRateLimit'] ?? $this->retryOnRateLimit,
            retryableStatusCodes: $updates['retryableStatusCodes'] ?? $this->retryableStatusCodes
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
            'max_retries' => $this->maxRetries,
            'initial_delay' => $this->initialDelay,
            'max_delay' => $this->maxDelay,
            'backoff_multiplier' => $this->backoffMultiplier,
            'retry_on_timeout' => $this->retryOnTimeout,
            'retry_on_server_error' => $this->retryOnServerError,
            'retry_on_rate_limit' => $this->retryOnRateLimit,
            'retryable_status_codes' => $this->retryableStatusCodes
        ];
    }
}
