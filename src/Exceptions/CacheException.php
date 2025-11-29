<?php

declare(strict_types=1);

namespace KraConnect\Exceptions;

/**
 * Exception thrown when cache operations fail.
 *
 * This exception is used for various cache-related errors including:
 * - Failed to read from cache
 * - Failed to write to cache
 * - Failed to delete from cache
 * - Cache connection errors
 * - Cache serialization errors
 *
 * @package KraConnect\Exceptions
 */
class CacheException extends KraConnectException
{
    /**
     * Create exception for cache operation failure.
     *
     * @param string $message The error message
     * @param array<string, mixed> $details Additional error details
     */
    public function __construct(string $message = 'Cache operation failed', array $details = [])
    {
        parent::__construct($message, $details);
    }

    /**
     * Create exception for cache read failure.
     *
     * @param string $key The cache key
     * @param string $reason The reason for failure
     * @return self
     */
    public static function readFailed(string $key, string $reason = ''): self
    {
        $message = sprintf('Failed to read from cache for key "%s"', $key);
        if ($reason) {
            $message .= ': ' . $reason;
        }

        return new self($message, [
            'operation' => 'read',
            'key' => $key,
            'reason' => $reason
        ]);
    }

    /**
     * Create exception for cache write failure.
     *
     * @param string $key The cache key
     * @param string $reason The reason for failure
     * @return self
     */
    public static function writeFailed(string $key, string $reason = ''): self
    {
        $message = sprintf('Failed to write to cache for key "%s"', $key);
        if ($reason) {
            $message .= ': ' . $reason;
        }

        return new self($message, [
            'operation' => 'write',
            'key' => $key,
            'reason' => $reason
        ]);
    }

    /**
     * Create exception for cache delete failure.
     *
     * @param string $key The cache key
     * @param string $reason The reason for failure
     * @return self
     */
    public static function deleteFailed(string $key, string $reason = ''): self
    {
        $message = sprintf('Failed to delete from cache for key "%s"', $key);
        if ($reason) {
            $message .= ': ' . $reason;
        }

        return new self($message, [
            'operation' => 'delete',
            'key' => $key,
            'reason' => $reason
        ]);
    }

    /**
     * Create exception for cache connection failure.
     *
     * @param string $reason The reason for failure
     * @return self
     */
    public static function connectionFailed(string $reason): self
    {
        return new self(
            'Failed to connect to cache: ' . $reason,
            [
                'operation' => 'connect',
                'reason' => $reason
            ]
        );
    }

    /**
     * Create exception for cache serialization failure.
     *
     * @param string $key The cache key
     * @param string $reason The reason for failure
     * @return self
     */
    public static function serializationFailed(string $key, string $reason): self
    {
        return new self(
            sprintf('Failed to serialize cache value for key "%s": %s', $key, $reason),
            [
                'operation' => 'serialize',
                'key' => $key,
                'reason' => $reason
            ]
        );
    }

    /**
     * Create exception for cache deserialization failure.
     *
     * @param string $key The cache key
     * @param string $reason The reason for failure
     * @return self
     */
    public static function deserializationFailed(string $key, string $reason): self
    {
        return new self(
            sprintf('Failed to deserialize cache value for key "%s": %s', $key, $reason),
            [
                'operation' => 'deserialize',
                'key' => $key,
                'reason' => $reason
            ]
        );
    }

    /**
     * Get the cache operation that failed.
     *
     * @return string|null
     */
    public function getOperation(): ?string
    {
        return $this->details['operation'] ?? null;
    }

    /**
     * Get the cache key involved in the operation.
     *
     * @return string|null
     */
    public function getCacheKey(): ?string
    {
        return $this->details['key'] ?? null;
    }
}
