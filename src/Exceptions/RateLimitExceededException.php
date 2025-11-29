<?php

declare(strict_types=1);

namespace KraConnect\Exceptions;

/**
 * Exception thrown when the API rate limit is exceeded.
 *
 * The KRA GavaConnect API has rate limits to prevent abuse.
 * When the rate limit is exceeded, requests will be rejected until
 * the rate limit window resets.
 *
 * @package KraConnect\Exceptions
 */
class RateLimitExceededException extends KraConnectException
{
    private int $retryAfter;

    /**
     * Create exception for rate limit exceeded.
     *
     * @param int $retryAfter Number of seconds to wait before retrying
     * @param int $limit The rate limit that was exceeded
     * @param int $windowSeconds The rate limit window in seconds
     */
    public function __construct(int $retryAfter, int $limit = 0, int $windowSeconds = 0)
    {
        $this->retryAfter = $retryAfter;

        $message = sprintf(
            'API rate limit exceeded. Please retry after %d seconds.',
            $retryAfter
        );

        $details = [
            'retry_after' => $retryAfter,
            'error_type' => 'rate_limit_exceeded'
        ];

        if ($limit > 0) {
            $details['limit'] = $limit;
            $details['window_seconds'] = $windowSeconds;
            $message .= sprintf(' (Limit: %d requests per %d seconds)', $limit, $windowSeconds);
        }

        parent::__construct($message, $details, 429);
    }

    /**
     * Get the number of seconds to wait before retrying.
     *
     * @return int
     */
    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }

    /**
     * Get the rate limit that was exceeded.
     *
     * @return int|null
     */
    public function getLimit(): ?int
    {
        return $this->details['limit'] ?? null;
    }

    /**
     * Get the rate limit window in seconds.
     *
     * @return int|null
     */
    public function getWindowSeconds(): ?int
    {
        return $this->details['window_seconds'] ?? null;
    }

    /**
     * Get the timestamp when the request can be retried.
     *
     * @return int Unix timestamp
     */
    public function getRetryTimestamp(): int
    {
        return time() + $this->retryAfter;
    }
}
