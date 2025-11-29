<?php

declare(strict_types=1);

namespace KraConnect\Services;

use KraConnect\Config\RetryConfig;
use KraConnect\Exceptions\ApiException;
use KraConnect\Exceptions\ApiTimeoutException;
use KraConnect\Exceptions\RateLimitExceededException;

/**
 * Retry Handler Service
 *
 * Implements retry logic with exponential backoff for failed API requests.
 *
 * @package KraConnect\Services
 */
class RetryHandler
{
    /**
     * Create a new retry handler.
     *
     * @param RetryConfig $config The retry configuration
     */
    public function __construct(
        private readonly RetryConfig $config
    ) {
    }

    /**
     * Execute a callable with retry logic.
     *
     * @template T
     * @param callable(): T $callable The function to execute
     * @param string $context Context information for logging (e.g., endpoint)
     * @return T The result from the callable
     * @throws ApiException If all retries are exhausted
     */
    public function execute(callable $callable, string $context = '')
    {
        $attemptNumber = 0;
        $lastException = null;

        while ($attemptNumber <= $this->config->maxRetries) {
            $attemptNumber++;

            try {
                return $callable();
            } catch (\Exception $exception) {
                $lastException = $exception;

                // Don't retry if we've exhausted retries
                if ($this->config->hasReachedMaxRetries($attemptNumber)) {
                    throw $exception;
                }

                // Check if this exception should be retried
                if (!$this->shouldRetry($exception)) {
                    throw $exception;
                }

                // Calculate and apply backoff delay
                $delay = $this->config->calculateDelay($attemptNumber);
                $this->logRetry($attemptNumber, $delay, $context, $exception);
                $this->sleep($delay);
            }
        }

        // This should never be reached, but throw last exception if it somehow is
        if ($lastException !== null) {
            throw $lastException;
        }

        throw new ApiException('Retry logic failed unexpectedly');
    }

    /**
     * Determine if an exception should trigger a retry.
     *
     * @param \Exception $exception The exception to check
     * @return bool True if should retry, false otherwise
     */
    private function shouldRetry(\Exception $exception): bool
    {
        // Always retry timeout errors if configured
        if ($exception instanceof ApiTimeoutException && $this->config->retryOnTimeout) {
            return true;
        }

        // Always retry rate limit errors if configured
        if ($exception instanceof RateLimitExceededException && $this->config->retryOnRateLimit) {
            // For rate limit, use the retry-after time from exception
            return true;
        }

        // Retry API exceptions based on status code
        if ($exception instanceof ApiException) {
            $statusCode = $exception->getStatusCode();

            if ($statusCode !== null) {
                return $this->config->shouldRetryStatusCode($statusCode);
            }
        }

        // Don't retry other exceptions by default
        return false;
    }

    /**
     * Sleep for the specified delay.
     *
     * @param float $seconds Number of seconds to sleep
     * @return void
     */
    private function sleep(float $seconds): void
    {
        usleep((int) ($seconds * 1000000));
    }

    /**
     * Log retry attempt information.
     *
     * @param int $attemptNumber The current attempt number
     * @param float $delay The delay before next retry
     * @param string $context Context information
     * @param \Exception $exception The exception that triggered the retry
     * @return void
     */
    private function logRetry(int $attemptNumber, float $delay, string $context, \Exception $exception): void
    {
        error_log(sprintf(
            '[KRA-Connect Retry] Attempt %d/%d for "%s" after %.2fs delay. Error: %s',
            $attemptNumber,
            $this->config->maxRetries + 1,
            $context,
            $delay,
            $exception->getMessage()
        ));
    }

    /**
     * Execute a callable with custom retry configuration.
     *
     * @template T
     * @param callable(): T $callable The function to execute
     * @param RetryConfig $customConfig Custom retry configuration
     * @param string $context Context information for logging
     * @return T The result from the callable
     * @throws ApiException If all retries are exhausted
     */
    public function executeWithConfig(callable $callable, RetryConfig $customConfig, string $context = '')
    {
        $originalConfig = $this->config;
        $tempHandler = new self($customConfig);

        try {
            return $tempHandler->execute($callable, $context);
        } finally {
            // Config is readonly, so no need to restore
        }
    }

    /**
     * Get the retry configuration.
     *
     * @return RetryConfig
     */
    public function getConfig(): RetryConfig
    {
        return $this->config;
    }
}
