<?php

declare(strict_types=1);

namespace KraConnect\Exceptions;

/**
 * Exception thrown when an API request times out.
 *
 * This occurs when the API server doesn't respond within the configured timeout period.
 * The SDK will automatically retry timed-out requests based on the retry configuration.
 *
 * @package KraConnect\Exceptions
 */
class ApiTimeoutException extends KraConnectException
{
    /**
     * Create exception for API timeout.
     *
     * @param string $endpoint The endpoint that timed out
     * @param float $timeout The timeout duration in seconds
     * @param int $attemptNumber The attempt number that timed out
     */
    public function __construct(string $endpoint, float $timeout, int $attemptNumber = 1)
    {
        $message = sprintf(
            'API request to "%s" timed out after %.2f seconds (attempt %d)',
            $endpoint,
            $timeout,
            $attemptNumber
        );

        parent::__construct($message, [
            'endpoint' => $endpoint,
            'timeout' => $timeout,
            'attempt_number' => $attemptNumber,
            'error_type' => 'timeout'
        ], 408);
    }

    /**
     * Get the endpoint that timed out.
     *
     * @return string
     */
    public function getEndpoint(): string
    {
        return $this->details['endpoint'];
    }

    /**
     * Get the timeout duration.
     *
     * @return float
     */
    public function getTimeout(): float
    {
        return $this->details['timeout'];
    }

    /**
     * Get the attempt number.
     *
     * @return int
     */
    public function getAttemptNumber(): int
    {
        return $this->details['attempt_number'];
    }
}
